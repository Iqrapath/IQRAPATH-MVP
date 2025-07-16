<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of notifications.
     */
    public function index(Request $request)
    {
        $notifications = Notification::with('sender')
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(5)
            ->withQueryString();
            
        // Format pagination data to match what the frontend expects
        $formattedNotifications = [
            'data' => $notifications->items(),
            'links' => [
                'prev' => $notifications->previousPageUrl(),
                'next' => $notifications->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'from' => $notifications->firstItem() ?? 0,
                'last_page' => $notifications->lastPage(),
                'links' => $notifications->linkCollection()->toArray(),
                'path' => $notifications->path(),
                'per_page' => $notifications->perPage(),
                'to' => $notifications->lastItem() ?? 0,
                'total' => $notifications->total(),
            ],
        ];

        return Inertia::render('admin/notification', [
            'notifications' => $formattedNotifications,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new notification.
     */
    public function create()
    {
        $templates = \App\Models\NotificationTemplate::active()->get();
        
        return Inertia::render('admin/notification-component/notification-create', [
            'templates' => $templates,
            'roles' => ['super-admin', 'admin', 'teacher', 'student', 'guardian'],
        ]);
    }

    /**
     * Store a newly created notification in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|string',
            'recipient_type' => 'required|string|in:all,role,specific',
            'roles' => 'required_if:recipient_type,role|array',
            'user_ids' => 'required_if:recipient_type,specific|array',
            'channels' => 'required|array',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        // Determine initial status
        $status = 'draft';
        if ($request->scheduled_at) {
            $status = 'scheduled';
        }

        // Create notification
        $notification = $this->notificationService->createNotification([
            'title' => $request->title,
            'body' => $request->body,
            'type' => $request->type,
            'status' => $status,
            'sender_type' => 'admin',
            'sender_id' => $request->user()->id,
            'scheduled_at' => $request->scheduled_at,
        ]);

        // Add recipients
        $recipientData = [];
        switch ($request->recipient_type) {
            case 'all':
                $recipientData['all_users'] = true;
                break;
            case 'role':
                $recipientData['roles'] = $request->roles;
                break;
            case 'specific':
                $recipientData['user_ids'] = $request->user_ids;
                break;
        }
        $recipientData['channels'] = $request->channels;
        
        $this->notificationService->addRecipients($notification, $recipientData);

        // Send immediately if not scheduled
        if (!$request->scheduled_at) {
            $this->notificationService->sendNotification($notification);
        }

        return redirect()->route('admin.notification')
            ->with('success', 'Notification created successfully.');
    }

    /**
     * Display the specified notification.
     */
    public function show(Notification $notification)
    {
        $notification->load(['sender', 'recipients.user']);
        
        // Ensure sender is properly formatted even if null
        $notificationData = $notification->toArray();
        if (!$notification->sender) {
            $notificationData['sender'] = [
                'id' => null,
                'name' => 'System'
            ];
        }
        
        // Group recipients by status for analytics
        $analytics = [
            'total' => $notification->recipients->count(),
            'delivered' => $notification->recipients->where('status', 'delivered')->count(),
            'read' => $notification->recipients->where('status', 'read')->count(),
            'failed' => $notification->recipients->where('status', 'failed')->count(),
            'pending' => $notification->recipients->whereIn('status', ['pending', 'sent'])->count(),
        ];
        
        return Inertia::render('admin/notification-component/notification-show', [
            'notification' => $notificationData,
            'recipients' => $notification->recipients,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Show the form for editing the specified notification.
     */
    public function edit(Notification $notification)
    {
        // Get recipient data
        $recipientType = 'all';
        $roles = [];
        $userIds = [];
        $channels = [];
        $selectedUsers = [];
        
        $recipients = $notification->recipients;
        if ($recipients->isNotEmpty()) {
            // Check if it's for all users
            $allUsers = $recipients->where('user_id', null)->first();
            if (!$allUsers) {
                // Check if it's for specific roles
                $roleRecipients = $recipients->where('role', '!=', null);
                if ($roleRecipients->isNotEmpty()) {
                    $recipientType = 'role';
                    $roles = $roleRecipients->pluck('role')->unique()->toArray();
                } else {
                    // It's for specific users
                    $recipientType = 'specific';
                    $userIds = $recipients->pluck('user_id')->unique()->toArray();
                    
                    // Get user details for selected users
                    $selectedUsers = User::whereIn('id', $userIds)
                        ->select('id', 'name', 'email', 'role', 'avatar')
                        ->get();
                }
            }
            
            // Get channels
            $channels = $recipients->pluck('channel')->unique()->toArray();
        }

        return Inertia::render('admin/notification-component/notification-edit', [
            'notification' => $notification,
            'recipientType' => $recipientType,
            'roles' => $roles,
            'userIds' => $userIds,
            'channels' => $channels,
            'allRoles' => ['super-admin', 'admin', 'teacher', 'student', 'guardian'],
            'selectedUsers' => $selectedUsers,
        ]);
    }

    /**
     * Update the specified notification in storage.
     */
    public function update(Request $request, Notification $notification)
    {
        // Allow updating regardless of status
        
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|string',
            'status' => 'required|string|in:draft,scheduled,sent,delivered,read,failed',
            'recipient_type' => 'required|string|in:all,role,specific',
            'roles' => 'required_if:recipient_type,role|array',
            'user_ids' => 'required_if:recipient_type,specific|array',
            'channels' => 'required|array',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        // Determine status based on scheduling
        $status = $request->status;
        if ($request->scheduled_at) {
            $status = 'scheduled';
        } elseif (!$request->scheduled_at && in_array($status, ['draft', 'scheduled'])) {
            $status = 'draft';
        }

        // Update notification
        $notification->update([
            'title' => $request->title,
            'body' => $request->body,
            'type' => $request->type,
            'status' => $status,
            'scheduled_at' => $request->scheduled_at,
        ]);

        // Delete existing recipients
        $notification->recipients()->delete();

        // Add new recipients
        $recipientData = [];
        switch ($request->recipient_type) {
            case 'all':
                $recipientData['all_users'] = true;
                break;
            case 'role':
                $recipientData['roles'] = $request->roles;
                break;
            case 'specific':
                $recipientData['user_ids'] = $request->user_ids;
                break;
        }
        $recipientData['channels'] = $request->channels;
        
        $this->notificationService->addRecipients($notification, $recipientData);

        return redirect()->route('admin.notification.show', $notification)
            ->with('success', 'Notification updated successfully.');
    }

    /**
     * Send the notification.
     */
    public function send(Notification $notification)
    {
        if (!in_array($notification->status, ['draft', 'scheduled'])) {
            return redirect()->route('admin.notification.show', $notification)
                ->with('error', 'This notification has already been sent.');
        }
        
        // Check if notification is scheduled for future
        if ($notification->scheduled_at && $notification->scheduled_at->isFuture()) {
            $notification->status = 'scheduled';
            $notification->save();
            
            return redirect()->route('admin.notification.show', $notification)
                ->with('success', 'Notification has been scheduled for ' . $notification->scheduled_at->format('M d, Y h:i A'));
        }
        
        $success = $this->notificationService->sendNotification($notification);
        
        if ($success) {
            return redirect()->route('admin.notification.show', $notification)
                ->with('success', 'Notification sent successfully.');
        } else {
            return redirect()->route('admin.notification.show', $notification)
                ->with('error', 'Failed to send notification.');
        }
    }

    /**
     * Remove the specified notification from storage.
     */
    public function destroy(Notification $notification)
    {
        $notification->recipients()->delete();
        $notification->delete();
        
        return redirect()->route('admin.notification')
            ->with('success', 'Notification deleted successfully.');
    }

    /**
     * Display a listing of notification history.
     */
    public function history(Request $request)
    {
        $notifications = Notification::with('sender')
            ->whereIn('status', ['sent', 'delivered'])
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
            
        // Format pagination data to match what the frontend expects
        $formattedNotifications = [
            'data' => $notifications->items(),
            'links' => [
                'prev' => $notifications->previousPageUrl(),
                'next' => $notifications->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'from' => $notifications->firstItem() ?? 0,
                'last_page' => $notifications->lastPage(),
                'links' => $notifications->linkCollection()->toArray(),
                'path' => $notifications->path(),
                'per_page' => $notifications->perPage(),
                'to' => $notifications->lastItem() ?? 0,
                'total' => $notifications->total(),
            ],
        ];

        return Inertia::render('admin/notification-component/notification-history', [
            'notifications' => $formattedNotifications,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    /**
     * Search for users by name or email.
     */
    public function searchUsers(Request $request)
    {
        $query = $request->query('query');
        
        Log::info('User search query', [
            'query' => $query,
            'request_all' => $request->all()
        ]);
        
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->select('id', 'name', 'email', 'role', 'avatar')
            ->limit(10)
            ->get();
        
        Log::info('User search results', [
            'count' => $users->count(),
            'users' => $users->toArray()
        ]);

        return response()->json($users);
    }

    /**
     * Create a test user for development purposes.
     */
    public function createTestUser()
    {
        $user = User::create([
            'name' => 'Test User ' . rand(100, 999),
            'email' => 'test' . rand(100, 999) . '@example.com',
            'password' => bcrypt('password'),
            'role' => array_rand(array_flip(['student', 'teacher', 'guardian', 'admin'])),
        ]);

        return response()->json([
            'message' => 'Test user created successfully',
            'user' => $user
        ]);
    }

    /**
     * Get paginated notifications for the current user.
     */
    public function getUserNotifications(Request $request)
    {
        $user = $request->user();
        $perPage = $request->query('per_page', 5);
        $page = $request->query('page', 1);
        
        // Get notification recipients for this user
        $recipients = NotificationRecipient::where('user_id', $user->id)
            ->where('channel', 'in-app')
            ->with(['notification' => function($query) {
                $query->select('id', 'title', 'body', 'created_at', 'type', 'status');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
            
        // Format the notifications for the frontend
        $notifications = $recipients->map(function($recipient) {
            $notification = $recipient->notification;
            return [
                'id' => $recipient->id,
                'title' => $recipient->personalized_title,
                'body' => \Illuminate\Support\Str::limit($recipient->personalized_body, 100),
                'created_at' => $notification->created_at->diffForHumans(),
                'type' => $notification->type,
                'status' => $recipient->status,
                'is_read' => !is_null($recipient->read_at),
            ];
        });
        
        // Get unread count
        $unreadCount = NotificationRecipient::where('user_id', $user->id)
            ->where('channel', 'in-app')
            ->whereNull('read_at')
            ->count();
            
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'pagination' => [
                'total' => $recipients->total(),
                'per_page' => $recipients->perPage(),
                'current_page' => $recipients->currentPage(),
                'last_page' => $recipients->lastPage(),
                'from' => $recipients->firstItem() ?? 0,
                'to' => $recipients->lastItem() ?? 0,
            ]
        ]);
    }

    /**
     * Display a listing of the admin's notifications.
     *
     * @return \Inertia\Response
     */
    public function viewUserNotifications(Request $request)
    {
        $user = Auth::user();
        
        // Get pagination parameters
        $perPage = $request->input('per_page', 10);
        
        // Get search and filter parameters
        $search = $request->input('search');
        $type = $request->input('type');
        $status = $request->input('status');
        
        // Query the notification recipients for this admin
        $query = NotificationRecipient::where('user_id', $user->id)
            ->where('channel', 'in-app')
            ->with('notification');
            
        // Apply search filter if provided
        if ($search) {
            $query->whereHas('notification', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }
        
        // Apply type filter if provided
        if ($type && $type !== 'all') {
            $query->whereHas('notification', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }
        
        // Apply status filter if provided
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        // Get paginated results
        $notifications = $query->orderBy('created_at', 'desc')
                              ->paginate($perPage)
                              ->withQueryString();
        
        // Format the notifications for the frontend
        $formattedNotifications = [
            'data' => collect($notifications->items())->map(function ($recipient) {
                return [
                    'id' => $recipient->id,
                    'title' => $recipient->personalized_title,
                    'body' => $recipient->personalized_body,
                    'type' => $recipient->notification->type,
                    'status' => $recipient->status,
                    'created_at' => $recipient->created_at->diffForHumans(),
                ];
            })->toArray(),
            'links' => [
                'prev' => $notifications->previousPageUrl(),
                'next' => $notifications->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'from' => $notifications->firstItem() ?? 0,
                'last_page' => $notifications->lastPage(),
                'links' => $notifications->linkCollection()->map(function ($link) {
                    return [
                        'url' => $link['url'],
                        'label' => $link['label'],
                        'active' => $link['active'],
                    ];
                })->toArray(),
                'path' => $notifications->path(),
                'per_page' => $notifications->perPage(),
                'to' => $notifications->lastItem() ?? 0,
                'total' => $notifications->total(),
            ],
        ];
        
        return Inertia::render('admin/notification', [
            'notifications' => $formattedNotifications,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Display the specified notification.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function viewUserNotification($id)
    {
        // Get the notification details
        $notification = NotificationRecipient::with('notification')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        // Mark as read if not already
        if (!$notification->read_at) {
            $notification->markAsRead();
        }
        
        // Check if content is personalized
        $isPersonalized = isset($notification->personalized_content['title']) || 
                         isset($notification->personalized_content['body']);
        
        // Format the notification data for the frontend
        $formattedNotification = [
            'id' => $notification->id,
            'title' => $notification->personalized_title,
            'body' => $notification->personalized_body,
            'type' => $notification->notification->type,
            'status' => $notification->status,
            'created_at' => $notification->created_at,
            'sender' => $notification->notification->sender_id ? User::find($notification->notification->sender_id) : null,
            'is_personalized' => $isPersonalized,
            'metadata' => $notification->notification->metadata,
        ];
        
        return Inertia::render('admin/notifications/show', [
            'notification' => $formattedNotification,
            'currentUser' => Auth::user(),
        ]);
    }
    
    /**
     * Mark a notification as read.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsRead($id)
    {
        $notification = NotificationRecipient::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        $notification->markAsRead();
        
        return redirect()->back();
    }
    
    /**
     * Mark all notifications as read.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        
        // Update all unread notifications for this user
        NotificationRecipient::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        return redirect()->back();
    }
    
    /**
     * Delete a notification.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyUserNotification($id)
    {
        $notification = NotificationRecipient::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
            
        $notification->delete();
        
        return redirect()->route('admin.notification');
    }
} 
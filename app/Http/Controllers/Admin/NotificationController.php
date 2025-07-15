<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\NotificationTemplate;
use App\Models\NotificationTrigger;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class NotificationController extends Controller
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
        $templates = NotificationTemplate::active()->get();
        
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

        return redirect()->route('admin.notification.index')
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
        // Only allow deleting draft notifications
        if ($notification->status !== 'draft') {
            return redirect()->route('admin.notification.index')
                ->with('error', 'Only draft notifications can be deleted.');
        }
        
        $notification->recipients()->delete();
        $notification->delete();
        
        return redirect()->route('admin.notification.index')
            ->with('success', 'Notification deleted successfully.');
    }

    /**
     * Display a listing of notification templates.
     */
    public function templates(Request $request)
    {
        $templates = NotificationTemplate::when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
            
        // Format pagination data to match what the frontend expects
        $formattedTemplates = [
            'data' => $templates->items(),
            'links' => [
                'prev' => $templates->previousPageUrl(),
                'next' => $templates->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $templates->currentPage(),
                'from' => $templates->firstItem() ?? 0,
                'last_page' => $templates->lastPage(),
                'links' => $templates->linkCollection()->toArray(),
                'path' => $templates->path(),
                'per_page' => $templates->perPage(),
                'to' => $templates->lastItem() ?? 0,
                'total' => $templates->total(),
            ],
        ];

        return Inertia::render('admin/notification-component/templates', [
            'templates' => $formattedTemplates,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function createTemplate()
    {
        return Inertia::render('admin/notification-component/template-create');
    }

    /**
     * Store a newly created template in storage.
     */
    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|string',
            'is_active' => 'boolean',
        ]);

        NotificationTemplate::create([
            'name' => $request->name,
            'title' => $request->title,
            'body' => $request->body,
            'type' => $request->type,
            'is_active' => $request->is_active ?? true,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.notification.templates')
            ->with('success', 'Template created successfully.');
    }
    
    /**
     * Display the specified template.
     */
    public function showTemplate(NotificationTemplate $template)
    {
        return Inertia::render('admin/notification-component/template-show', [
            'template' => $template,
        ]);
    }
    
    /**
     * Show the form for editing the specified template.
     */
    public function editTemplate(NotificationTemplate $template)
    {
        return Inertia::render('admin/notification-component/template-edit', [
            'template' => $template,
        ]);
    }
    
    /**
     * Update the specified template in storage.
     */
    public function updateTemplate(Request $request, NotificationTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|string',
            'placeholders' => 'nullable|array',
            'is_active' => 'boolean',
        ]);
        
        $template->update([
            'name' => $request->name,
            'title' => $request->title,
            'body' => $request->body,
            'type' => $request->type,
            'placeholders' => $request->placeholders,
            'is_active' => $request->is_active,
        ]);
        
        return redirect()->route('admin.notification.templates')
            ->with('success', 'Template updated successfully.');
    }
    
    /**
     * Remove the specified template from storage.
     */
    public function destroyTemplate(NotificationTemplate $template)
    {
        // Check if template is used by any triggers
        if ($template->triggers()->count() > 0) {
            return redirect()->route('admin.notification.templates')
                ->with('error', 'Cannot delete template that is used by triggers.');
        }
        
        $template->delete();
        
        return redirect()->route('admin.notification.templates')
            ->with('success', 'Template deleted successfully.');
    }

    /**
     * Display a listing of notification triggers.
     */
    public function triggers(Request $request)
    {
        $triggers = NotificationTrigger::with('template')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('is_active', $status === 'active');
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
            
        // Format pagination data to match what the frontend expects
        $formattedTriggers = [
            'data' => $triggers->items(),
            'links' => [
                'prev' => $triggers->previousPageUrl(),
                'next' => $triggers->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $triggers->currentPage(),
                'from' => $triggers->firstItem() ?? 0,
                'last_page' => $triggers->lastPage(),
                'links' => $triggers->linkCollection()->toArray(),
                'path' => $triggers->path(),
                'per_page' => $triggers->perPage(),
                'to' => $triggers->lastItem() ?? 0,
                'total' => $triggers->total(),
            ],
        ];

        return Inertia::render('admin/notification-component/triggers', [
            'triggers' => $formattedTriggers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new trigger.
     */
    public function createTrigger()
    {
        $templates = NotificationTemplate::active()->get();
        
        return Inertia::render('admin/notification-component/trigger-create', [
            'templates' => $templates,
            'events' => [
                'user.registered' => 'User Registered',
                'payment.processed' => 'Payment Processed',
                'subscription.expiring' => 'Subscription Expiring',
                'session.scheduled' => 'Session Scheduled',
            ],
        ]);
    }

    /**
     * Store a newly created trigger in storage.
     */
    public function storeTrigger(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'event' => 'required|string',
            'template_id' => 'required|exists:notification_templates,id',
            'conditions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        NotificationTrigger::create([
            'name' => $request->name,
            'event' => $request->event,
            'template_id' => $request->template_id,
            'conditions' => $request->conditions ?? [],
            'is_active' => $request->is_active ?? true,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.notification.triggers')
            ->with('success', 'Trigger created successfully.');
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
                'id' => $notification->id,
                'title' => $notification->title,
                'body' => \Illuminate\Support\Str::limit($notification->body, 100),
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
} 
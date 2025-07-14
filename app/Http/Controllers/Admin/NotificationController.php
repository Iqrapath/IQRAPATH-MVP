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
            'scheduled_at' => 'nullable|date',
        ]);

        // Create notification
        $notification = $this->notificationService->createNotification([
            'title' => $request->title,
            'body' => $request->body,
            'type' => $request->type,
            'status' => 'draft',
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

        return redirect()->route('admin.notifications.index')
            ->with('success', 'Notification created successfully.');
    }

    /**
     * Display the specified notification.
     */
    public function show(Notification $notification)
    {
        $notification->load(['sender', 'recipients.user']);
        
        // Group recipients by status for analytics
        $analytics = [
            'total' => $notification->recipients->count(),
            'delivered' => $notification->recipients->where('status', 'delivered')->count(),
            'read' => $notification->recipients->where('status', 'read')->count(),
            'failed' => $notification->recipients->where('status', 'failed')->count(),
            'pending' => $notification->recipients->whereIn('status', ['pending', 'sent'])->count(),
        ];
        
        return Inertia::render('admin/notification-component/notification-show', [
            'notification' => $notification,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Show the form for editing the specified notification.
     */
    public function edit(Notification $notification)
    {
        if ($notification->status !== 'draft') {
            return redirect()->route('admin.notifications.show', $notification)
                ->with('error', 'Only draft notifications can be edited.');
        }
        
        $notification->load('recipients.user');
        
        // Determine recipient type and data
        $recipientType = 'specific';
        $roles = [];
        $userIds = [];
        
        $recipients = $notification->recipients;
        if ($recipients->count() === User::count()) {
            $recipientType = 'all';
        } else {
            // Check if recipients are grouped by role
            $recipientUsers = User::whereIn('id', $recipients->pluck('user_id')->unique())->get();
            $roleGroups = $recipientUsers->groupBy('role');
            
            if ($roleGroups->count() < $recipientUsers->count()) {
                $recipientType = 'role';
                $roles = $roleGroups->keys()->toArray();
            } else {
                $userIds = $recipients->pluck('user_id')->unique()->toArray();
            }
        }
        
        // Get channels
        $channels = $recipients->pluck('channel')->unique()->toArray();
        
        return Inertia::render('admin/notification-component/notification-edit', [
            'notification' => $notification,
            'recipientType' => $recipientType,
            'roles' => $roles,
            'userIds' => $userIds,
            'channels' => $channels,
            'allRoles' => ['super-admin', 'admin', 'teacher', 'student', 'guardian'],
        ]);
    }

    /**
     * Update the specified notification in storage.
     */
    public function update(Request $request, Notification $notification)
    {
        if ($notification->status !== 'draft') {
            return redirect()->route('admin.notifications.show', $notification)
                ->with('error', 'Only draft notifications can be edited.');
        }
        
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|string',
            'recipient_type' => 'required|string|in:all,role,specific',
            'roles' => 'required_if:recipient_type,role|array',
            'user_ids' => 'required_if:recipient_type,specific|array',
            'channels' => 'required|array',
            'scheduled_at' => 'nullable|date',
        ]);

        // Update notification
        $notification->update([
            'title' => $request->title,
            'body' => $request->body,
            'type' => $request->type,
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

        return redirect()->route('admin.notifications.show', $notification)
            ->with('success', 'Notification updated successfully.');
    }

    /**
     * Send the notification.
     */
    public function send(Notification $notification)
    {
        if (!in_array($notification->status, ['draft', 'scheduled'])) {
            return redirect()->route('admin.notifications.show', $notification)
                ->with('error', 'This notification has already been sent.');
        }
        
        $success = $this->notificationService->sendNotification($notification);
        
        if ($success) {
            return redirect()->route('admin.notifications.show', $notification)
                ->with('success', 'Notification sent successfully.');
        } else {
            return redirect()->route('admin.notifications.show', $notification)
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
            return redirect()->route('admin.notifications.index')
                ->with('error', 'Only draft notifications can be deleted.');
        }
        
        $notification->recipients()->delete();
        $notification->delete();
        
        return redirect()->route('admin.notifications.index')
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

        return Inertia::render('admin/notification-component/notification-templates', [
            'templates' => $formattedTemplates,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function createTemplate()
    {
        return Inertia::render('admin/notification-component/notification-templates');
    }

    /**
     * Store a newly created template in storage.
     */
    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:notification_templates,name',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|string',
            'placeholders' => 'nullable|array',
        ]);

        NotificationTemplate::create([
            'name' => $request->name,
            'title' => $request->title,
            'body' => $request->body,
            'type' => $request->type,
            'placeholders' => $request->placeholders,
            'is_active' => true,
        ]);

        return redirect()->route('admin.notifications.templates')
            ->with('success', 'Template created successfully.');
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
            ->when($request->event, function ($query, $event) {
                $query->where('event', $event);
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

        return Inertia::render('admin/notification-component/notification-triggers', [
            'triggers' => $formattedTriggers,
            'filters' => $request->only(['search', 'event']),
        ]);
    }

    /**
     * Show the form for creating a new trigger.
     */
    public function createTrigger()
    {
        $templates = NotificationTemplate::active()->get();
        
        return Inertia::render('Admin/Notifications/Triggers/Create', [
            'templates' => $templates,
            'eventTypes' => [
                'payment.successful' => 'Payment Successful',
                'payment.failed' => 'Payment Failed',
                'class.reminder' => 'Class Reminder',
                'class.cancelled' => 'Class Cancelled',
                'subscription.expiry' => 'Subscription Expiry',
                'user.registered' => 'User Registered',
                'user.verified' => 'User Verified',
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
            'audience_type' => 'required|string|in:all,role,specific_users',
            'audience_filter' => 'nullable|array',
            'channels' => 'required|array',
            'timing_type' => 'required|string|in:immediate,before_event,after_event',
            'timing_value' => 'required_unless:timing_type,immediate|nullable|integer',
            'timing_unit' => 'required_unless:timing_type,immediate|nullable|string|in:minutes,hours,days',
        ]);

        NotificationTrigger::create([
            'name' => $request->name,
            'event' => $request->event,
            'template_id' => $request->template_id,
            'audience_type' => $request->audience_type,
            'audience_filter' => $request->audience_filter,
            'channels' => $request->channels,
            'timing_type' => $request->timing_type,
            'timing_value' => $request->timing_value,
            'timing_unit' => $request->timing_unit,
            'is_enabled' => true,
        ]);

        return redirect()->route('admin.notifications.triggers')
            ->with('success', 'Trigger created successfully.');
    }
    
    /**
     * Display the notification history page.
     */
    public function history(Request $request)
    {
        // Get sent notifications with their recipients
        $notifications = NotificationRecipient::with(['notification', 'user'])
            ->whereHas('notification', function ($query) {
                $query->whereIn('status', ['sent', 'delivered']);
            })
            ->when($request->search, function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                if ($status !== 'all') {
                    $query->where('status', $status);
                }
            })
            ->when($request->subject, function ($query, $subject) {
                if ($subject !== 'all') {
                    $query->whereHas('notification', function ($q) use ($subject) {
                        $q->where('type', $subject);
                    });
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
            
        // Get scheduled notifications
        $scheduledNotifications = Notification::with(['sender'])
            ->where('status', 'scheduled')
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                if ($status !== 'all') {
                    $query->where('status', $status);
                }
            })
            ->when($request->subject, function ($query, $subject) {
                if ($subject !== 'all') {
                    $query->where('type', $subject);
                }
            })
            ->orderBy('scheduled_at')
            ->paginate(10)
            ->withQueryString();
            
        // Get completed teaching sessions with relationships
        $completedClasses = \App\Models\TeachingSession::with(['teacher', 'student', 'subject'])
            ->where('status', 'completed')
            ->when($request->search, function ($query, $search) {
                $query->whereHas('teacher', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('student', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                if ($status !== 'all') {
                    $query->where('status', $status);
                }
            })
            ->when($request->subject, function ($query, $subject) {
                if ($subject !== 'all') {
                    $query->whereHas('subject', function ($q) use ($subject) {
                        $q->where('name', 'like', "%{$subject}%");
                    });
                }
            })
            ->orderBy('session_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(10)
            ->withQueryString();
            
        // For debugging - check pagination structure
        if ($request->has('debug')) {
            return response()->json([
                'notifications' => $notifications,
                'links' => $notifications->links(),
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'from' => $notifications->firstItem(),
                    'last_page' => $notifications->lastPage(),
                    'links' => $notifications->linkCollection()->toArray(),
                    'path' => $notifications->path(),
                    'per_page' => $notifications->perPage(),
                    'to' => $notifications->lastItem(),
                    'total' => $notifications->total(),
                ],
            ]);
        }
            
        // Get urgent actions data from relevant models
        $urgentActions = [
            'withdrawalRequests' => \App\Models\PayoutRequest::where('status', 'pending')->count(),
            'teacherApplications' => \App\Models\VerificationRequest::where('status', 'pending')->count(),
            'pendingSessions' => \App\Models\TeachingSession::whereNull('teacher_id')
                ->orWhere('status', 'pending_teacher')
                ->count(),
            'reportedDisputes' => \App\Models\Dispute::where('status', 'reported')->count(),
        ];
        
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
        
        // Format scheduled notifications pagination data
        $formattedScheduledNotifications = [
            'data' => $scheduledNotifications->items(),
            'links' => [
                'prev' => $scheduledNotifications->previousPageUrl(),
                'next' => $scheduledNotifications->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $scheduledNotifications->currentPage(),
                'from' => $scheduledNotifications->firstItem() ?? 0,
                'last_page' => $scheduledNotifications->lastPage(),
                'links' => $scheduledNotifications->linkCollection()->toArray(),
                'path' => $scheduledNotifications->path(),
                'per_page' => $scheduledNotifications->perPage(),
                'to' => $scheduledNotifications->lastItem() ?? 0,
                'total' => $scheduledNotifications->total(),
            ],
        ];
        
        // Format completed classes pagination data
        $formattedCompletedClasses = [
            'data' => $completedClasses->items(),
            'links' => [
                'prev' => $completedClasses->previousPageUrl(),
                'next' => $completedClasses->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $completedClasses->currentPage(),
                'from' => $completedClasses->firstItem() ?? 0,
                'last_page' => $completedClasses->lastPage(),
                'links' => $completedClasses->linkCollection()->toArray(),
                'path' => $completedClasses->path(),
                'per_page' => $completedClasses->perPage(),
                'to' => $completedClasses->lastItem() ?? 0,
                'total' => $completedClasses->total(),
            ],
        ];
        
        return Inertia::render('admin/notification-component/notification-history', [
            'notifications' => $formattedNotifications,
            'scheduledNotifications' => $formattedScheduledNotifications,
            'completedClasses' => $formattedCompletedClasses,
            'urgentActions' => $urgentActions,
            'filters' => $request->only(['search', 'status', 'subject', 'rating']),
        ]);
    }
} 
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
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('admin/notification', [
            'notifications' => $notifications,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new notification.
     */
    public function create()
    {
        $templates = NotificationTemplate::active()->get();
        
        return Inertia::render('Admin/Notifications/Create', [
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
        
        return Inertia::render('Admin/Notifications/Show', [
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
        
        return Inertia::render('Admin/Notifications/Edit', [
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

        return Inertia::render('Admin/Notifications/Templates/Index', [
            'templates' => $templates,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function createTemplate()
    {
        return Inertia::render('Admin/Notifications/Templates/Create');
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

        return Inertia::render('Admin/Notifications/Triggers/Index', [
            'triggers' => $triggers,
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
} 
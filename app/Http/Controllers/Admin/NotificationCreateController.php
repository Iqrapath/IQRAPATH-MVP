<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class NotificationCreateController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Show the notification creation form.
     */
    public function create(): Response
    {
        // Get active notification templates
        $templates = NotificationTemplate::active()
            ->orderBy('name')
            ->get();

        // Get all users for recipient selection
        $users = User::select('id', 'name', 'email', 'role')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/notifications/create', [
            'templates' => $templates,
            'users' => $users,
        ]);
    }

    /**
     * Store a new notification.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:custom,template',
            'template_name' => 'nullable|string|exists:notification_templates,name',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'action_text' => 'nullable|string|max:255',
            'action_url' => 'nullable|string|max:500',
            'level' => 'required|in:info,success,warning,error',
            'audience_type' => 'required|in:all,role,individual',
            'audience_filter' => 'nullable|array',
            'audience_filter.roles' => 'nullable|array',
            'audience_filter.roles.*' => 'string|in:super-admin,admin,teacher,student,guardian',
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
            'channels' => 'required|array',
            'channels.*' => 'string|in:in-app,email,sms',
            'scheduled_for' => 'nullable|date|after:now',
            'placeholders' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $notificationData = [
                'title' => $validated['title'],
                'body' => $validated['body'],
                'action_text' => $validated['action_text'],
                'action_url' => $validated['action_url'],
                'level' => $validated['level'],
            ];

            $userIds = $validated['user_ids'];
            $channels = $validated['channels'];
            $scheduledFor = $validated['scheduled_for'];

            // Create notifications for each user
            $notifications = [];
            foreach ($userIds as $userId) {
                $user = User::find($userId);
                if (!$user) {
                    continue;
                }

                if ($validated['type'] === 'template' && $validated['template_name']) {
                    // Create from template
                    $notification = $this->notificationService->createFromTemplate(
                        $validated['template_name'],
                        $validated['placeholders'] ?? [],
                        [
                            'user_ids' => [$userId],
                            'channels' => $channels,
                            'scheduled_for' => $scheduledFor,
                        ]
                    );
                } else {
                    // Create custom notification
                    $notification = $this->notificationService->createNotification(
                        $user,
                        'custom',
                        $notificationData,
                        $validated['level']
                    );
                }

                if ($notification) {
                    $notifications[] = $notification;
                }
            }

            DB::commit();

            return redirect()->route('admin.notifications.index')
                ->with('success', 'Notification created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withErrors([
                'error' => 'Failed to create notification: ' . $e->getMessage()
            ]);
        }
    }
}

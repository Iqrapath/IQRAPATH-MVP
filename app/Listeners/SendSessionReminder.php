<?php

namespace App\Listeners;

use App\Events\SessionScheduled;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSessionReminder implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The notification service instance.
     *
     * @var \App\Services\NotificationService
     */
    protected $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(SessionScheduled $event): void
    {
        $user = $event->user;
        $session = $event->getSession();
        
        // Create a session reminder notification from template
        $notification = $this->notificationService->createFromTemplate(
            'session_reminder',
            [
                'User_Name' => $user->name,
                'Session_Title' => $session->title ?? 'Your Class',
                'Session_Time' => $session->start_time 
                    ? date('F j, Y g:i A', strtotime($session->start_time))
                    : 'Scheduled Time',
                'Teacher_Name' => $session->teacher_profile->name ?? 'Your Teacher',
                'action_url' => route('dashboard'),
                'action_text' => 'View Session Details',
            ],
            [
                'user_ids' => [$user->id],
                'channels' => ['in-app', 'email'],
            ]
        );
        
        // Schedule the notification for 1 hour before the session
        if ($notification && isset($session->start_time)) {
            $reminderTime = strtotime($session->start_time) - 3600; // 1 hour before
            if ($reminderTime > time()) {
                $notification->scheduled_at = date('Y-m-d H:i:s', $reminderTime);
                $notification->status = 'scheduled';
                $notification->save();
            } else {
                // If the session is less than 1 hour away, send immediately
                $this->notificationService->sendNotification($notification);
            }
        } elseif ($notification) {
            // If no start time, send immediately
            $this->notificationService->sendNotification($notification);
        }
    }
} 
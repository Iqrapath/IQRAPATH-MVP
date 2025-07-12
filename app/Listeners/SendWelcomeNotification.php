<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWelcomeNotification implements ShouldQueue
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
    public function handle(UserRegistered $event): void
    {
        $user = $event->user;
        
        // Create a welcome notification from template
        $notification = $this->notificationService->createFromTemplate(
            'welcome_user',
            [
                'User_Name' => $user->name,
                'action_url' => route('dashboard'),
                'action_text' => 'Go to Dashboard',
            ],
            [
                'user_ids' => [$user->id],
                'channels' => ['in-app', 'email'],
            ]
        );
        
        // Send immediately
        if ($notification) {
            $this->notificationService->sendNotification($notification);
        }
    }
} 
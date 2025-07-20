<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

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
        Log::info('SendWelcomeNotification listener triggered', ['user_id' => $event->user->id]);
        
        $user = $event->user;
        
        try {
            // Create a welcome notification
            $notification = $this->notificationService->createNotification(
                $user,
                'welcome',
                [
                    'title' => 'Welcome to IqraPath!',
                    'message' => 'Thank you for joining our platform. We are excited to have you with us.',
                    'action_text' => 'Explore Dashboard',
                    'action_url' => route('dashboard'),
                ],
                'info'
            );
            
            Log::info('Welcome notification created', [
                'notification_id' => $notification->id,
                'user_id' => $user->id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error creating welcome notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 
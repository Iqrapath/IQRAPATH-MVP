<?php

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendLoginNotification implements ShouldQueue
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
    public function handle(UserLoggedIn $event): void
    {
        Log::info('SendLoginNotification listener triggered', ['user_id' => $event->user->id]);
        
        $user = $event->user;
        
        try {
            // Create a login notification
            $notification = $this->notificationService->createNotification(
                $user,
                'login',
                [
                    'title' => 'Welcome back, ' . $user->name . '!',
                    'message' => 'You have successfully logged in to your account. If this wasn\'t you, please contact support immediately.',
                    'action_text' => 'Go to Dashboard',
                    'action_url' => route('dashboard'),
                ],
                'info'
            );
            
            Log::info('Login notification created', [
                'notification_id' => $notification->id,
                'user_id' => $user->id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error creating login notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 
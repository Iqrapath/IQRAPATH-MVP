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
            // Determine the correct dashboard URL based on user role
            $dashboardUrl = $this->getDashboardUrl($user->role);
            
            // Create a login notification
            $notification = $this->notificationService->createNotification(
                $user,
                'login',
                [
                    'title' => 'Welcome back, ' . $user->name . '!',
                    'message' => 'You have successfully logged in to your account. If this wasn\'t you, please contact support immediately.',
                    'action_text' => 'Go to Dashboard',
                    'action_url' => $dashboardUrl,
                ],
                'info'
            );
            
            Log::info('Login notification created', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'dashboard_url' => $dashboardUrl
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error creating login notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Get the dashboard URL based on user role.
     *
     * @param string|null $role
     * @return string
     */
    private function getDashboardUrl(?string $role): string
    {
        return match ($role) {
            'super-admin' => route('admin.dashboard'),
            'teacher' => route('teacher.dashboard'),
            'student' => route('student.dashboard'),
            'guardian' => route('guardian.dashboard'),
            null => route('unassigned'),
            default => route('unassigned'),
        };
    }
} 
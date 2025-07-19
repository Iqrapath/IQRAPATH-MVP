<?php

namespace App\Listeners;

use App\Events\UserRoleAssigned;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AccountRoleAssignedNotification implements ShouldQueue
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
    public function handle(UserRoleAssigned $event): void
    {
        Log::info('AccountRoleAssignedNotification listener triggered', [
            'user_id' => $event->user->id,
            'role' => $event->getRole()
        ]);
        
        $user = $event->user;
        $role = $event->getRole();
        
        // Format the role name for display
        $roleName = ucfirst(str_replace('-', ' ', $role));
        
        // Create a notification for the user
        $notification = $this->notificationService->createNotification(
            $user,
            'role_assigned',
            [
                'title' => 'Your Account Role Has Been Assigned',
                'body' => "Your account has been assigned the role of {$roleName}. You now have access to all features associated with this role.",
                'action_text' => 'Go to Dashboard',
                'action_url' => route('dashboard'),
            ],
            'success'
        );
        
        Log::info('Role assignment notification created', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'role' => $role
        ]);
    }
}

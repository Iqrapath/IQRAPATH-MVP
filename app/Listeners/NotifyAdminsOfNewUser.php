<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyAdminsOfNewUser implements ShouldQueue
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
        Log::info('NotifyAdminsOfNewUser listener triggered', ['user_id' => $event->user->id]);
        
        $newUser = $event->user;
        
        try {
            // Get all super-admin and admin users
            $adminUsers = User::whereIn('role', ['super-admin', 'admin'])
                ->where('id', '!=', $newUser->id) // Don't notify the new user if they're an admin
                ->get();

            foreach ($adminUsers as $admin) {
                // Create admin notification
                $notification = $this->notificationService->createNotification(
                    $admin,
                    'new_user_registration',
                    [
                        'title' => 'New User Registration',
                        'message' => "A new user '{$newUser->name}' has registered on the platform.",
                        'action_text' => 'View User',
                        'action_url' => route('admin.user-management.show', $newUser->id),
                        'new_user_id' => $newUser->id,
                        'new_user_name' => $newUser->name,
                        'new_user_email' => $newUser->email ?? 'No email provided',
                        'new_user_phone' => $newUser->phone ?? 'No phone provided',
                        'registration_time' => $newUser->created_at->format('Y-m-d H:i:s'),
                    ],
                    'info'
                );
                
                Log::info('Admin notification created for new user', [
                    'notification_id' => $notification->id,
                    'admin_id' => $admin->id,
                    'new_user_id' => $newUser->id
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error creating admin notifications for new user', [
                'new_user_id' => $newUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

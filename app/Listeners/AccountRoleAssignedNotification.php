<?php

namespace App\Listeners;

use App\Events\UserRoleAssigned;
use App\Models\VerificationRequest;
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
        
        // Create verification request if role is teacher
        if ($role === 'teacher') {
            try {
                $verificationRequest = VerificationRequest::create([
                    'teacher_profile_id' => $user->teacherProfile->id,
                    'status' => 'pending',
                    'docs_status' => 'pending',
                    'video_status' => 'not_scheduled',
                    'submitted_at' => now(),
                ]);
                
                Log::info('Verification request created for teacher role assignment', [
                    'verification_request_id' => $verificationRequest->id,
                    'user_id' => $user->id,
                    'teacher_profile_id' => $user->teacherProfile->id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create verification request for teacher role assignment', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                // Don't throw the exception - let the notification continue
                // The verification request creation failure will be logged for admin review
            }
        }
        
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

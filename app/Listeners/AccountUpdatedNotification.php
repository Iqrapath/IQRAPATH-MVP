<?php

namespace App\Listeners;

use App\Events\UserAccountUpdated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AccountUpdatedNotification implements ShouldQueue
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
    public function handle(UserAccountUpdated $event): void
    {
        Log::info('AccountUpdatedNotification listener triggered', [
            'user_id' => $event->user->id,
            'update_details' => $event->getUpdateDetails()
        ]);
        
        $user = $event->user;
        $updateDetails = $event->getUpdateDetails();
        
        // Create a notification for the user
        $notification = $this->notificationService->createNotification(
            $user,
            'account_updated',
            [
                'title' => 'Your Account Has Been Updated',
                'body' => "Your account information has been updated. {$updateDetails}",
                'action_text' => 'View Profile',
                'action_url' => route('settings.profile'),
            ],
            'info'
        );
        
        Log::info('Account update notification created', [
            'notification_id' => $notification->id,
            'user_id' => $user->id
        ]);
    }
}

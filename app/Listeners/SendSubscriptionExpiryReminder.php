<?php

namespace App\Listeners;

use App\Events\SubscriptionExpiring;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSubscriptionExpiryReminder implements ShouldQueue
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
    // public function __construct(NotificationService $notificationService)
    // {
    //     $this->notificationService = $notificationService;
    // }

    /**
     * Handle the event.
     */
    public function handle(SubscriptionExpiring $event): void
    {
        $user = $event->user;
        $subscription = $event->getSubscription();
        
        // Create a subscription expiry notification from template
        $notification = $this->notificationService->createFromTemplate(
            'subscription_expiry',
            [
                'User_Name' => $user->name,
                'Plan_Name' => $subscription->plan_name ?? 'Your Subscription',
                'Expiry_Date' => $subscription->expires_at 
                    ? date('F j, Y', strtotime($subscription->expires_at))
                    : 'Soon',
                'Days_Remaining' => $subscription->expires_at 
                    ? ceil((strtotime($subscription->expires_at) - time()) / 86400)
                    : '3',
                'action_url' => route('subscriptions.plans'),
                'action_text' => 'Renew Subscription',
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
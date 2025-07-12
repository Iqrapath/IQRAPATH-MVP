<?php

namespace App\Listeners;

use App\Events\PaymentProcessed;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPaymentConfirmation implements ShouldQueue
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
    public function handle(PaymentProcessed $event): void
    {
        $user = $event->user;
        $payment = $event->getPayment();
        
        // Create notification data with safe property access
        $notificationData = [
            'User_Name' => $user->name,
            'Amount_Paid' => $payment->amount ?? '0.00',
            'Currency' => $payment->currency ?? 'USD',
            'Date' => now()->format('F j, Y'),
            'Plan_Name' => $payment->plan_name ?? 'Subscription',
            'action_url' => route('subscriptions.my'),
            'action_text' => 'View Subscription',
        ];
        
        // Create a payment confirmation notification from template
        $notification = $this->notificationService->createFromTemplate(
            'payment_confirmation',
            $notificationData,
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
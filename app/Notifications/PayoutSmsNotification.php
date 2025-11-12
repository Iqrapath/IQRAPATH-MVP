<?php

namespace App\Notifications;

use App\Models\PayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class PayoutSmsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $title,
        public string $message,
        public PayoutRequest $payoutRequest
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['vonage']; // or 'twilio' if using Twilio
    }

    /**
     * Get the Vonage / SMS representation of the notification.
     */
    public function toVonage(object $notifiable): VonageMessage
    {
        $amount = $this->payoutRequest->currency === 'NGN' 
            ? '₦' . number_format($this->payoutRequest->amount, 2)
            : $this->payoutRequest->currency . ' ' . number_format($this->payoutRequest->amount, 2);
        
        // SMS messages should be concise (160 characters recommended)
        $smsMessage = "{$this->title}: {$amount} payout. {$this->message}";
        
        // Truncate if too long
        if (strlen($smsMessage) > 160) {
            $smsMessage = substr($smsMessage, 0, 157) . '...';
        }
        
        return (new VonageMessage)
            ->content($smsMessage)
            ->from('IQRAQUEST'); // Your SMS sender ID
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'payout_request_id' => $this->payoutRequest->id,
            'amount' => $this->payoutRequest->amount,
            'currency' => $this->payoutRequest->currency ?? 'NGN',
        ];
    }
}

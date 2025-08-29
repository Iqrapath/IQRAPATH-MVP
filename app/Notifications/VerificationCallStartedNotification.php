<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class VerificationCallStartedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected VerificationRequest $verificationRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(VerificationRequest $verificationRequest)
    {
        $this->verificationRequest = $verificationRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $currentTime = now()->format('l, F j, Y \a\t g:i A T');
        
        return (new MailMessage)
            ->subject('LIVE: Your Verification Call Has Started - IqraPath')
            ->markdown('emails.verification-call-started', [
                'notifiable' => $notifiable,
                'verificationRequest' => $this->verificationRequest,
                'currentTime' => $currentTime,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Verification Call Started',
            'message' => 'Your verification call is now live. Please join immediately.',
            'action_text' => $this->verificationRequest->meeting_link ? 'Join Call Now' : null,
            'action_url' => $this->verificationRequest->meeting_link ?? null,
            'verification_request_id' => $this->verificationRequest->id,
            'started_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}

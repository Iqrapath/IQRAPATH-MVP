<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class VerificationApprovedNotification extends Notification implements ShouldQueue, ShouldBroadcast
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
        $approvedDate = now()->format('l, F j, Y');
        
        return (new MailMessage)
            ->subject('APPROVED! Welcome to IqraPath Teaching Community!')
            ->markdown('emails.verification-approved', [
                'notifiable' => $notifiable,
                'verificationRequest' => $this->verificationRequest,
                'approvedDate' => $approvedDate,
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
            'title' => '🎉 Verification Approved!',
            'message' => 'Your teacher verification has been approved. Welcome to IqraPath!',
            'action_text' => null,
            'action_url' => null,
            'verification_request_id' => $this->verificationRequest->id,
            'approved_at' => now()->toIso8601String(),
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

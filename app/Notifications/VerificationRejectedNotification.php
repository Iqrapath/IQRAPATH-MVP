<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class VerificationRejectedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected VerificationRequest $verificationRequest;
    protected string $rejectionReason;

    /**
     * Create a new notification instance.
     */
    public function __construct(VerificationRequest $verificationRequest, string $rejectionReason)
    {
        $this->verificationRequest = $verificationRequest;
        $this->rejectionReason = $rejectionReason;
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
        $reviewDate = now()->format('l, F j, Y');
        
        return (new MailMessage)
            ->subject('Verification Application Update - Action Required')
            ->markdown('emails.verification-rejected', [
                'notifiable' => $notifiable,
                'verificationRequest' => $this->verificationRequest,
                'rejectionReason' => $this->rejectionReason,
                'reviewDate' => $reviewDate,
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
            'title' => 'Verification Update',
            'message' => 'Your teacher verification application has been reviewed.',
            'rejection_reason' => $this->rejectionReason,
            'action_text' => null,
            'action_url' => null,
            'verification_request_id' => $this->verificationRequest->id,
            'rejected_at' => now()->toIso8601String(),
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

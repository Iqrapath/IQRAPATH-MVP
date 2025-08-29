<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class VerificationCallCompletedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected VerificationRequest $verificationRequest;
    protected string $result;
    protected ?string $notes;

    /**
     * Create a new notification instance.
     */
    public function __construct(VerificationRequest $verificationRequest, string $result, ?string $notes = null)
    {
        $this->verificationRequest = $verificationRequest;
        $this->result = $result;
        $this->notes = $notes;
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
        $isPassed = $this->result === 'passed';
        $completedTime = now()->format('l, F j, Y \a\t g:i A T');
        
        if ($isPassed) {
            return (new MailMessage)
                ->subject('Verification Call Passed! Welcome to IqraPath')
                ->markdown('emails.verification-call-completed-passed', [
                    'notifiable' => $notifiable,
                    'verificationRequest' => $this->verificationRequest,
                    'result' => $this->result,
                    'notes' => $this->notes,
                    'isPassed' => $isPassed,
                    'completedTime' => $completedTime,
                ]);
        } else {
            return (new MailMessage)
                ->subject('Verification Call Results - Next Steps Available')
                ->markdown('emails.verification-call-completed-failed', [
                    'notifiable' => $notifiable,
                    'verificationRequest' => $this->verificationRequest,
                    'result' => $this->result,
                    'notes' => $this->notes,
                    'isPassed' => $isPassed,
                    'completedTime' => $completedTime,
                ]);
        }
    }



    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $isPassed = $this->result === 'passed';
        
        return [
            'title' => $isPassed ? 'Verification Call Passed' : 'Verification Call Failed',
            'message' => $isPassed 
                ? 'Your video verification has been completed successfully.' 
                : 'Your video verification did not meet our requirements.',
            'result' => $this->result,
            'notes' => $this->notes,
            'completed_at' => now()->toIso8601String(),
            'action_text' => null,
            'action_url' => null,
            'verification_request_id' => $this->verificationRequest->id,
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

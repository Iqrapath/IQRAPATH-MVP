<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class VerificationCallScheduledNotification extends Notification implements ShouldBroadcast
{

    protected $verificationRequest;
    protected $scheduledAt;
    protected $platform;
    protected $meetingLink;
    protected $notes;
    protected $isForTeacher;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        $verificationRequest,
        string $scheduledAt,
        string $platform,
        ?string $meetingLink = null,
        ?string $notes = null,
        bool $isForTeacher = true
    ) {
        $this->verificationRequest = $verificationRequest;
        $this->scheduledAt = $scheduledAt;
        $this->platform = $platform;
        $this->meetingLink = $meetingLink;
        $this->notes = $notes;
        $this->isForTeacher = $isForTeacher;
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
        $scheduledDate = \Carbon\Carbon::parse($this->scheduledAt);
        $platformLabel = $this->getPlatformLabel($this->platform);
        
        if ($this->isForTeacher) {
            return (new MailMessage)
                ->subject('Verification Call Scheduled - IQRAQUEST')
                ->view('emails.verification-call-scheduled', [
                    'verificationRequest' => $this->verificationRequest,
                    'scheduledDate' => $scheduledDate,
                    'platformLabel' => $platformLabel,
                    'meetingLink' => $this->meetingLink,
                    'notes' => $this->notes,
                ]);
        } else {
            // Admin notification with countdown
            return (new MailMessage)
                ->subject('Verification Call Scheduled - IQRAQUEST')
                ->view('emails.admin-verification-call-scheduled', [
                    'verificationRequest' => $this->verificationRequest,
                    'scheduledDate' => $scheduledDate,
                    'platformLabel' => $platformLabel,
                    'meetingLink' => $this->meetingLink,
                    'notes' => $this->notes,
                    'teacherName' => $this->verificationRequest->teacherProfile->user->name,
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
        $scheduledDate = \Carbon\Carbon::parse($this->scheduledAt);
        
        return [
            'title' => 'Verification Call Scheduled',
            'message' => $this->isForTeacher
                ? "Your verification call has been scheduled for {$scheduledDate->format('M d, Y g:i A')} on {$this->getPlatformLabel($this->platform)}"
                : "Verification call scheduled for {$this->verificationRequest->teacherProfile->user->name} on {$scheduledDate->format('M d, Y g:i A')}",
            'scheduled_at' => $this->scheduledAt,
            'scheduled_at_human' => $scheduledDate->format('M d, Y g:i A'),
            'platform' => $this->platform,
            'platform_label' => $this->getPlatformLabel($this->platform),
            'meeting_link' => $this->meetingLink,
            'notes' => $this->notes,
            'action_text' => $this->meetingLink ? 'Join Meeting' : null,
            'action_url' => $this->meetingLink ?: null,
            'verification_request_id' => $this->verificationRequest->id,
            'is_for_teacher' => $this->isForTeacher,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Get platform label for display.
     */
    private function getPlatformLabel(string $platform): string
    {
        return match ($platform) {
            'zoom' => 'Zoom',
            'google_meet' => 'Google Meet',
            default => ucfirst($platform),
        };
    }
}

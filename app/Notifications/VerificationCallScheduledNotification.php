<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use App\Mail\VerificationCallScheduledMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class VerificationCallScheduledNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected VerificationRequest $verificationRequest;
    protected string $scheduledAt;
    protected string $platform;
    protected ?string $meetingLink;
    protected ?string $notes;

    /**
     * Create a new notification instance.
     */
    public function __construct(VerificationRequest $verificationRequest, string $scheduledAt, string $platform, ?string $meetingLink = null, ?string $notes = null)
    {
        $this->verificationRequest = $verificationRequest;
        $this->scheduledAt = $scheduledAt;
        $this->platform = $platform;
        $this->meetingLink = $meetingLink;
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
        return (new VerificationCallScheduledMail(
            $this->verificationRequest,
            $this->scheduledAt,
            $this->platform,
            $this->meetingLink,
            $this->notes
        ))->to($notifiable->email);
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
            'message' => 'Your verification call has been scheduled for ' . $scheduledDate->format('M d, Y g:i A'),
            'scheduled_at' => $this->scheduledAt,
            'scheduled_at_human' => $scheduledDate->format('M d, Y g:i A'),
            'platform' => $this->platform,
            'platform_label' => $this->getPlatformLabel($this->platform),
            'meeting_link' => $this->meetingLink,
            'notes' => $this->notes,
            'action_text' => $this->meetingLink ? 'Join Meeting' : null,
            'action_url' => $this->meetingLink ?: null,
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

    /**
     * Generate Google Calendar URL for the verification call.
     */
    private function generateGoogleCalendarUrl(\Carbon\Carbon $scheduledDate, string $platformLabel): string
    {
        $title = 'IqraPath Teacher Verification Call';
        $startTime = $scheduledDate->utc()->format('Ymd\THis\Z');
        $endTime = $scheduledDate->addMinutes(30)->utc()->format('Ymd\THis\Z');
        
        $details = "Teacher verification call with IqraPath team.\n\n";
        $details .= "Platform: {$platformLabel}\n";
        
        if ($this->meetingLink) {
            $details .= "Meeting Link: {$this->meetingLink}\n";
        }
        
        $details .= "\nWhat to prepare:\n";
        $details .= "- Valid government-issued ID\n";
        $details .= "- Teaching certificates or qualifications\n";
        $details .= "- Stable internet connection\n";
        $details .= "- Quiet environment\n";
        $details .= "\nJoin 5 minutes early to test your setup.";
        
        $params = [
            'action' => 'TEMPLATE',
            'text' => $title,
            'dates' => $startTime . '/' . $endTime,
            'details' => $details,
            'location' => $this->meetingLink ?: 'Online - ' . $platformLabel,
            'sf' => 'true',
            'output' => 'xml'
        ];
        
        return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
    }
}

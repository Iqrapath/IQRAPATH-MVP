<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
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
        $teacher = $this->verificationRequest->teacherProfile->user;
        $scheduledDate = \Carbon\Carbon::parse($this->scheduledAt);
        $platformLabel = $this->getPlatformLabel($this->platform);
        
        // Generate calendar link for Google Calendar
        $calendarUrl = $this->generateGoogleCalendarUrl($scheduledDate, $platformLabel);
        
        $mail = (new MailMessage)
            ->subject('🎯 Your Teacher Verification Call is Scheduled - IqraPath')
            ->greeting('Hello ' . $notifiable->name . '! 👋')
            ->line('<h2 style="color: #28a745; margin: 20px 0 10px 0;">Great news! Your teacher verification call has been successfully scheduled.</h2>')
            ->line('<div style="background-color: #e3f2fd; border: 1px solid #bbdefb; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #1976d2; margin-top: 0;">📅 Meeting Details</h3>')
            ->line('<p><strong>Date:</strong> ' . $scheduledDate->format('l, F j, Y') . '</p>')
            ->line('<p><strong>Time:</strong> ' . $scheduledDate->format('g:i A T') . '</p>')
            ->line('<p><strong>Platform:</strong> ' . $platformLabel . ' 🎥</p>')
            ->line('<p><strong>Duration:</strong> Approximately 15-20 minutes</p>');
            
        if ($this->meetingLink) {
            $mail->line('<p><strong>Meeting Link:</strong> <a href="' . $this->meetingLink . '" style="color: #007bff; text-decoration: none;">Click here to join</a> 🔗</p>');
        }
        
        if ($this->notes) {
            $mail->line('<p><strong>Special Notes:</strong> ' . $this->notes . ' 📝</p>');
        }
        
        $mail->line('</div>')
            ->line('<div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #495057; margin-top: 0;">📝 What to Prepare:</h3>')
            ->line('<ul style="margin: 0; padding-left: 20px;">')
            ->line('<li>✅ Valid government-issued ID (passport, driver\'s license, or national ID)</li>')
            ->line('<li>✅ Your teaching certificates or qualifications</li>')
            ->line('<li>✅ Stable internet connection and good lighting</li>')
            ->line('<li>✅ Quiet environment without distractions</li>')
            ->line('<li>✅ Join 5 minutes early to test your audio/video</li>')
            ->line('</ul>')
            ->line('</div>')
            ->line('<div style="background-color: #fff3cd; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #856404; margin-top: 0;">🎯 During the Call:</h3>')
            ->line('<ul style="margin: 0; padding-left: 20px;">')
            ->line('<li>Our verification team will verify your identity</li>')
            ->line('<li>Review your teaching qualifications</li>')
            ->line('<li>Discuss your teaching experience and goals</li>')
            ->line('<li>Answer any questions you might have</li>')
            ->line('</ul>')
            ->line('</div>')
            ->line('<div style="background-color: #e8f5e8; border: 1px solid #c3e6cb; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center;">')
            ->line('<h3 style="color: #155724; margin-top: 0;">📅 Add to Your Calendar</h3>')
            ->line('<p>Don\'t forget about your call! <a href="' . $calendarUrl . '" style="color: #28a745; font-weight: bold; text-decoration: none;">Add to Google Calendar</a> to set a reminder.</p>')
            ->line('</div>');
            
        if ($this->meetingLink) {
            $mail->action('🚀 Join Verification Call', $this->meetingLink);
        }
        
        return $mail->line('<hr style="margin: 30px 0; border: none; border-top: 1px solid #dee2e6;">')
               ->line('<p style="color: #6c757d;"><strong>Need to reschedule?</strong> Please contact our support team at least 2 hours before your scheduled time.</p>')
               ->line('<p style="font-size: 16px; color: #28a745; font-weight: bold;">We\'re excited to welcome you to the IqraPath teaching community! 🌟</p>')
               ->salutation('<div style="margin-top: 30px; color: #495057;">Best regards,<br><strong>The IqraPath Team</strong></div>');
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

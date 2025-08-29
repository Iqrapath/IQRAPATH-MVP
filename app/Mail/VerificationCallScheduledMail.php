<?php

namespace App\Mail;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCallScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $verificationRequest;
    public $scheduledDate;
    public $platformLabel;
    public $meetingLink;
    public $notes;
    public $calendarUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        VerificationRequest $verificationRequest,
        string $scheduledAt,
        string $platform,
        ?string $meetingLink = null,
        ?string $notes = null
    ) {
        $this->verificationRequest = $verificationRequest;
        $this->scheduledDate = \Carbon\Carbon::parse($scheduledAt);
        $this->platformLabel = $this->getPlatformLabel($platform);
        $this->meetingLink = $meetingLink;
        $this->notes = $notes;
        $this->calendarUrl = $this->generateGoogleCalendarUrl($this->scheduledDate, $this->platformLabel);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Teacher Verification Call is Scheduled - IqraPath',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-call-scheduled',
            with: [
                'verificationRequest' => $this->verificationRequest,
                'scheduledDate' => $this->scheduledDate,
                'platformLabel' => $this->platformLabel,
                'meetingLink' => $this->meetingLink,
                'notes' => $this->notes,
                'calendarUrl' => $this->calendarUrl,
            ],
        );
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->view('emails.verification-call-scheduled')
                    ->with([
                        'verificationRequest' => $this->verificationRequest,
                        'scheduledDate' => $this->scheduledDate,
                        'platformLabel' => $this->platformLabel,
                        'meetingLink' => $this->meetingLink,
                        'notes' => $this->notes,
                        'calendarUrl' => $this->calendarUrl,
                    ]);
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

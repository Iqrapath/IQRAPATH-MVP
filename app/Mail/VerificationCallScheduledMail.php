<?php

namespace App\Mail;

use App\Models\VerificationRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VerificationCallScheduledMail extends Mailable
{

    public $verificationRequest;
    public $scheduledDate;
    public $platformLabel;
    public $meetingLink;
    public $notes;

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
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Teacher Verification Call is Scheduled - IQRAQUEST',
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
            ]
        );
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

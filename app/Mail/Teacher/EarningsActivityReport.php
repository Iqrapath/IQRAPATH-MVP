<?php

namespace App\Mail\Teacher;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EarningsActivityReport extends Mailable
{
    use Queueable, SerializesModels;

    public $teacher;
    public $summary;
    public $transactions;

    /**
     * Create a new message instance.
     */
    public function __construct($teacher, $summary, $transactions)
    {
        $this->teacher = $teacher;
        $this->summary = $summary;
        $this->transactions = $transactions;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Earnings Activity Report - ' . now()->format('F Y'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher.earnings-activity-report',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

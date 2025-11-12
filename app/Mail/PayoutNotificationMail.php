<?php

namespace App\Mail;

use App\Models\User;
use App\Models\PayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayoutNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $recipient,
        public string $title,
        public string $messageBody,
        public PayoutRequest $payoutRequest,
        public string $notificationType
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->title . ' - IQRAQUEST',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payout-notification',
            with: [
                'recipientName' => $this->recipient->name,
                'title' => $this->title,
                'message' => $this->messageBody,
                'amount' => $this->payoutRequest->amount,
                'currency' => $this->payoutRequest->currency ?? 'NGN',
                'paymentMethod' => $this->formatPaymentMethod($this->payoutRequest->payment_method),
                'requestDate' => $this->payoutRequest->request_date,
                'notificationType' => $this->notificationType,
                'actionUrl' => $this->recipient->role === 'teacher' 
                    ? route('teacher.financial.payout-requests.show', $this->payoutRequest->id)
                    : route('student.wallet.index'),
            ],
        );
    }

    /**
     * Format payment method for display
     */
    private function formatPaymentMethod(string $method): string
    {
        return str_replace('_', ' ', ucwords($method, '_'));
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

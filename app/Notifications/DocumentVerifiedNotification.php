<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class DocumentVerifiedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected Document $document;

    /**
     * Create a new notification instance.
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
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
        $documentType = $this->getDocumentTypeLabel($this->document->type);
        $verifiedDate = $this->document->verified_at->format('l, F j, Y \a\t g:i A');
        
        return (new MailMessage)
            ->subject('Document Approved! Great Progress on Your Verification')
            ->markdown('emails.document-verified', [
                'notifiable' => $notifiable,
                'document' => $this->document,
                'documentType' => $documentType,
                'verifiedDate' => $verifiedDate,
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
            'title' => '✅ Document Verified',
            'message' => 'Your document "' . $this->document->name . '" has been verified successfully.',
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'document_type' => $this->document->type,
            'document_type_label' => $this->getDocumentTypeLabel($this->document->type),
            'verified_at' => $this->document->verified_at->toIso8601String(),
            'action_text' => null,
            'action_url' => null,
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
     * Get document type label for display.
     */
    private function getDocumentTypeLabel(string $type): string
    {
        return match ($type) {
            'id_verification' => 'Identity Verification',
            'certificate' => 'Certificate/Qualification',
            'resume' => 'Resume/CV',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}

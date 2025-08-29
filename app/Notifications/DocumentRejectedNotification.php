<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class DocumentRejectedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected Document $document;
    protected string $rejectionReason;
    protected ?string $resubmissionInstructions;

    /**
     * Create a new notification instance.
     */
    public function __construct(Document $document, string $rejectionReason, ?string $resubmissionInstructions = null)
    {
        $this->document = $document;
        $this->rejectionReason = $rejectionReason;
        $this->resubmissionInstructions = $resubmissionInstructions;
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
        $reviewedDate = $this->document->verified_at->format('l, F j, Y \a\t g:i A');
        $remainingAttempts = $this->document->getRemainingResubmissions();
        $maxAttempts = $this->document->max_resubmissions;
        
        return (new MailMessage)
            ->subject('Document Review Complete - Resubmission Needed')
            ->markdown('emails.document-rejected', [
                'notifiable' => $notifiable,
                'document' => $this->document,
                'documentType' => $documentType,
                'rejectionReason' => $this->rejectionReason,
                'resubmissionInstructions' => $this->resubmissionInstructions,
                'reviewedDate' => $reviewedDate,
                'remainingAttempts' => $remainingAttempts,
                'maxAttempts' => $maxAttempts,
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
            'title' => '❌ Document Update',
            'message' => 'Your document "' . $this->document->name . '" needs to be resubmitted.',
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'document_type' => $this->document->type,
            'document_type_label' => $this->getDocumentTypeLabel($this->document->type),
            'rejection_reason' => $this->rejectionReason,
            'resubmission_instructions' => $this->resubmissionInstructions,
            'remaining_attempts' => $this->document->getRemainingResubmissions(),
            'max_attempts' => $this->document->max_resubmissions,
            'reviewed_at' => $this->document->verified_at->toIso8601String(),
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

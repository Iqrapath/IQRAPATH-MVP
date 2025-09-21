<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TeacherDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private TeacherDocument $document,
        private ?string $rejectionReason = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $teacher = $this->document->teacherProfile->user;
        $isForTeacher = $notifiable->id === $teacher->id;
        
        if ($isForTeacher) {
            $documentType = ucfirst(str_replace('_', ' ', $this->document->type));
            $reviewedDate = $this->document->updated_at ? 
                $this->document->updated_at->format('F j, Y \a\t g:i A') : 
                now()->format('F j, Y \a\t g:i A');
            
            // Set default values for template
            $maxAttempts = 3;
            $remainingAttempts = 2; // This should be calculated based on actual attempts
            
            return (new MailMessage)
                ->subject('Document Review Complete - Resubmission Needed')
                ->view('emails.document-rejected', [
                    'notifiable' => $notifiable,
                    'document' => $this->document,
                    'documentType' => $documentType,
                    'rejectionReason' => $this->rejectionReason ?? 'The document does not meet our quality or authenticity requirements.',
                    'reviewedDate' => $reviewedDate,
                    'resubmissionInstructions' => 'Please ensure the document is clear, complete, and in the correct format (PDF, JPG, PNG).',
                    'maxAttempts' => $maxAttempts,
                    'remainingAttempts' => $remainingAttempts,
                ]);
        } else {
            // Admin notification - keep simple for now
            return (new MailMessage)
                ->subject('Document Rejected - ' . $teacher->name)
                ->greeting('Admin Notification')
                ->line('A document has been rejected for teacher verification.')
                ->line('**Teacher Details:**')
                ->line('• Name: ' . $teacher->name)
                ->line('• Email: ' . $teacher->email)
                ->line('**Document Details:**')
                ->line('• Type: ' . ucfirst(str_replace('_', ' ', $this->document->type)))
                ->line('• Name: ' . $this->document->name)
                ->line('• Rejected by: ' . ($this->document->verifiedBy->name ?? 'System'))
                ->line('• Rejection reason: ' . ($this->rejectionReason ?? 'Not specified'))
                ->action('View Verification', route('admin.verification.show', $this->document->teacherProfile->verificationRequest))
                ->salutation('IQRAQUEST Admin System');
        }
    }

    public function toDatabase($notifiable): array
    {
        $teacher = $this->document->teacherProfile->user;
        $isForTeacher = $notifiable->id === $teacher->id;
        
        return [
            'type' => 'document_rejected',
            'title' => $isForTeacher ? 'Document Rejected' : 'Document Rejected',
            'message' => $isForTeacher 
                ? 'Your ' . str_replace('_', ' ', $this->document->type) . ' has been rejected. Please upload a new document.'
                : 'Document rejected for ' . $teacher->name,
            'document_id' => $this->document->id,
            'teacher_id' => $this->document->teacherProfile->user_id,
            'document_type' => $this->document->type,
            'rejection_reason' => $this->rejectionReason,
            'rejected_by' => $this->document->verified_by,
            'action_url' => $isForTeacher 
                ? route('teacher.documents')
                : route('admin.verification.show', $this->document->teacherProfile->verificationRequest),
            'icon' => 'x-circle',
            'color' => 'error',
        ];
    }
}

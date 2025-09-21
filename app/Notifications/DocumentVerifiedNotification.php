<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TeacherDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentVerifiedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private TeacherDocument $document
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
            $verifiedDate = $this->document->verified_at ? 
                $this->document->verified_at->format('F j, Y \a\t g:i A') : 
                now()->format('F j, Y \a\t g:i A');
            
            return (new MailMessage)
                ->subject('Document Verified Successfully')
                ->view('emails.document-verified', [
                    'notifiable' => $notifiable,
                    'document' => $this->document,
                    'documentType' => $documentType,
                    'verifiedDate' => $verifiedDate,
                ]);
        } else {
            // Admin notification - keep simple for now
            return (new MailMessage)
                ->subject('Document Verified - ' . $teacher->name)
                ->greeting('Admin Notification')
                ->line('A document has been verified for teacher verification.')
                ->line('**Teacher Details:**')
                ->line('• Name: ' . $teacher->name)
                ->line('• Email: ' . $teacher->email)
                ->line('**Document Details:**')
                ->line('• Type: ' . ucfirst(str_replace('_', ' ', $this->document->type)))
                ->line('• Name: ' . $this->document->name)
                ->line('• Verified by: ' . ($this->document->verifiedBy->name ?? 'System'))
                ->line('• Verified on: ' . $this->document->verified_at->format('M d, Y H:i'))
                ->action('View Verification', route('admin.verification.show', $this->document->teacherProfile->verificationRequest))
                ->salutation('IQRAQUEST Admin System');
        }
    }

    public function toDatabase($notifiable): array
    {
        $teacher = $this->document->teacherProfile->user;
        $isForTeacher = $notifiable->id === $teacher->id;
        
        return [
            'type' => 'document_verified',
            'title' => $isForTeacher ? 'Document Verified' : 'Document Verified',
            'message' => $isForTeacher 
                ? 'Your ' . str_replace('_', ' ', $this->document->type) . ' has been verified successfully.'
                : 'Document verified for ' . $teacher->name,
            'document_id' => $this->document->id,
            'teacher_id' => $this->document->teacherProfile->user_id,
            'document_type' => $this->document->type,
            'verified_by' => $this->document->verified_by,
            'verified_at' => $this->document->verified_at,
            'action_url' => $isForTeacher 
                ? route('teacher.documents')
                : route('admin.verification.show', $this->document->teacherProfile->verificationRequest),
            'icon' => 'check-circle',
            'color' => 'success',
        ];
    }
}

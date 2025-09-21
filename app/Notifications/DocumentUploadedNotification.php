<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TeacherDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentUploadedNotification extends Notification
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
            return (new MailMessage)
                ->subject('Document Uploaded Successfully')
                ->greeting('Hello ' . $teacher->name . ',')
                ->line('Your document has been uploaded successfully and is now under review.')
                ->line('**Document Details:**')
                ->line('• Type: ' . ucfirst(str_replace('_', ' ', $this->document->type)))
                ->line('• Name: ' . $this->document->name)
                ->line('• Status: Pending Review')
                ->line('**What happens next:**')
                ->line('• Our team will review your document within 24-48 hours')
                ->line('• You will be notified once the review is complete')
                ->line('• You can upload additional documents if needed')
                ->action('View Documents', route('teacher.documents'))
                ->line('Thank you for providing the required documentation.')
                ->salutation('Best regards, The IqraQuest Team');
        } else {
            // Admin notification
            return (new MailMessage)
                ->subject('New Document Uploaded - ' . $teacher->name)
                ->greeting('Admin Notification')
                ->line('A new document has been uploaded for teacher verification.')
                ->line('**Teacher Details:**')
                ->line('• Name: ' . $teacher->name)
                ->line('• Email: ' . $teacher->email)
                ->line('**Document Details:**')
                ->line('• Type: ' . ucfirst(str_replace('_', ' ', $this->document->type)))
                ->line('• Name: ' . $this->document->name)
                ->line('• Uploaded: ' . $this->document->created_at->format('M d, Y H:i'))
                ->action('Review Document', route('admin.verification.show', $this->document->teacherProfile->verificationRequest))
                ->salutation('IqraQuest Admin System');
        }
    }

    public function toDatabase($notifiable): array
    {
        $teacher = $this->document->teacherProfile->user;
        $isForTeacher = $notifiable->id === $teacher->id;
        
        return [
            'type' => 'document_uploaded',
            'title' => $isForTeacher ? 'Document Uploaded' : 'New Document Uploaded',
            'message' => $isForTeacher 
                ? 'Your ' . str_replace('_', ' ', $this->document->type) . ' has been uploaded and is under review.'
                : 'New ' . str_replace('_', ' ', $this->document->type) . ' uploaded by ' . $teacher->name,
            'document_id' => $this->document->id,
            'teacher_id' => $this->document->teacherProfile->user_id,
            'document_type' => $this->document->type,
            'action_url' => $isForTeacher 
                ? route('teacher.documents')
                : route('admin.verification.show', $this->document->teacherProfile->verificationRequest),
            'icon' => 'upload',
            'color' => 'info',
        ];
    }
}

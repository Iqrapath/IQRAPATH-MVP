<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationCallStartedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private VerificationRequest $verificationRequest
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $teacher = $this->verificationRequest->teacherProfile->user;
        $isForTeacher = $notifiable->id === $teacher->id;
        
        if ($isForTeacher) {
            $currentTime = now()->format('F j, Y \a\t g:i A');
            
            return (new MailMessage)
                ->subject('Verification Call Started - Join Now!')
                ->view('emails.verification-call-started', [
                    'notifiable' => $notifiable,
                    'verificationRequest' => $this->verificationRequest,
                    'currentTime' => $currentTime,
                ]);
        } else {
            // Admin notification - keep simple for now
            return (new MailMessage)
                ->subject('Video Verification Call Started - ' . $teacher->name)
                ->greeting('Admin Notification')
                ->line('The video verification call for ' . $teacher->name . ' has started.')
                ->line('**Teacher Details:**')
                ->line('• Name: ' . $teacher->name)
                ->line('• Email: ' . $teacher->email)
                ->line('• Platform: ' . ucfirst($this->verificationRequest->video_platform))
                ->line('• Meeting Link: ' . ($this->verificationRequest->meeting_link ?? 'N/A'))
                ->action('View Verification', route('admin.verification.show', $this->verificationRequest))
                ->salutation('IQRAQUEST Admin System');
        }
    }

    public function toDatabase($notifiable): array
    {
        $teacher = $this->verificationRequest->teacherProfile->user;
        $isForTeacher = $notifiable->id === $teacher->id;
        
        return [
            'type' => 'verification_call_started',
            'title' => $isForTeacher ? 'Video Call Started' : 'Verification Call Started',
            'message' => $isForTeacher 
                ? 'Your video verification call has started. Please join using the meeting link.'
                : 'Video verification call for ' . $teacher->name . ' has started.',
            'verification_request_id' => $this->verificationRequest->id,
            'teacher_id' => $this->verificationRequest->teacherProfile->user_id,
            'action_url' => $isForTeacher 
                ? ($this->verificationRequest->meeting_link ?? route('teacher.dashboard'))
                : route('admin.verification.show', $this->verificationRequest),
            'icon' => 'video',
            'color' => 'info',
        ];
    }
}

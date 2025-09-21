<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationCallCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private VerificationRequest $verificationRequest,
        private string $result,
        private ?string $notes = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $teacher = $this->verificationRequest->teacherProfile->user;
        $isForTeacher = $notifiable->id === $teacher->id;
        $passed = $this->result === 'passed';
        
        if ($isForTeacher) {
            $completedTime = now()->format('F j, Y \a\t g:i A');
            
            if ($passed) {
                return (new MailMessage)
                    ->subject('Video Verification Completed - Congratulations!')
                    ->view('emails.verification-call-completed-passed', [
                        'notifiable' => $notifiable,
                        'verificationRequest' => $this->verificationRequest,
                        'completedTime' => $completedTime,
                        'notes' => $this->notes,
                    ]);
            } else {
                return (new MailMessage)
                    ->subject('Video Verification Update - Action Required')
                    ->view('emails.verification-call-completed-failed', [
                        'notifiable' => $notifiable,
                        'verificationRequest' => $this->verificationRequest,
                        'completedTime' => $completedTime,
                        'notes' => $this->notes,
                    ]);
            }
        } else {
            // Admin notification - keep simple for now
            return (new MailMessage)
                ->subject('Video Verification Completed - ' . $teacher->name . ' (' . ucfirst($this->result) . ')')
                ->greeting('Admin Notification')
                ->line('The video verification call for ' . $teacher->name . ' has been completed.')
                ->line('**Result:** ' . ucfirst($this->result))
                ->line('**Teacher Details:**')
                ->line('• Name: ' . $teacher->name)
                ->line('• Email: ' . $teacher->email)
                ->line('• Notes: ' . ($this->notes ?? 'No additional notes'))
                ->action('View Verification', route('admin.verification.show', $this->verificationRequest))
                ->salutation('IQRAQUEST Admin System');
        }
    }

    public function toDatabase($notifiable): array
    {
        $teacher = $this->verificationRequest->teacherProfile->user;
        $isForTeacher = $notifiable->id === $teacher->id;
        $passed = $this->result === 'passed';
        
        return [
            'type' => 'verification_call_completed',
            'title' => $isForTeacher ? 'Video Call Completed' : 'Verification Call Completed',
            'message' => $isForTeacher 
                ? ($passed 
                    ? 'Your video verification call has been completed successfully. You passed!'
                    : 'Your video verification call has been completed. Please review the feedback and reschedule if needed.')
                : 'Video verification call for ' . $teacher->name . ' has been completed with result: ' . ucfirst($this->result),
            'verification_request_id' => $this->verificationRequest->id,
            'teacher_id' => $this->verificationRequest->teacherProfile->user_id,
            'result' => $this->result,
            'notes' => $this->notes,
            'action_url' => $isForTeacher 
                ? route('teacher.profile.edit')
                : route('admin.verification.show', $this->verificationRequest),
            'icon' => $passed ? 'check-circle' : 'x-circle',
            'color' => $passed ? 'success' : 'warning',
        ];
    }
}

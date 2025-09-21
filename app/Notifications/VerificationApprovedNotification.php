<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationApprovedNotification extends Notification
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
        $approvedDate = $this->verificationRequest->approved_at ? 
            $this->verificationRequest->approved_at->format('F j, Y \a\t g:i A') : 
            now()->format('F j, Y \a\t g:i A');
        
        return (new MailMessage)
            ->subject('🎉 Teacher Verification Approved - Welcome to IQRAQUEST!')
            ->view('emails.verification-approved', [
                'notifiable' => $notifiable,
                'verificationRequest' => $this->verificationRequest,
                'approvedDate' => $approvedDate,
            ]);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'verification_approved',
            'title' => 'Teacher Verification Approved',
            'message' => 'Congratulations! Your teacher verification has been approved. You can now start teaching on IqraQuest.',
            'verification_request_id' => $this->verificationRequest->id,
            'teacher_id' => $this->verificationRequest->teacherProfile->user_id,
            'action_url' => route('teacher.dashboard'),
            'icon' => 'check-circle',
            'color' => 'success',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private VerificationRequest $verificationRequest,
        private string $rejectionReason
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $teacher = $this->verificationRequest->teacherProfile->user;
        $reviewDate = $this->verificationRequest->updated_at ? 
            $this->verificationRequest->updated_at->format('F j, Y \a\t g:i A') : 
            now()->format('F j, Y \a\t g:i A');
        
        return (new MailMessage)
            ->subject('Verification Application Update - Action Required')
            ->view('emails.verification-rejected', [
                'notifiable' => $notifiable,
                'verificationRequest' => $this->verificationRequest,
                'rejectionReason' => $this->rejectionReason,
                'reviewDate' => $reviewDate,
            ]);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'verification_rejected',
            'title' => 'Teacher Verification Rejected',
            'message' => 'Your teacher verification has been rejected. Please review the feedback and resubmit your application.',
            'verification_request_id' => $this->verificationRequest->id,
            'teacher_id' => $this->verificationRequest->teacherProfile->user_id,
            'rejection_reason' => $this->rejectionReason,
            'action_url' => route('onboarding.teacher'),
            'icon' => 'x-circle',
            'color' => 'error',
        ];
    }
}

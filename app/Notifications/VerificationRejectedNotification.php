<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class VerificationRejectedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected VerificationRequest $verificationRequest;
    protected string $rejectionReason;

    /**
     * Create a new notification instance.
     */
    public function __construct(VerificationRequest $verificationRequest, string $rejectionReason)
    {
        $this->verificationRequest = $verificationRequest;
        $this->rejectionReason = $rejectionReason;
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
        $reviewDate = now()->format('l, F j, Y');
        
        return (new MailMessage)
            ->subject('📋 Verification Application Update - Action Required')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('<p>Thank you for submitting your teacher verification application to IqraPath.</p>')
            ->line('<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #856404; margin-top: 0;">📋 Application Review Results</h3>')
            ->line('<p><strong>Review Date:</strong> ' . $reviewDate . '</p>')
            ->line('<p><strong>Status:</strong> <span style="color: #f39c12; font-weight: bold;">🔄 Requires Additional Information</span></p>')
            ->line('<p><strong>Application ID:</strong> #' . $this->verificationRequest->id . '</p>')
            ->line('</div>')
            ->line('<div style="background-color: #e8f4f8; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0;">')
            ->line('<h4 style="color: #0c5460; margin-top: 0;">💡 What We Found:</h4>')
            ->line('<p>' . $this->rejectionReason . '</p>')
            ->line('</div>')
            ->line('<div style="background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #155724; margin-top: 0;">🎯 Good News - This is Fixable!</h3>')
            ->line('<p style="color: #28a745; font-weight: bold;">Don\'t worry!</p> <p>Most applications need some adjustments. This is completely normal and doesn\'t affect your ability to become a teacher with us.</p>')
            ->line('</div>')
            ->line('<div style="background-color: #e3f2fd; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #1976d2; margin-top: 0;">🚀 Your Action Plan:</h3>')
            ->line('<div style="margin: 15px 0;">')
            ->line('<h4 style="color: #1976d2;">📋 Step 1: Review & Fix</h4>')
            ->line('<ul style="margin: 0; padding-left: 20px;">')
            ->line('<li>Carefully read the feedback above</li>')
            ->line('<li>Gather any missing documents or information</li>')
            ->line('<li>Ensure all documents are clear and readable</li>')
            ->line('</ul>')
            ->line('</div>')
            ->line('<div style="margin: 15px 0;">')
            ->line('<h4 style="color: #1976d2;">📞 Step 2: Get Help (Optional)</h4>')
            ->line('<ul style="margin: 0; padding-left: 20px;">')
            ->line('<li>Contact our support team for clarification</li>')
            ->line('<li>Schedule a call if you need guidance</li>')
            ->line('<li>Join our teacher preparation webinar</li>')
            ->line('</ul>')
            ->line('</div>')
            ->line('<div style="margin: 15px 0;">')
            ->line('<h4 style="color: #1976d2;">✅ Step 3: Resubmit When Ready</h4>')
            ->line('<ul style="margin: 0; padding-left: 20px;">')
            ->line('<li>Update your application with improvements</li>')
            ->line('<li>Submit for review again</li>')
            ->line('<li>Most resubmissions are approved within 24 hours!</li>')
            ->line('</ul>')
            ->line('</div>')
            ->line('</div>')
            ->line('<div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #495057; margin-top: 0;">📊 Success Stats</h3>')
            ->line('<ul style="margin: 0; padding-left: 20px;">')
            ->line('<li><strong>85%</strong> of resubmitted applications get approved</li>')
            ->line('<li><strong>Average time</strong> to approval after resubmission: 1-2 days</li>')
            ->line('<li><strong>Support response time:</strong> Under 4 hours</li>')
            ->line('</ul>')
            ->line('</div>')
            ->line('<div style="background-color: #e8f5e8; border: 1px solid #c3e6cb; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #155724; margin-top: 0;">💚 We\'re Here to Help You Succeed</h3>')
            ->line('<p>Our goal is to help you become a successful teacher on IqraPath. Every piece of feedback is designed to help you meet our quality standards and serve students better.</p>')
            ->line('<p style="font-weight: bold; color: #28a745;">Remember: This is just a temporary step in your teaching journey!</p>')
            ->line('</div>')
            ->action('📝 Update My Application', config('app.url') . '/teacher/verification')
            ->line('<hr style="margin: 30px 0; border: none; border-top: 1px solid #dee2e6;">')
            ->line('<p style="color: #6c757d;"><strong>Need assistance?</strong> Our teacher support team is standing by to help you succeed.</p>')
            ->line('<p style="color: #6c757d;"><strong>Email:</strong> support@iqrapath.com | <strong>Response time:</strong> Under 4 hours</p>')
            ->line('<p style="font-size: 16px; color: #28a745; font-weight: bold;">We believe in your potential and look forward to welcoming you to our teaching community soon!</p>')
            ->salutation('<div style="margin-top: 30px; color: #495057;">Here to support your success,<br><strong>The IqraPath Verification Team</strong></div>');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Verification Update',
            'message' => 'Your teacher verification application has been reviewed.',
            'rejection_reason' => $this->rejectionReason,
            'action_text' => null,
            'action_url' => null,
            'verification_request_id' => $this->verificationRequest->id,
            'rejected_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}

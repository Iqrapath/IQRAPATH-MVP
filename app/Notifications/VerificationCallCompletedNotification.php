<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class VerificationCallCompletedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected VerificationRequest $verificationRequest;
    protected string $result;
    protected ?string $notes;

    /**
     * Create a new notification instance.
     */
    public function __construct(VerificationRequest $verificationRequest, string $result, ?string $notes = null)
    {
        $this->verificationRequest = $verificationRequest;
        $this->result = $result;
        $this->notes = $notes;
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
        $isPassed = $this->result === 'passed';
        $completedTime = now()->format('l, F j, Y \a\t g:i A T');
        
        if ($isPassed) {
            return $this->buildPassedNotification($notifiable, $completedTime);
        } else {
            return $this->buildFailedNotification($notifiable, $completedTime);
        }
    }

    /**
     * Build notification for passed verification call.
     */
    private function buildPassedNotification(object $notifiable, string $completedTime): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('🎉 Verification Call Passed! Welcome to IqraPath')
            ->greeting('Congratulations ' . $notifiable->name . '! 🎊')
            ->line('<h2 style="color: #28a745; margin: 20px 0 10px 0;">🎯 Excellent news! You\'ve successfully passed your verification call!</h2>')
            ->line('<div style="background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #155724; margin-top: 0;">✅ Verification Results</h3>')
            ->line('<p><strong>Status:</strong> <span style="color: #28a745; font-weight: bold;">🟢 PASSED</span></p>')
            ->line('<p><strong>Completed:</strong> ' . $completedTime . '</p>')
            ->line('<p><strong>Duration:</strong> Video verification phase complete</p>')
            ->line('</div>');

        if ($this->notes) {
            $mail->line('<div style="background-color: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0;">')
                 ->line('<p><strong>Feedback from our team:</strong> ' . $this->notes . '</p>')
                 ->line('</div>');
        }

        return $mail->line('<div style="background-color: #e3f2fd; border-radius: 8px; padding: 20px; margin: 20px 0;">')
                   ->line('<h3 style="color: #1976d2; margin-top: 0;">🚀 What Happens Next?</h3>')
                   ->line('<ol style="margin: 0; padding-left: 20px;">')
                   ->line('<li><strong>Document review</strong> 📄 (24-48 hours)</li>')
                   ->line('<li><strong>Final approval notification</strong> 📧</li>')
                   ->line('<li><strong>Access to teacher dashboard</strong> 🎓</li>')
                   ->line('<li><strong>Start creating courses and teaching!</strong> 👨‍🏫</li>')
                   ->line('</ol>')
                   ->line('</div>')
                   ->line('<div style="background-color: #fff3cd; border-radius: 8px; padding: 20px; margin: 20px 0;">')
                   ->line('<h3 style="color: #856404; margin-top: 0;">🌟 You\'re Almost There!</h3>')
                   ->line('<p>Our document verification team will now review your submitted documents. This typically takes 24-48 hours during business days.</p>')
                   ->line('<h4 style="color: #856404;">Pro tip: Use this time to:</h4>')
                   ->line('<ul style="margin: 0; padding-left: 20px;">')
                   ->line('<li>Plan your first course outline</li>')
                   ->line('<li>Prepare your teaching materials</li>')
                   ->line('<li>Think about your teaching schedule</li>')
                   ->line('<li>Get excited about inspiring students!</li>')
                   ->line('</ul>')
                   ->line('</div>')
                   ->line('<p style="font-size: 18px; font-weight: bold; color: #e91e63; text-align: center;">🎊 Welcome to the IqraPath teaching family! 🎊</p>')
                   ->salutation('<div style="margin-top: 30px; color: #495057;">Warm regards,<br><strong>The IqraPath Verification Team</strong></div>');
    }

    /**
     * Build notification for failed verification call.
     */
    private function buildFailedNotification(object $notifiable, string $completedTime): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('📋 Verification Call Results - Next Steps Available')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('<p>Thank you for completing your verification call with our team.</p>')
            ->line('<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #856404; margin-top: 0;">📋 Verification Results</h3>')
            ->line('<p><strong>Status:</strong> <span style="color: #f39c12; font-weight: bold;">🔄 Needs Improvement</span></p>')
            ->line('<p><strong>Completed:</strong> ' . $completedTime . '</p>')
            ->line('<p><strong>Review Status:</strong> Additional requirements identified</p>')
            ->line('</div>');

        if ($this->notes) {
            $mail->line('<div style="background-color: #e8f4f8; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0;">')
                 ->line('<h4 style="color: #0c5460; margin-top: 0;">💡 Specific Feedback:</h4>')
                 ->line('<p>' . $this->notes . '</p>')
                 ->line('</div>');
        }

        return $mail->line('<div style="background-color: #e3f2fd; border-radius: 8px; padding: 20px; margin: 20px 0;">')
                   ->line('<h3 style="color: #1976d2; margin-top: 0;">🎯 Your Next Steps:</h3>')
                   ->line('<p style="color: #28a745; font-weight: bold;">Don\'t worry! This is a common part of the verification process.</p>')
                   ->line('<div style="margin: 15px 0;">')
                   ->line('<h4 style="color: #1976d2;">Option 1: 📞 Schedule Another Call</h4>')
                   ->line('<ul style="margin: 0; padding-left: 20px;">')
                   ->line('<li>Address the feedback points above</li>')
                   ->line('<li>Prepare any additional documents if needed</li>')
                   ->line('<li>Book a new slot when you\'re ready</li>')
                   ->line('</ul>')
                   ->line('</div>')
                   ->line('<div style="margin: 15px 0;">')
                   ->line('<h4 style="color: #1976d2;">Option 2: 💬 Contact Support</h4>')
                   ->line('<ul style="margin: 0; padding-left: 20px;">')
                   ->line('<li>Get clarification on specific requirements</li>')
                   ->line('<li>Ask questions about the feedback</li>')
                   ->line('<li>Receive guidance for your next attempt</li>')
                   ->line('</ul>')
                   ->line('</div>')
                   ->line('</div>')
                   ->line('<div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0;">')
                   ->line('<h3 style="color: #495057; margin-top: 0;">🌟 Remember:</h3>')
                   ->line('<ul style="margin: 0; padding-left: 20px;">')
                   ->line('<li>Many successful teachers needed multiple attempts</li>')
                   ->line('<li>Each call helps us ensure quality education</li>')
                   ->line('<li>We\'re here to help you succeed</li>')
                   ->line('<li>Your teaching goals are still achievable!</li>')
                   ->line('</ul>')
                   ->line('</div>')
                   ->line('<p style="font-size: 16px; color: #28a745; font-weight: bold; text-align: center;">Ready to try again? Take time to address the feedback, then schedule your next verification call.</p>')
                   ->salutation('<div style="margin-top: 30px; color: #495057;">Keep going - you\'ve got this!<br><strong>The IqraPath Verification Team</strong></div>');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $isPassed = $this->result === 'passed';
        
        return [
            'title' => $isPassed ? 'Verification Call Passed' : 'Verification Call Failed',
            'message' => $isPassed 
                ? 'Your video verification has been completed successfully.' 
                : 'Your video verification did not meet our requirements.',
            'result' => $this->result,
            'notes' => $this->notes,
            'completed_at' => now()->toIso8601String(),
            'action_text' => null,
            'action_url' => null,
            'verification_request_id' => $this->verificationRequest->id,
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

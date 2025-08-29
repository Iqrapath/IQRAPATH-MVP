<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class VerificationApprovedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected VerificationRequest $verificationRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(VerificationRequest $verificationRequest)
    {
        $this->verificationRequest = $verificationRequest;
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
        $approvedDate = now()->format('l, F j, Y');
        
        return (new MailMessage)
            ->subject('🎊 APPROVED! Welcome to IqraPath Teaching Community!')
            ->greeting('🎉 Congratulations ' . $notifiable->name . '! 🎉')
            ->line('<h2 style="color: #28a745; margin: 20px 0 10px 0;">🏆 Your teacher verification has been APPROVED!</h2>')
            ->line('<p>After careful review of your documents and video verification, we\'re thrilled to officially welcome you to the IqraPath teaching community!</p>')
            ->line('<div style="background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #155724; margin-top: 0;">✅ Verification Complete</h3>')
            ->line('<p><strong>Status:</strong> <span style="color: #28a745; font-weight: bold;">🟢 FULLY APPROVED</span></p>')
            ->line('<p><strong>Approved Date:</strong> ' . $approvedDate . '</p>')
            ->line('<p><strong>Profile Status:</strong> Active Teacher</p>')
            ->line('</div>')
            ->line('<div style="background-color: #e3f2fd; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #1976d2; margin-top: 0;">🚀 Your Teaching Journey Starts Now!</h3>')
            ->line('<h4 style="color: #1976d2;">🎯 Immediate Next Steps (15 minutes):</h4>')
            ->line('<ol style="margin: 0; padding-left: 20px;">')
            ->line('<li><strong>Complete Your Profile</strong> - Add your bio and teaching specialties</li>')
            ->line('<li><strong>Setup Payment Info</strong> - Configure how you\'ll receive earnings</li>')
            ->line('<li><strong>Set Your Availability</strong> - Let students know when you\'re free</li>')
            ->line('</ol>')
            ->line('<h4 style="color: #1976d2;">📚 Start Teaching (This Week):</h4>')
            ->line('<ol start="4" style="margin: 0; padding-left: 20px;">')
            ->line('<li><strong>Create Your First Course</strong> - Share your expertise</li>')
            ->line('<li><strong>Offer Tutoring Sessions</strong> - One-on-one teaching</li>')
            ->line('<li><strong>Join Our Teacher Community</strong> - Connect with fellow educators</li>')
            ->line('</ol>')
            ->line('</div>')
            ->line('<div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #495057; margin-top: 0;">💰 Earning Potential</h3>')
            ->line('<ul style="margin: 0; padding-left: 20px;">')
            ->line('<li><strong>Hourly Tutoring:</strong> $15-50+ per hour</li>')
            ->line('<li><strong>Course Sales:</strong> Passive income from course enrollments</li>')
            ->line('<li><strong>Group Sessions:</strong> Teach multiple students simultaneously</li>')
            ->line('<li><strong>Flexible Schedule:</strong> Teach when it works for you</li>')
            ->line('</ul>')
            ->line('</div>')
            ->line('<div style="background-color: #fff3cd; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #856404; margin-top: 0;">🌟 Teacher Benefits</h3>')
            ->line('<ul style="margin: 0; padding-left: 20px;">')
            ->line('<li>✅ Access to 10,000+ active students</li>')
            ->line('<li>✅ Professional teacher dashboard</li>')
            ->line('<li>✅ Automated payment processing</li>')
            ->line('<li>✅ Marketing support for your courses</li>')
            ->line('<li>✅ 24/7 technical support</li>')
            ->line('<li>✅ Teacher community and resources</li>')
            ->line('</ul>')
            ->line('</div>')
            ->line('<div style="background-color: #e8f5e8; border: 1px solid #c3e6cb; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;">')
            ->line('<h4 style="color: #155724; margin-top: 0;">🎓 Fun Fact</h4>')
            ->line('<p style="margin: 0;">You\'re joining a community of <strong>500+ verified teachers</strong> who are making a real impact on students\' lives worldwide!</p>')
            ->line('</div>')
            ->action('🚀 Start Your Teaching Journey', config('app.url') . '/teacher/dashboard')
            ->line('<hr style="margin: 30px 0; border: none; border-top: 1px solid #dee2e6;">')
            ->line('<p style="color: #6c757d;"><strong>Questions?</strong> Our teacher success team is here to help you succeed!</p>')
            ->line('<p style="font-size: 18px; color: #e91e63; font-weight: bold; text-align: center;">🌟 Welcome to IqraPath - Where Teaching Transforms Lives! 🌟</p>')
            ->salutation('<div style="margin-top: 30px; color: #495057;">Excited to see you teach!<br><strong>The IqraPath Team 💚</strong></div>');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => '🎉 Verification Approved!',
            'message' => 'Your teacher verification has been approved. Welcome to IqraPath!',
            'action_text' => null,
            'action_url' => null,
            'verification_request_id' => $this->verificationRequest->id,
            'approved_at' => now()->toIso8601String(),
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

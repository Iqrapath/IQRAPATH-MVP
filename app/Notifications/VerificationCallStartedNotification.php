<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class VerificationCallStartedNotification extends Notification implements ShouldQueue, ShouldBroadcast
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
        $currentTime = now()->format('l, F j, Y \a\t g:i A T');
        
        $mail = (new MailMessage)
            ->subject('🔴 LIVE: Your Verification Call Has Started - IqraPath')
            ->greeting('Hello ' . $notifiable->name . '! 👋')
            ->line('<h2 style="color: #dc3545; margin: 20px 0 10px 0;">🔴 Your verification call is now LIVE!</h2>')
            ->line('<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #856404; margin-top: 0;">⚡ Immediate Action Required</h3>')
            ->line('<p><strong>Current Time:</strong> ' . $currentTime . '</p>')
            ->line('<p><strong>Status:</strong> 🟢 Active & Waiting for You</p>')
            ->line('<p><strong>Our Verification Team:</strong> Ready and waiting</p>')
            ->line('</div>');
            
        if ($this->verificationRequest->meeting_link) {
            $mail->line('<div style="text-align: center; margin: 20px 0;">')
                 ->line('<p style="font-size: 16px; margin-bottom: 15px;"><strong>Meeting Room:</strong> <a href="' . $this->verificationRequest->meeting_link . '" style="color: #007bff; text-decoration: none;">Click here to join NOW</a> 🚀</p>')
                 ->line('</div>');
        }
        
        $mail->line('<div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #495057; margin-top: 0;">📋 Quick Checklist (30 seconds):</h3>')
            ->line('<ul style="margin: 0; padding-left: 20px;">')
            ->line('<li>🎥 Camera working and good lighting?</li>')
            ->line('<li>🎤 Microphone clear and no background noise?</li>')
            ->line('<li>📄 ID and teaching documents within reach?</li>')
            ->line('<li>📶 Stable internet connection?</li>')
            ->line('</ul>')
            ->line('</div>')
            ->line('<div style="background-color: #e3f2fd; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #1976d2; margin-top: 0;">💡 Pro Tips:</h3>')
            ->line('<ul style="margin: 0; padding-left: 20px;">')
            ->line('<li><strong>Join immediately</strong> - Don\'t keep the team waiting</li>')
            ->line('<li><strong>Speak clearly</strong> and maintain eye contact</li>')
            ->line('<li><strong>Have documents ready</strong> for quick verification</li>')
            ->line('<li><strong>Stay calm and confident</strong> - you\'ve got this!</li>')
            ->line('</ul>')
            ->line('</div>')
            ->line('<p style="font-size: 18px; font-weight: bold; color: #dc3545; text-align: center;">⏰ Time is of the essence - Our team is ready for you now!</p>');
            
        if ($this->verificationRequest->meeting_link) {
            $mail->action('🔴 JOIN LIVE CALL NOW', $this->verificationRequest->meeting_link);
        }
        
        return $mail->line('<hr style="margin: 30px 0; border: none; border-top: 1px solid #dee2e6;">')
               ->line('<p style="color: #6c757d;"><strong>Technical issues?</strong> If you can\'t join, contact support immediately.</p>')
               ->line('<p style="font-size: 16px; color: #28a745; font-weight: bold;">Your teaching journey with IqraPath starts here! 🌟</p>')
               ->salutation('<div style="margin-top: 30px; color: #495057;">Best of luck!<br><strong>The IqraPath Verification Team</strong></div>');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Verification Call Started',
            'message' => 'Your verification call is now live. Please join immediately.',
            'action_text' => $this->verificationRequest->meeting_link ? 'Join Call Now' : null,
            'action_url' => $this->verificationRequest->meeting_link ?? null,
            'verification_request_id' => $this->verificationRequest->id,
            'started_at' => now()->toIso8601String(),
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

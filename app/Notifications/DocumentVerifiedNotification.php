<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class DocumentVerifiedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected Document $document;

    /**
     * Create a new notification instance.
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
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
        $documentType = $this->getDocumentTypeLabel($this->document->type);
        $verifiedDate = $this->document->verified_at->format('l, F j, Y \a\t g:i A');
        
        return (new MailMessage)
            ->subject('✅ Document Approved! Great Progress on Your Verification')
            ->greeting('Excellent news, ' . $notifiable->name . '! 🎉')
            ->line('<h2 style="color: #28a745; margin: 20px 0 10px 0;">🎯 Your document has been successfully verified!</h2>')
            ->line('<div style="background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #155724; margin-top: 0;">✅ Document Verification Complete</h3>')
            ->line('<p><strong>Document Name:</strong> ' . $this->document->name . '</p>')
            ->line('<p><strong>Document Type:</strong> ' . $documentType . ' 📋</p>')
            ->line('<p><strong>Verification Status:</strong> <span style="color: #28a745; font-weight: bold;">🟢 APPROVED</span></p>')
            ->line('<p><strong>Verified Date:</strong> ' . $verifiedDate . '</p>')
            ->line('<p><strong>Reviewer:</strong> IqraPath Verification Team</p>')
            ->line('</div>')
            ->line('<div style="background-color: #e8f5e8; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #155724; margin-top: 0;">🎊 Why This Matters:</h3>')
            ->line('<ul style="margin: 0; padding-left: 20px;">')
            ->line('<li>✅ <strong>Quality Confirmed</strong> - Your document meets our high standards</li>')
            ->line('<li>✅ <strong>Trust Building</strong> - Students can trust your credentials</li>')
            ->line('<li>✅ <strong>Progress Made</strong> - You\'re moving closer to full verification</li>')
            ->line('<li>✅ <strong>Professional Standing</strong> - Part of our verified teacher community</li>')
            ->line('</ul>')
            ->line('</div>')
            ->line('<div style="background-color: #e3f2fd; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #1976d2; margin-top: 0;">🚀 Your Verification Journey:</h3>')
            ->line('<ol style="margin: 0; padding-left: 20px;">')
            ->line('<li><strong>Document submission</strong> 📄 ✅ <span style="color: #28a745;">DONE</span></li>')
            ->line('<li><strong>Document review</strong> 📋 ✅ <span style="color: #28a745;">APPROVED</span></li>')
            ->line('<li><strong>Video verification</strong> 🎥 ⏳ <em>In progress</em></li>')
            ->line('<li><strong>Final approval</strong> 🏆 ⏳ <em>Pending</em></li>')
            ->line('</ol>')
            ->line('</div>')
            ->line('<div style="background-color: #fff3cd; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #856404; margin-top: 0;">🎯 What\'s Next?</h3>')
            ->line('<p style="font-weight: bold; color: #856404;">Almost there! Here\'s what happens next:</p>')
            ->line('<h4 style="color: #856404;">📹 Video Verification (if not completed)</h4>')
            ->line('<ul style="margin: 0; padding-left: 20px;">')
            ->line('<li>Schedule your verification call</li>')
            ->line('<li>15-20 minute friendly interview</li>')
            ->line('<li>Verify your identity and qualifications</li>')
            ->line('</ul>')
            ->line('<h4 style="color: #856404;">📋 Remaining Documents (if any)</h4>')
            ->line('<ul style="margin: 0; padding-left: 20px;">')
            ->line('<li>Upload any additional required documents</li>')
            ->line('<li>Each gets reviewed within 24-48 hours</li>')
            ->line('</ul>')
            ->line('<h4 style="color: #856404;">🏁 Final Review</h4>')
            ->line('<ul style="margin: 0; padding-left: 20px;">')
            ->line('<li>All components reviewed together</li>')
            ->line('<li>Final approval notification sent</li>')
            ->line('<li>Welcome to the teaching community!</li>')
            ->line('</ul>')
            ->line('</div>')
            ->line('<div style="background-color: #f8f9fa; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0;">')
            ->line('<h4 style="color: #495057; margin-top: 0;">📊 Your Progress</h4>')
            ->line('<p style="margin: 0;">Based on your current status, you\'re <strong style="color: #007bff;">~75% complete</strong> with the verification process!</p>')
            ->line('</div>')
            ->line('<div style="background-color: #e8f4f8; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0;">')
            ->line('<h4 style="color: #0c5460; margin-top: 0;">💡 Pro Tip</h4>')
            ->line('<p style="margin: 0;">While waiting for final approval, you can start planning your first course or tutoring session. Our most successful teachers begin preparing their teaching materials during verification!</p>')
            ->line('</div>')
            ->action('📄 View Verification Status', config('app.url') . '/teacher/verification')
            ->line('<hr style="margin: 30px 0; border: none; border-top: 1px solid #dee2e6;">')
            ->line('<p style="color: #28a745; font-weight: bold;">Keep up the momentum! You\'re doing great and we\'re excited to have you join our teaching community.</p>')
            ->salutation('<div style="margin-top: 30px; color: #495057;">Celebrating your progress,<br><strong>The IqraPath Verification Team 🌟</strong></div>');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => '✅ Document Verified',
            'message' => 'Your document "' . $this->document->name . '" has been verified successfully.',
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'document_type' => $this->document->type,
            'document_type_label' => $this->getDocumentTypeLabel($this->document->type),
            'verified_at' => $this->document->verified_at->toIso8601String(),
            'action_text' => null,
            'action_url' => null,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Get document type label for display.
     */
    private function getDocumentTypeLabel(string $type): string
    {
        return match ($type) {
            'id_verification' => 'Identity Verification',
            'certificate' => 'Certificate/Qualification',
            'resume' => 'Resume/CV',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}

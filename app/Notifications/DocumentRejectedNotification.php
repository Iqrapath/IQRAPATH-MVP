<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class DocumentRejectedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected Document $document;
    protected string $rejectionReason;
    protected ?string $resubmissionInstructions;

    /**
     * Create a new notification instance.
     */
    public function __construct(Document $document, string $rejectionReason, ?string $resubmissionInstructions = null)
    {
        $this->document = $document;
        $this->rejectionReason = $rejectionReason;
        $this->resubmissionInstructions = $resubmissionInstructions;
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
        $reviewedDate = $this->document->verified_at->format('l, F j, Y \a\t g:i A');
        $remainingAttempts = $this->document->getRemainingResubmissions();
        $maxAttempts = $this->document->max_resubmissions;
        
        $mail = (new MailMessage)
            ->subject('📋 Document Review Complete - Resubmission Needed')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('<p>Thank you for submitting your document for verification. Our review is complete and we need you to make some improvements.</p>')
            ->line('<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin: 20px 0;">')
            ->line('<h3 style="color: #856404; margin-top: 0;">📋 Document Review Results</h3>')
            ->line('<p><strong>Document:</strong> ' . $this->document->name . '</p>')
            ->line('<p><strong>Type:</strong> ' . $documentType . ' 📄</p>')
            ->line('<p><strong>Review Date:</strong> ' . $reviewedDate . '</p>')
            ->line('<p><strong>Status:</strong> <span style="color: #f39c12; font-weight: bold;">🔄 Needs Improvement</span></p>')
            ->line('</div>')
            ->line('<div style="background-color: #e8f4f8; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0;">')
            ->line('<h4 style="color: #0c5460; margin-top: 0;">💡 What We Found:</h4>')
            ->line('<p>' . $this->rejectionReason . '</p>')
            ->line('</div>');
            
        if ($this->resubmissionInstructions) {
            $mail->line('## 📝 **Specific Instructions for Resubmission:**')
                 ->line($this->resubmissionInstructions)
                 ->line('');
        }
        
        $mail->line('## 🎯 **Easy Fix - Here\'s Your Action Plan:**')
            ->line('')
            ->line('**📋 Step 1: Review the Feedback**')
            ->line('• Read the specific issues mentioned above')
            ->line('• Check if you have a better version of this document')
            ->line('• Ensure you understand all requirements')
            ->line('')
            ->line('**🔧 Step 2: Fix the Issues**')
            ->line('• Address each point mentioned in the feedback')
            ->line('• Use high-quality scans or clear photos')
            ->line('• Make sure all text is readable and complete')
            ->line('• Verify file format requirements (usually PDF)')
            ->line('')
            ->line('**📤 Step 3: Resubmit the Document**')
            ->line('• Upload the improved version')
            ->line('• Double-check before submitting')
            ->line('• Most resubmissions are approved within 24 hours!')
            ->line('')
            ->line('## 📊 **Submission Status**')
            ->line('**Attempts Used:** ' . ($maxAttempts - $remainingAttempts) . ' of ' . $maxAttempts)
            ->line('**Remaining Attempts:** **' . $remainingAttempts . '** more chances')
            ->line('**Success Rate:** 95% of resubmissions get approved!')
            ->line('')
            ->line('## 💚 **Don\'t Worry - This is Normal!**')
            ->line('Document resubmissions are very common and nothing to worry about. Our review process helps ensure all teachers meet the same high standards that students expect.')
            ->line('')
            ->line('**Most common fixes:**')
            ->line('• Higher resolution scans')
            ->line('• Complete document pages')
            ->line('• Correct file formats')
            ->line('• Clear, readable text')
            ->line('')
            ->action('📤 Upload Improved Document', config('app.url') . '/teacher/verification/documents')
            ->line('')
            ->line('## 🆘 **Need Help?**')
            ->line('**Stuck on the requirements?** Our support team is here to help!')
            ->line('• **Email:** support@iqrapath.com')
            ->line('• **Response Time:** Under 4 hours')
            ->line('• **Live Chat:** Available on our website')
            ->line('')
            ->line('**We believe in your success!** Every piece of feedback helps you meet our quality standards and serve students better.');
        
        return $mail->salutation('Here to help you succeed,  
The IqraPath Document Review Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => '❌ Document Update',
            'message' => 'Your document "' . $this->document->name . '" needs to be resubmitted.',
            'document_id' => $this->document->id,
            'document_name' => $this->document->name,
            'document_type' => $this->document->type,
            'document_type_label' => $this->getDocumentTypeLabel($this->document->type),
            'rejection_reason' => $this->rejectionReason,
            'resubmission_instructions' => $this->resubmissionInstructions,
            'remaining_attempts' => $this->document->getRemainingResubmissions(),
            'max_attempts' => $this->document->max_resubmissions,
            'reviewed_at' => $this->document->verified_at->toIso8601String(),
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

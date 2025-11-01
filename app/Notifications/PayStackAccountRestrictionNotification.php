<?php

namespace App\Notifications;

use App\Models\PayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayStackAccountRestrictionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public PayoutRequest $payoutRequest,
        public string $errorMessage
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $paymentDetails = is_string($this->payoutRequest->payment_details) 
            ? json_decode($this->payoutRequest->payment_details, true) 
            : $this->payoutRequest->payment_details;

        return (new MailMessage)
            ->subject('⚠️ Urgent: Teacher Payout Requires Manual Processing - PayStack Restriction')
            ->view('emails.paystack-restriction', [
                'admin' => $notifiable,
                'payoutRequest' => $this->payoutRequest,
                'paymentDetails' => $paymentDetails,
                'errorMessage' => $this->errorMessage,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $paymentDetails = is_string($this->payoutRequest->payment_details) 
            ? json_decode($this->payoutRequest->payment_details, true) 
            : $this->payoutRequest->payment_details;

        return [
            'type' => 'paystack_restriction',
            'title' => '⚠️ Payout Requires Manual Processing',
            'message' => 'Teacher payout request #' . $this->payoutRequest->request_uuid . ' for ' . $this->payoutRequest->teacher->name . ' (₦' . number_format($this->payoutRequest->amount, 2) . ') could not be processed automatically. PayStack transfers are disabled. Please enable transfers in PayStack dashboard or process manually.',
            'icon' => 'alert-triangle',
            'color' => 'warning',
            'priority' => 'high',
            
            // Payout details
            'payout_request_id' => $this->payoutRequest->id,
            'request_uuid' => $this->payoutRequest->request_uuid,
            'teacher_id' => $this->payoutRequest->teacher_id,
            'teacher_name' => $this->payoutRequest->teacher->name,
            'teacher_email' => $this->payoutRequest->teacher->email,
            'amount' => $this->payoutRequest->amount,
            'currency' => $this->payoutRequest->currency,
            'formatted_amount' => '₦' . number_format($this->payoutRequest->amount, 2),
            'payment_method' => $this->payoutRequest->payment_method,
            'request_date' => $this->payoutRequest->request_date->format('Y-m-d'),
            
            // Bank details
            'bank_name' => $paymentDetails['bank_name'] ?? null,
            'account_number' => $paymentDetails['account_number'] ?? null,
            'account_name' => $paymentDetails['account_name'] ?? null,
            
            // Error information
            'error_message' => $this->errorMessage,
            'error_type' => 'paystack_account_restriction',
            'status' => 'requires_manual_processing',
            
            // Actions
            'action_url' => url('/admin/financial/payouts?id=' . $this->payoutRequest->id),
            'action_text' => 'View & Process Payout',
            'secondary_action_url' => 'https://dashboard.paystack.com/settings',
            'secondary_action_text' => 'PayStack Settings',
            
            // Metadata
            'created_at' => now()->toISOString(),
            'requires_action' => true,
            'is_urgent' => true,
        ];
    }
}

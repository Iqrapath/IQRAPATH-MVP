<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\PayoutRequest;

class PayoutProcessedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $status,
        public PayoutRequest $payoutRequest,
        public ?string $errorMessage = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $data = [
            'payout_request_id' => $this->payoutRequest->id,
            'teacher_id' => $this->payoutRequest->teacher_id,
            'teacher_name' => $this->payoutRequest->teacher->name,
            'amount' => $this->payoutRequest->amount,
            'payment_method' => $this->payoutRequest->payment_method,
            'status' => $this->status,
        ];

        if ($this->status === 'success') {
            $data['title'] = 'Payout Processed Successfully';
            $data['message'] = "Payout of ₦" . number_format($this->payoutRequest->amount, 2) . " to " . $this->payoutRequest->teacher->name . " has been initiated via " . $this->payoutRequest->payment_method;
            $data['level'] = 'success';
        } elseif ($this->status === 'failed') {
            $data['title'] = 'Payout Processing Failed';
            $data['message'] = "Automatic payout of ₦" . number_format($this->payoutRequest->amount, 2) . " to " . $this->payoutRequest->teacher->name . " failed: " . ($this->errorMessage ?? 'Unknown error');
            $data['level'] = 'warning';
            $data['error'] = $this->errorMessage;
        } else {
            $data['title'] = 'Payout Processing Error';
            $data['message'] = "Error processing payout of ₦" . number_format($this->payoutRequest->amount, 2) . " to " . $this->payoutRequest->teacher->name . ": " . ($this->errorMessage ?? 'Unknown error');
            $data['level'] = 'error';
            $data['error'] = $this->errorMessage;
        }

        return $data;
    }
}

<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentNotification extends Notification
{

    protected Transaction $transaction;
    protected string $title;
    protected string $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Transaction $transaction, string $title, string $message)
    {
        $this->transaction = $transaction;
        $this->title = $title;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->message)
            ->line('Transaction ID: ' . $this->transaction->transaction_id)
            ->line('Amount: ' . $this->formatAmount($this->transaction->amount, $this->transaction->currency))
            ->line('Status: ' . ucfirst($this->transaction->status))
            ->line('Date: ' . $this->transaction->created_at->format('F j, Y \a\t g:i A'))
            ->action('View Transaction', url('/transactions/' . $this->transaction->id))
            ->line('Thank you for using our platform!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'title' => $this->title,
            'message' => $this->message,
            'amount' => $this->transaction->amount,
            'currency' => $this->transaction->currency,
            'status' => $this->transaction->status,
            'transaction_date' => $this->transaction->created_at,
            'action_text' => 'View Transaction',
            'action_url' => '/transactions/' . $this->transaction->id,
        ];
    }
    
    /**
     * Format the amount with currency symbol.
     */
    protected function formatAmount(float $amount, string $currency): string
    {
        $symbol = '$'; // Default to USD
        
        if ($currency === 'EUR') {
            $symbol = '€';
        } elseif ($currency === 'GBP') {
            $symbol = '£';
        }
        
        return $symbol . number_format($amount, 2);
    }
}

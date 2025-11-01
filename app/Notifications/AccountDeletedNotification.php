<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Account Deleted - IQRAQUEST')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your account has been deleted due to the following reason:')
            ->line($this->reason)
            ->line('If you believe this is an error, please contact our support team.')
            ->action('Contact Support', url('/contact'))
            ->line('Thank you for using IQRAQUEST.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Account Deleted',
            'message' => 'Your account has been deleted. Reason: ' . $this->reason,
            'type' => 'account_deleted',
            'action_url' => '/contact',
            'action_text' => 'Contact Support',
        ];
    }
}
<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountSuspendedNotification extends Notification implements ShouldQueue
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
            ->subject('Account Suspended - IQRAPATH')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your account has been suspended due to the following reason:')
            ->line($this->reason)
            ->line('If you believe this is an error, please contact our support team.')
            ->action('Contact Support', url('/contact'))
            ->line('Thank you for using IQRAPATH.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Account Suspended',
            'message' => 'Your account has been suspended. Reason: ' . $this->reason,
            'type' => 'account_suspended',
            'action_url' => '/contact',
            'action_text' => 'Contact Support',
        ];
    }
}
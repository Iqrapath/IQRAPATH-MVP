<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountUnsuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private ?string $reason = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Account Restored - IQRAQUEST')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Great news! Your account has been restored and you can now access all features.')
            ->action('Access Your Account', url('/dashboard'))
            ->line('Thank you for your patience.');

        if ($this->reason) {
            $message->line('Reason: ' . $this->reason);
        }

        return $message;
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Account Restored',
            'message' => 'Your account has been restored and you can now access all features.',
            'type' => 'account_restored',
            'action_url' => '/dashboard',
            'action_text' => 'Access Dashboard',
        ];
    }
}
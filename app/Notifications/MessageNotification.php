<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Message $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Message $message)
    {
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
        $sender = $this->message->sender;
        
        return (new MailMessage)
            ->subject('New Message from ' . $sender->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have received a new message from ' . $sender->name . '.')
            ->line('Message: ' . $this->truncateContent($this->message->content))
            ->action('View Message', url('/messages/' . $sender->id))
            ->line('Please log in to your account to respond.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $sender = $this->message->sender;
        
        return [
            'message_id' => $this->message->id,
            'title' => 'New Message from ' . $sender->name,
            'message' => $this->truncateContent($this->message->content),
            'sender' => [
                'id' => $sender->id,
                'name' => $sender->name,
                'avatar' => $sender->avatar,
            ],
            'created_at' => $this->message->created_at,
            'action_text' => 'View Message',
            'action_url' => '/messages/' . $sender->id,
        ];
    }
    
    /**
     * Truncate the content if it's too long.
     */
    protected function truncateContent(string $content): string
    {
        if (strlen($content) > 100) {
            return substr($content, 0, 100) . '...';
        }
        
        return $content;
    }
}

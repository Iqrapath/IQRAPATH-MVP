<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class MessageNotification extends Notification implements ShouldQueue, ShouldBroadcast
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
        return ['database', 'mail', 'broadcast'];
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
            'sender_id' => $sender->id,
            'sender_name' => $sender->name,
            'content' => $this->truncateContent($this->message->content),
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
    
    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        $sender = $this->message->sender;
        
        return new BroadcastMessage([
            'message_id' => $this->message->id,
            'sender_id' => $sender->id,
            'sender_name' => $sender->name,
            'content' => $this->truncateContent($this->message->content),
            'created_at' => now()->toIso8601String(),
        ]);
    }
    
    /**
     * Truncate the message content for preview.
     *
     * @param string $content
     * @return string
     */
    protected function truncateContent(string $content): string
    {
        return strlen($content) > 100 ? substr($content, 0, 97) . '...' : $content;
    }
}

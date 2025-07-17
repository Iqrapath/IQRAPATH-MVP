<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $message;
    protected ?string $actionText;
    protected ?string $actionUrl;
    protected string $level;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $title, string $message, ?string $actionText = null, ?string $actionUrl = null, string $level = 'info')
    {
        $this->title = $title;
        $this->message = $message;
        $this->actionText = $actionText;
        $this->actionUrl = $actionUrl;
        $this->level = $level;
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
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->message);
            
        if ($this->actionText && $this->actionUrl) {
            $mail->action($this->actionText, url($this->actionUrl));
        }
        
        return $mail->line('Thank you for using our platform!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $data = [
            'title' => $this->title,
            'message' => $this->message,
            'level' => $this->level,
            'created_at' => now(),
        ];
        
        if ($this->actionText && $this->actionUrl) {
            $data['action_text'] = $this->actionText;
            $data['action_url'] = $this->actionUrl;
        }
        
        return $data;
    }
}

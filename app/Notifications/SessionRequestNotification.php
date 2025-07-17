<?php

namespace App\Notifications;

use App\Models\TeachingSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected TeachingSession $session;

    /**
     * Create a new notification instance.
     */
    public function __construct(TeachingSession $session)
    {
        $this->session = $session;
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
        $student = $this->session->student;
        $subject = $this->session->subject;
        
        return (new MailMessage)
            ->subject('New Session Request')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have received a new session request from ' . $student->name . '.')
            ->line('Subject: ' . $subject->name)
            ->line('Scheduled for: ' . $this->session->scheduled_at->format('F j, Y \a\t g:i A'))
            ->action('View Request', url('/teacher/sessions/' . $this->session->id))
            ->line('Please respond to this request as soon as possible.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $student = $this->session->student;
        $subject = $this->session->subject;
        
        return [
            'session_id' => $this->session->id,
            'title' => 'New Session Request',
            'message' => 'You have received a new session request from ' . $student->name . ' for ' . $subject->name,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'avatar' => $student->avatar,
            ],
            'subject' => [
                'id' => $subject->id,
                'name' => $subject->name,
            ],
            'scheduled_at' => $this->session->scheduled_at,
            'action_text' => 'View Request',
            'action_url' => '/teacher/sessions/' . $this->session->id,
        ];
    }
}

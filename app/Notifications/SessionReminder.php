<?php

namespace App\Notifications;

use App\Models\TeachingSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $session;
    protected $recipientType;

    /**
     * Create a new notification instance.
     */
    public function __construct(TeachingSession $session, string $recipientType)
    {
        $this->session = $session;
        $this->recipientType = $recipientType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $startTime = $this->session->start_time->format('h:i A');
        $subject = $this->session->subject->name;
        
        if ($this->recipientType === 'teacher') {
            $studentName = $this->session->student->name;
            
            return (new MailMessage)
                ->subject("Reminder: Teaching Session at {$startTime}")
                ->greeting("Hello {$notifiable->name},")
                ->line("This is a reminder that you have a teaching session scheduled in about an hour.")
                ->line("Session Details:")
                ->line("- Subject: {$subject}")
                ->line("- Student: {$studentName}")
                ->line("- Time: {$startTime}")
                ->line("- Date: {$this->session->session_date->format('l, F j, Y')}")
                ->action('Join Session', url('/sessions/' . $this->session->id . '/teacher-join'))
                ->line('Please be ready 5 minutes before the scheduled time.');
        } else {
            $teacherName = $this->session->teacher->name;
            
            return (new MailMessage)
                ->subject("Reminder: Learning Session at {$startTime}")
                ->greeting("Hello {$notifiable->name},")
                ->line("This is a reminder that you have a learning session scheduled in about an hour.")
                ->line("Session Details:")
                ->line("- Subject: {$subject}")
                ->line("- Teacher: {$teacherName}")
                ->line("- Time: {$startTime}")
                ->line("- Date: {$this->session->session_date->format('l, F j, Y')}")
                ->action('Join Session', url('/sessions/' . $this->session->id . '/student-join'))
                ->line('Please be ready 5 minutes before the scheduled time.');
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'session_id' => $this->session->id,
            'session_uuid' => $this->session->session_uuid,
            'subject' => $this->session->subject->name,
            'start_time' => $this->session->start_time->format('h:i A'),
            'session_date' => $this->session->session_date->format('Y-m-d'),
            'recipient_type' => $this->recipientType,
        ];
    }
} 
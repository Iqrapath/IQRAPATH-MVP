<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public string $reason = '',
        public bool $notifyParties = true
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        // Only send emails in production, database notifications always work
        if (app()->environment('production')) {
            return ['database', 'mail'];
        }
        
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $isStudent = $notifiable->id === $this->booking->student_id;
        $isTeacher = $notifiable->id === $this->booking->teacher_id;
        
        $subject = $isStudent ? 'Your booking has been cancelled' : 'A booking you were teaching has been cancelled';
        
        return (new MailMessage)
            ->subject($subject)
            ->view('emails.booking-cancelled', [
                'booking' => $this->booking,
                'user' => $notifiable,
                'reason' => $this->reason,
                'isStudent' => $isStudent,
                'isTeacher' => $isTeacher,
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $isStudent = $notifiable->id === $this->booking->student_id;
        $isTeacher = $notifiable->id === $this->booking->teacher_id;
        
        return [
            'title' => 'Booking Cancelled',
            'message' => $isStudent 
                ? 'Your booking with ' . $this->booking->teacher->name . ' has been cancelled.'
                : 'Your teaching session with ' . $this->booking->student->name . ' has been cancelled.',
            'type' => 'booking_cancelled',
            'booking_id' => $this->booking->id,
            'reason' => $this->reason,
            'booking_date' => $this->booking->booking_date,
            'start_time' => $this->booking->start_time,
            'subject' => $this->booking->subject->template->name ?? 'Unknown Subject',
        ];
    }
}

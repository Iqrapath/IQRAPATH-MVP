<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRescheduledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public string $oldDate,
        public string $oldTime,
        public ?string $reason = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $isForTeacher = $notifiable->role === 'teacher';
        $isForStudent = $notifiable->role === 'student';
        
        if ($isForTeacher) {
            return (new MailMessage)
                ->subject('Booking Rescheduled - IQRAQUEST')
                ->view('emails.booking-rescheduled', [
                    'notifiable' => $notifiable,
                    'booking' => $this->booking,
                    'oldDate' => $this->oldDate,
                    'oldTime' => $this->oldTime,
                    'reason' => $this->reason,
                    'isForTeacher' => true,
                ]);
        } elseif ($isForStudent) {
            return (new MailMessage)
                ->subject('Your Booking Has Been Rescheduled - IQRAQUEST')
                ->view('emails.booking-rescheduled', [
                    'notifiable' => $notifiable,
                    'booking' => $this->booking,
                    'oldDate' => $this->oldDate,
                    'oldTime' => $this->oldTime,
                    'reason' => $this->reason,
                    'isForTeacher' => false,
                ]);
        }

        return (new MailMessage)
            ->subject('Booking Rescheduled')
            ->line('A booking has been rescheduled.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'booking_rescheduled',
            'booking_id' => $this->booking->id,
            'old_date' => $this->oldDate,
            'old_time' => $this->oldTime,
            'new_date' => $this->booking->booking_date,
            'new_time' => $this->booking->start_time,
            'reason' => $this->reason,
            'message' => "Your booking has been rescheduled from {$this->oldDate} {$this->oldTime} to {$this->booking->booking_date} {$this->booking->start_time}.",
        ];
    }
}

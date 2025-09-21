<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class BookingReassignedNotification extends Notification
{
    use Queueable;

    protected Booking $booking;
    protected string $type; // 'assigned', 'removed', 'student'
    protected ?string $adminNote;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, string $type, ?string $adminNote = null)
    {
        $this->booking = $booking;
        $this->type = $type;
        $this->adminNote = $adminNote;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $bookingDate = Carbon::parse($this->booking->booking_date)->format('F j, Y');
        $bookingTime = Carbon::parse($this->booking->start_time)->format('h:i A');

        if ($this->type === 'assigned') {
            return (new MailMessage)
                ->subject('New Booking Assignment - Action Required')
                ->view('emails.booking-reassigned-assigned', [
                    'notifiable' => $notifiable,
                    'booking' => $this->booking,
                    'bookingDate' => $bookingDate,
                    'bookingTime' => $bookingTime,
                    'adminNote' => $this->adminNote,
                ]);
        } elseif ($this->type === 'removed') {
            return (new MailMessage)
                ->subject('Booking Reassignment - You Have Been Removed')
                ->view('emails.booking-reassigned-removed', [
                    'notifiable' => $notifiable,
                    'booking' => $this->booking,
                    'bookingDate' => $bookingDate,
                    'bookingTime' => $bookingTime,
                    'adminNote' => $this->adminNote,
                ]);
        } else { // student
            return (new MailMessage)
                ->subject('Booking Teacher Changed - Important Update')
                ->view('emails.booking-reassigned-student', [
                    'notifiable' => $notifiable,
                    'booking' => $this->booking,
                    'bookingDate' => $bookingDate,
                    'bookingTime' => $bookingTime,
                    'adminNote' => $this->adminNote,
                ]);
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $bookingDate = Carbon::parse($this->booking->booking_date)->format('F j, Y');
        $bookingTime = Carbon::parse($this->booking->start_time)->format('h:i A');

        if ($this->type === 'assigned') {
            return [
                'booking_id' => $this->booking->id,
                'booking_uuid' => $this->booking->booking_uuid,
                'message' => "You have been assigned to teach {$this->booking->subject->template->name} for {$this->booking->student->name} on {$bookingDate} at {$bookingTime}.",
                'type' => 'booking_reassigned_assigned',
                'booking_date' => $this->booking->booking_date,
                'booking_time' => $this->booking->start_time,
                'student_name' => $this->booking->student->name,
                'subject_name' => $this->booking->subject->template->name,
                'admin_note' => $this->adminNote,
            ];
        } elseif ($this->type === 'removed') {
            return [
                'booking_id' => $this->booking->id,
                'booking_uuid' => $this->booking->booking_uuid,
                'message' => "You have been removed from teaching {$this->booking->subject->template->name} for {$this->booking->student->name} on {$bookingDate} at {$bookingTime}.",
                'type' => 'booking_reassigned_removed',
                'booking_date' => $this->booking->booking_date,
                'booking_time' => $this->booking->start_time,
                'student_name' => $this->booking->student->name,
                'subject_name' => $this->booking->subject->template->name,
                'admin_note' => $this->adminNote,
            ];
        } else { // student
            return [
                'booking_id' => $this->booking->id,
                'booking_uuid' => $this->booking->booking_uuid,
                'message' => "Your {$this->booking->subject->template->name} session on {$bookingDate} at {$bookingTime} has been reassigned to a different teacher.",
                'type' => 'booking_reassigned_student',
                'booking_date' => $this->booking->booking_date,
                'booking_time' => $this->booking->start_time,
                'teacher_name' => $this->booking->teacher->name,
                'subject_name' => $this->booking->subject->template->name,
                'admin_note' => $this->adminNote,
            ];
        }
    }
}

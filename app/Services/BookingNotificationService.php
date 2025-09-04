<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingNotification;
use App\Models\BookingHistory;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BookingNotificationService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Send booking created notifications to both student and teacher.
     */
    public function sendBookingCreatedNotifications(Booking $booking): void
    {
        $student = $booking->student;
        $teacher = $booking->teacher;
        $subject = $booking->subject;

        // Create booking history entry
        $this->createBookingHistory($booking, 'created', [
            'booking_data' => $booking->toArray()
        ], $student);

        // Create main notification for student
        Notification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\BookingNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $student->id,
            'data' => [
                'title' => 'Booking Confirmed',
                'message' => "Your booking for {$subject->name} with {$teacher->name} has been created and is pending approval.",
                'level' => 'success',
                'action_url' => route('student.my-bookings'),
                'action_text' => 'View Booking',
                'booking_id' => $booking->id,
                'teacher_name' => $teacher->name,
                'subject_name' => $subject->name,
                'booking_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
            ],
            'read_at' => null,
        ]);

        // Create main notification for teacher
        Notification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\BookingNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $teacher->id,
            'data' => [
                'title' => 'New Booking Request',
                'message' => "You have a new booking request from {$student->name} for {$subject->name}.",
                'level' => 'info',
                'action_url' => route('teacher.bookings.index'),
                'action_text' => 'Review Booking',
                'booking_id' => $booking->id,
                'student_name' => $student->name,
                'subject_name' => $subject->name,
                'booking_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
            ],
            'read_at' => null,
        ]);

        // Send email notifications
        $this->sendEmailNotifications($booking, 'booking_created');
    }

    /**
     * Send booking approved notifications.
     */
    public function sendBookingApprovedNotifications(Booking $booking): void
    {
        $student = $booking->student;
        $teacher = $booking->teacher;
        $subject = $booking->subject;

        // Create booking history entry
        $this->createBookingHistory($booking, 'approved', [
            'previous_status' => 'pending',
            'new_status' => 'approved'
        ], $teacher);

        // Create main notification for student
        Notification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\BookingNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $student->id,
            'data' => [
                'title' => 'Booking Approved!',
                'message' => "Great news! Your booking for {$subject->name} with {$teacher->name} has been approved.",
                'level' => 'success',
                'action_url' => route('student.my-bookings'),
                'action_text' => 'View Booking',
                'booking_id' => $booking->id,
                'teacher_name' => $teacher->name,
                'subject_name' => $subject->name,
                'booking_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'meeting_link' => $booking->teachingSession?->zoom_join_url ?? $booking->teachingSession?->google_meet_link,
            ],
            'read_at' => null,
        ]);

        // Create main notification for teacher
        Notification::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\BookingNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $teacher->id,
            'data' => [
                'title' => 'Booking Approved',
                'message' => "You have approved the booking request from {$student->name} for {$subject->name}.",
                'level' => 'success',
                'action_url' => route('teacher.bookings.index'),
                'action_text' => 'View Booking',
                'booking_id' => $booking->id,
                'student_name' => $student->name,
                'subject_name' => $subject->name,
                'booking_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
            ],
            'read_at' => null,
        ]);

        // Send email notifications
        $this->sendEmailNotifications($booking, 'booking_approved');

        // Schedule reminder notifications
        $this->scheduleReminderNotifications($booking);
    }

    /**
     * Send booking rejected notifications.
     */
    public function sendBookingRejectedNotifications(Booking $booking, ?string $reason = null): void
    {
        $student = $booking->student;
        $teacher = $booking->teacher;
        $subject = $booking->subject;

        // Create booking history entry
        $this->createBookingHistory($booking, 'rejected', [
            'previous_status' => 'pending',
            'new_status' => 'rejected',
            'reason' => $reason
        ], $teacher);

        // Notify student
        $this->createNotification(
            $booking,
            $student,
            'booking_rejected',
            'Booking Not Available',
            "Unfortunately, your booking for {$subject->name} with {$teacher->name} could not be approved.",
            [
                'teacher_name' => $teacher->name,
                'subject_name' => $subject->name,
                'booking_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'reason' => $reason,
            ]
        );

        // Send email notifications
        $this->sendEmailNotifications($booking, 'booking_rejected');
    }

    /**
     * Send session reminder notifications.
     */
    public function sendSessionReminderNotifications(Booking $booking): void
    {
        $student = $booking->student;
        $teacher = $booking->teacher;
        $subject = $booking->subject;

        // Create booking history entry
        $this->createBookingHistory($booking, 'reminder_sent', [
            'reminder_type' => 'session_starting_soon'
        ], null);

        // Notify student
        $this->createNotification(
            $booking,
            $student,
            'session_starting_soon',
            'Session Starting Soon',
            "Your {$subject->name} session with {$teacher->name} is starting in 15 minutes!",
            [
                'teacher_name' => $teacher->name,
                'subject_name' => $subject->name,
                'booking_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'meeting_link' => $booking->teachingSession?->zoom_join_url ?? $booking->teachingSession?->google_meet_link,
            ]
        );

        // Notify teacher
        $this->createNotification(
            $booking,
            $teacher,
            'session_starting_soon',
            'Session Starting Soon',
            "Your {$subject->name} session with {$student->name} is starting in 15 minutes!",
            [
                'student_name' => $student->name,
                'subject_name' => $subject->name,
                'booking_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'meeting_link' => $booking->teachingSession?->zoom_join_url ?? $booking->teachingSession?->google_meet_link,
            ]
        );

        // Send email notifications
        $this->sendEmailNotifications($booking, 'session_starting_soon');
    }

    /**
     * Create a notification record.
     */
    public function createNotification(
        Booking $booking,
        User $user,
        string $type,
        string $title,
        string $message,
        array $metadata = []
    ): BookingNotification {
        return BookingNotification::create([
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'notification_type' => $type,
            'channel' => 'in_app',
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata,
            'is_read' => false,
            'is_sent' => true,
            'sent_at' => now(),
        ]);
    }

    /**
     * Create a booking history entry.
     */
    private function createBookingHistory(
        Booking $booking,
        string $action,
        array $data = [],
        ?User $performedBy = null
    ): BookingHistory {
        return BookingHistory::create([
            'booking_id' => $booking->id,
            'action' => $action,
            'new_data' => $data,
            'performed_by_id' => $performedBy?->id ?? auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Send email notifications.
     */
    private function sendEmailNotifications(Booking $booking, string $type): void
    {
        try {
            $student = $booking->student;
            $teacher = $booking->teacher;
            $subject = $booking->subject;

            $emailData = [
                'student_name' => $student->name,
                'teacher_name' => $teacher->name,
                'subject_name' => $subject->name,
                'booking_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'meeting_link' => $booking->teachingSession?->zoom_join_url ?? $booking->teachingSession?->google_meet_link,
            ];

            // Send email to student
            if ($student->email) {
                Mail::send("emails.booking.{$type}", $emailData, function ($message) use ($student, $type) {
                    $message->to($student->email, $student->name)
                           ->subject($this->getEmailSubject($type, 'student'));
                });
            }

            // Send email to teacher
            if ($teacher->email) {
                Mail::send("emails.booking.{$type}", $emailData, function ($message) use ($teacher, $type) {
                    $message->to($teacher->email, $teacher->name)
                           ->subject($this->getEmailSubject($type, 'teacher'));
                });
            }

            // Create email notification records
            $this->createNotification(
                $booking,
                $student,
                $type,
                $this->getEmailSubject($type, 'student'),
                $this->getEmailMessage($type, 'student', $emailData),
                array_merge($emailData, ['channel' => 'email'])
            );

            $this->createNotification(
                $booking,
                $teacher,
                $type,
                $this->getEmailSubject($type, 'teacher'),
                $this->getEmailMessage($type, 'teacher', $emailData),
                array_merge($emailData, ['channel' => 'email'])
            );

        } catch (\Exception $e) {
            Log::error('Failed to send booking email notifications', [
                'booking_id' => $booking->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Schedule reminder notifications.
     */
    private function scheduleReminderNotifications(Booking $booking): void
    {
        // Properly format the date and time
        $bookingDate = Carbon::parse($booking->booking_date)->format('Y-m-d');
        $startTime = Carbon::parse($booking->start_time)->format('H:i:s');
        $sessionDateTime = Carbon::parse($bookingDate . ' ' . $startTime);
        
        // Schedule 15-minute reminder
        $reminderTime = $sessionDateTime->subMinutes(15);
        
        if ($reminderTime->isFuture()) {
            BookingNotification::create([
                'booking_id' => $booking->id,
                'user_id' => $booking->student_id,
                'notification_type' => 'session_starting_soon',
                'channel' => 'in_app',
                'title' => 'Session Starting Soon',
                'message' => "Your session is starting in 15 minutes!",
                'scheduled_at' => $reminderTime,
                'is_sent' => false,
            ]);

            BookingNotification::create([
                'booking_id' => $booking->id,
                'user_id' => $booking->teacher_id,
                'notification_type' => 'session_starting_soon',
                'channel' => 'in_app',
                'title' => 'Session Starting Soon',
                'message' => "Your session is starting in 15 minutes!",
                'scheduled_at' => $reminderTime,
                'is_sent' => false,
            ]);
        }
    }

    /**
     * Get email subject based on type and recipient.
     */
    private function getEmailSubject(string $type, string $recipient): string
    {
        return match ($type) {
            'booking_created' => $recipient === 'student' 
                ? 'Booking Request Confirmed' 
                : 'New Booking Request Received',
            'booking_approved' => 'Booking Approved - Session Details',
            'booking_rejected' => 'Booking Update',
            'session_starting_soon' => 'Session Starting Soon - Join Now',
            default => 'Booking Update'
        };
    }

    /**
     * Get email message based on type and recipient.
     */
    private function getEmailMessage(string $type, string $recipient, array $data): string
    {
        return match ($type) {
            'booking_created' => $recipient === 'student'
                ? "Your booking for {$data['subject_name']} with {$data['teacher_name']} has been created and is pending approval."
                : "You have a new booking request from {$data['student_name']} for {$data['subject_name']}.",
            'booking_approved' => "Great news! Your booking for {$data['subject_name']} with {$data['teacher_name']} has been approved.",
            'booking_rejected' => "Unfortunately, your booking for {$data['subject_name']} with {$data['teacher_name']} could not be approved.",
            'session_starting_soon' => "Your {$data['subject_name']} session with {$data['teacher_name']} is starting in 15 minutes!",
            default => 'Booking update notification'
        };
    }
}

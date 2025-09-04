<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function __construct(
        private BookingNotificationService $bookingNotificationService
    ) {}

    /**
     * Display teacher's bookings
     */
    public function index(Request $request)
    {
        $teacher = auth()->user();
        
        $bookings = Booking::where('teacher_id', $teacher->id)
            ->with([
                'student.studentProfile',
                'subject',
                'teachingSession'
            ])
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(15);

        return response()->json([
            'bookings' => $bookings
        ]);
    }

    /**
     * Approve a booking
     */
    public function approve(Request $request, Booking $booking): JsonResponse
    {
        $teacher = auth()->user();
        
        // Ensure the booking belongs to this teacher
        if ($booking->teacher_id !== $teacher->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to booking.'
            ], 403);
        }

        // Check if booking can be approved
        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Booking cannot be approved in its current status.'
            ], 400);
        }

        DB::transaction(function () use ($booking, $teacher) {
            // Update booking status
            $booking->update([
                'status' => 'approved',
                'approved_by_id' => $teacher->id,
                'approved_at' => now(),
            ]);

            // Create teaching session if not exists
            if (!$booking->teachingSession) {
                $booking->teachingSession()->create([
                    'session_uuid' => \Illuminate\Support\Str::uuid(),
                    'teacher_id' => $booking->teacher_id,
                    'student_id' => $booking->student_id,
                    'subject_id' => $booking->subject_id,
                    'session_date' => $booking->booking_date,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'status' => 'scheduled',
                    'meeting_platform' => 'zoom', // Default platform
                ]);
            }

            // Send approval notifications
            $this->bookingNotificationService->sendBookingApprovedNotifications($booking);
        });

        return response()->json([
            'success' => true,
            'message' => 'Booking approved successfully.',
            'booking' => $booking->fresh(['student', 'subject', 'teachingSession'])
        ]);
    }

    /**
     * Reject a booking
     */
    public function reject(Request $request, Booking $booking): JsonResponse
    {
        $teacher = auth()->user();
        
        // Ensure the booking belongs to this teacher
        if ($booking->teacher_id !== $teacher->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to booking.'
            ], 403);
        }

        // Check if booking can be rejected
        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Booking cannot be rejected in its current status.'
            ], 400);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        DB::transaction(function () use ($booking, $teacher, $request) {
            // Update booking status
            $booking->update([
                'status' => 'rejected',
                'rejected_by_id' => $teacher->id,
                'rejected_at' => now(),
                'rejection_reason' => $request->input('reason'),
            ]);

            // Send rejection notifications
            $this->bookingNotificationService->sendBookingRejectedNotifications(
                $booking, 
                $request->input('reason')
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Booking rejected successfully.',
            'booking' => $booking->fresh(['student', 'subject'])
        ]);
    }

    /**
     * Reschedule a booking
     */
    public function reschedule(Request $request, Booking $booking): JsonResponse
    {
        $teacher = auth()->user();
        
        // Ensure the booking belongs to this teacher
        if ($booking->teacher_id !== $teacher->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to booking.'
            ], 403);
        }

        $request->validate([
            'new_date' => 'required|date|after:today',
            'new_start_time' => 'required|date_format:H:i',
            'new_end_time' => 'required|date_format:H:i|after:new_start_time',
            'reason' => 'nullable|string|max:500'
        ]);

        DB::transaction(function () use ($booking, $teacher, $request) {
            $oldData = [
                'booking_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
            ];

            $newData = [
                'booking_date' => $request->input('new_date'),
                'start_time' => $request->input('new_start_time'),
                'end_time' => $request->input('new_end_time'),
                'rescheduled_by_id' => $teacher->id,
                'rescheduled_at' => now(),
                'reschedule_reason' => $request->input('reason'),
            ];

            // Update booking
            $booking->update($newData);

            // Update teaching session if exists
            if ($booking->teachingSession) {
                $booking->teachingSession->update([
                    'session_date' => $request->input('new_date'),
                    'start_time' => $request->input('new_start_time'),
                    'end_time' => $request->input('new_end_time'),
                ]);
            }

            // Create booking history entry
            \App\Models\BookingHistory::create([
                'booking_id' => $booking->id,
                'action' => 'rescheduled',
                'previous_data' => $oldData,
                'new_data' => $newData,
                'performed_by_id' => $teacher->id,
                'notes' => $request->input('reason'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Send reschedule notifications
            $this->sendRescheduleNotifications($booking, $oldData, $newData);
        });

        return response()->json([
            'success' => true,
            'message' => 'Booking rescheduled successfully.',
            'booking' => $booking->fresh(['student', 'subject', 'teachingSession'])
        ]);
    }

    /**
     * Cancel a booking
     */
    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $teacher = auth()->user();
        
        // Ensure the booking belongs to this teacher
        if ($booking->teacher_id !== $teacher->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to booking.'
            ], 403);
        }

        // Check if booking can be cancelled
        if (!in_array($booking->status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Booking cannot be cancelled in its current status.'
            ], 400);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);

        DB::transaction(function () use ($booking, $teacher, $request) {
            // Update booking status
            $booking->update([
                'status' => 'cancelled',
                'cancelled_by_id' => $teacher->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $request->input('reason'),
            ]);

            // Update teaching session if exists
            if ($booking->teachingSession) {
                $booking->teachingSession->update([
                    'status' => 'cancelled',
                ]);
            }

            // Create booking history entry
            \App\Models\BookingHistory::create([
                'booking_id' => $booking->id,
                'action' => 'cancelled',
                'previous_data' => ['status' => $booking->getOriginal('status')],
                'new_data' => ['status' => 'cancelled', 'reason' => $request->input('reason')],
                'performed_by_id' => $teacher->id,
                'notes' => $request->input('reason'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Send cancellation notifications
            $this->sendCancellationNotifications($booking, $request->input('reason'));
        });

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'booking' => $booking->fresh(['student', 'subject'])
        ]);
    }

    /**
     * Send reschedule notifications
     */
    private function sendRescheduleNotifications(Booking $booking, array $oldData, array $newData): void
    {
        $student = $booking->student;
        $teacher = $booking->teacher;
        $subject = $booking->subject;

        // Notify student
        $this->bookingNotificationService->createNotification(
            $booking,
            $student,
            'rescheduled',
            'Session Rescheduled',
            "Your {$subject->name} session with {$teacher->name} has been rescheduled.",
            [
                'teacher_name' => $teacher->name,
                'subject_name' => $subject->name,
                'old_date' => $oldData['booking_date'],
                'old_time' => $oldData['start_time'],
                'new_date' => $newData['booking_date'],
                'new_time' => $newData['start_time'],
            ]
        );

        // Notify teacher
        $this->bookingNotificationService->createNotification(
            $booking,
            $teacher,
            'rescheduled',
            'Session Rescheduled',
            "You have rescheduled your {$subject->name} session with {$student->name}.",
            [
                'student_name' => $student->name,
                'subject_name' => $subject->name,
                'old_date' => $oldData['booking_date'],
                'old_time' => $oldData['start_time'],
                'new_date' => $newData['booking_date'],
                'new_time' => $newData['start_time'],
            ]
        );
    }

    /**
     * Send cancellation notifications
     */
    private function sendCancellationNotifications(Booking $booking, ?string $reason): void
    {
        $student = $booking->student;
        $teacher = $booking->teacher;
        $subject = $booking->subject;

        // Notify student
        $this->bookingNotificationService->createNotification(
            $booking,
            $student,
            'cancelled',
            'Session Cancelled',
            "Your {$subject->name} session with {$teacher->name} has been cancelled.",
            [
                'teacher_name' => $teacher->name,
                'subject_name' => $subject->name,
                'booking_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'reason' => $reason,
            ]
        );

        // Notify teacher
        $this->bookingNotificationService->createNotification(
            $booking,
            $teacher,
            'cancelled',
            'Session Cancelled',
            "You have cancelled your {$subject->name} session with {$student->name}.",
            [
                'student_name' => $student->name,
                'subject_name' => $subject->name,
                'booking_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'reason' => $reason,
            ]
        );
    }
}

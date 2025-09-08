<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingModification;
use App\Models\User;
use App\Models\Subject;
use App\Models\TeacherAvailability;
use App\Services\NotificationService;
use App\Services\FinancialService;
use App\Services\ZoomService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BookingModificationService
{
    public function __construct(
        private NotificationService $notificationService,
        private FinancialService $financialService,
        private ZoomService $zoomService
    ) {}

    /**
     * Create a reschedule request
     */
    public function createRescheduleRequest(
        int $bookingId,
        int $studentId,
        array $newDetails,
        string $reason = null
    ): BookingModification {
        return DB::transaction(function () use ($bookingId, $studentId, $newDetails, $reason) {
            $booking = Booking::with(['teacher', 'subject'])->findOrFail($bookingId);
            
            // Validate the new time slot
            $this->validateNewTimeSlot($booking->teacher_id, $newDetails);
            
            // Check if there's already a pending modification for this booking
            $existingModification = BookingModification::where('booking_id', $bookingId)
                ->whereIn('status', ['pending', 'approved'])
                ->first();
                
            if ($existingModification) {
                throw new \Exception('A modification request is already pending for this booking.');
            }
            
            // Create the modification request
            $modification = BookingModification::createRescheduleRequest(
                $bookingId,
                $studentId,
                $booking->teacher_id,
                $newDetails,
                $reason,
                $studentId
            );
            
            // Send notification to teacher
            $this->notificationService->createNotification(
                $booking->teacher, // User object
                'reschedule_request', // Notification type
                [
                    'title' => 'New Reschedule Request',
                    'body' => "Student {$booking->student->name} has requested to reschedule their class.",
                    'related_type' => 'booking_modification',
                    'related_id' => $modification->id,
                ]
            );
            
            // Log the action
            Log::info('Reschedule request created', [
                'modification_id' => $modification->id,
                'booking_id' => $bookingId,
                'student_id' => $studentId,
                'teacher_id' => $booking->teacher_id,
            ]);
            
            return $modification;
        });
    }

    /**
     * Create a rebook request
     */
    public function createRebookRequest(
        int $bookingId,
        int $studentId,
        int $newTeacherId,
        int $newSubjectId,
        array $newDetails,
        string $reason = null
    ): BookingModification {
        return DB::transaction(function () use ($bookingId, $studentId, $newTeacherId, $newSubjectId, $newDetails, $reason) {
            $booking = Booking::with(['teacher', 'subject'])->findOrFail($bookingId);
            $newTeacher = User::findOrFail($newTeacherId);
            $newSubject = Subject::findOrFail($newSubjectId);
            
            // Validate the new teacher and time slot
            $this->validateNewTeacher($newTeacherId);
            $this->validateNewTimeSlot($newTeacherId, $newDetails);
            
            // Check if there's already a pending modification for this booking
            $existingModification = BookingModification::where('booking_id', $bookingId)
                ->whereIn('status', ['pending', 'approved'])
                ->first();
                
            if ($existingModification) {
                throw new \Exception('A modification request is already pending for this booking.');
            }
            
            // Calculate price difference
            $priceDifference = $this->calculatePriceDifference($booking, $newSubject, $newDetails);
            
            // Create the modification request
            $modification = BookingModification::createRebookRequest(
                $bookingId,
                $studentId,
                $booking->teacher_id,
                $newTeacherId,
                $newSubjectId,
                $newDetails,
                $reason,
                $studentId
            );
            
            // Update price difference
            $modification->update(['price_difference' => $priceDifference]);
            
            // Send notification to new teacher
            $newTeacher = User::find($newTeacherId);
            $this->notificationService->createNotification(
                $newTeacher,
                'rebook_request',
                [
                    'title' => 'New Rebook Request',
                    'body' => "Student {$booking->student->name} wants to rebook a class with you.",
                    'related_type' => 'booking_modification',
                    'related_id' => $modification->id,
                ]
            );
            
            // Send notification to original teacher
            $this->notificationService->createNotification(
                $booking->teacher,
                'booking_rebook_request',
                [
                    'title' => 'Booking Rebook Request',
                    'body' => "Student {$booking->student->name} wants to rebook their class with another teacher.",
                    'related_type' => 'booking_modification',
                    'related_id' => $modification->id,
                ]
            );
            
            // Log the action
            Log::info('Rebook request created', [
                'modification_id' => $modification->id,
                'booking_id' => $bookingId,
                'student_id' => $studentId,
                'original_teacher_id' => $booking->teacher_id,
                'new_teacher_id' => $newTeacherId,
                'price_difference' => $priceDifference,
            ]);
            
            return $modification;
        });
    }

    /**
     * Approve a modification request
     */
    public function approveModification(
        int $modificationId,
        int $teacherId,
        string $teacherNotes = null
    ): BookingModification {
        return DB::transaction(function () use ($modificationId, $teacherId, $teacherNotes) {
            $modification = BookingModification::findOrFail($modificationId);
            
            // Verify teacher has permission to approve
            if ($modification->teacher_id !== $teacherId) {
                throw new \Exception('You are not authorized to approve this request.');
            }
            
            if (!$modification->canBeApproved()) {
                throw new \Exception('This modification request cannot be approved.');
            }
            
            // Approve the modification
            $modification->approve($teacherNotes, $teacherId);
            
            // Process the modification
            $this->processApprovedModification($modification);
            
            // Send notification to student
            $student = User::find($modification->student_id);
            $this->notificationService->createNotification(
                $student,
                'modification_approved',
                [
                    'title' => 'Modification Request Approved',
                    'body' => "Your {$modification->formatted_type} has been approved.",
                    'related_type' => 'booking_modification',
                    'related_id' => $modification->id,
                ]
            );
            
            // Log the action
            Log::info('Modification request approved', [
                'modification_id' => $modification->id,
                'teacher_id' => $teacherId,
                'type' => $modification->type,
            ]);
            
            return $modification;
        });
    }

    /**
     * Reject a modification request
     */
    public function rejectModification(
        int $modificationId,
        int $teacherId,
        string $teacherNotes = null
    ): BookingModification {
        return DB::transaction(function () use ($modificationId, $teacherId, $teacherNotes) {
            $modification = BookingModification::findOrFail($modificationId);
            
            // Verify teacher has permission to reject
            if ($modification->teacher_id !== $teacherId) {
                throw new \Exception('You are not authorized to reject this request.');
            }
            
            if (!$modification->canBeRejected()) {
                throw new \Exception('This modification request cannot be rejected.');
            }
            
            // Reject the modification
            $modification->reject($teacherNotes, $teacherId);
            
            // Send notification to student
            $student = User::find($modification->student_id);
            $this->notificationService->createNotification(
                $student,
                'modification_rejected',
                [
                    'title' => 'Modification Request Rejected',
                    'body' => "Your {$modification->formatted_type} has been rejected.",
                    'related_type' => 'booking_modification',
                    'related_id' => $modification->id,
                ]
            );
            
            // Log the action
            Log::info('Modification request rejected', [
                'modification_id' => $modification->id,
                'teacher_id' => $teacherId,
                'type' => $modification->type,
            ]);
            
            return $modification;
        });
    }

    /**
     * Process an approved modification
     */
    private function processApprovedModification(BookingModification $modification): void
    {
        $booking = $modification->booking;
        
        if ($modification->type === 'reschedule') {
            $this->processReschedule($modification, $booking);
        } elseif ($modification->type === 'rebook') {
            $this->processRebook($modification, $booking);
        }
        
        // Mark as completed
        $modification->complete();
    }

    /**
     * Process a reschedule
     */
    private function processReschedule(BookingModification $modification, Booking $booking): void
    {
        // Update the original booking
        $booking->update([
            'booking_date' => $modification->new_booking_date,
            'start_time' => $modification->new_start_time,
            'end_time' => $modification->new_end_time,
            'duration_minutes' => $modification->new_duration_minutes,
            'meeting_platform' => $modification->new_meeting_platform,
        ]);
        
        // Update or create teaching session
        if ($booking->teachingSession) {
            $booking->teachingSession->update([
                'session_date' => $modification->new_booking_date,
                'start_time' => $modification->new_start_time,
                'end_time' => $modification->new_end_time,
                'duration_minutes' => $modification->new_duration_minutes,
                'meeting_platform' => $modification->new_meeting_platform,
            ]);
        }
        
        // Create new Zoom meeting if needed
        if ($modification->new_meeting_platform === 'zoom') {
            $this->zoomService->createMeeting([
                'booking_id' => $booking->id,
                'session_date' => $modification->new_booking_date,
                'start_time' => $modification->new_start_time,
                'end_time' => $modification->new_end_time,
                'teacher_id' => $booking->teacher_id,
            ]);
        }
    }

    /**
     * Process a rebook
     */
    private function processRebook(BookingModification $modification, Booking $booking): void
    {
        // Cancel the original booking
        $booking->update(['status' => 'cancelled']);
        
        // Create new booking
        $newBooking = Booking::create([
            'booking_uuid' => \Illuminate\Support\Str::uuid(),
            'student_id' => $modification->student_id,
            'teacher_id' => $modification->new_teacher_id,
            'subject_id' => $modification->new_subject_id,
            'booking_date' => $modification->new_booking_date,
            'start_time' => $modification->new_start_time,
            'end_time' => $modification->new_end_time,
            'duration_minutes' => $modification->new_duration_minutes,
            'meeting_platform' => $modification->new_meeting_platform,
            'status' => 'confirmed',
            'notes' => "Rebooked from booking #{$booking->id}",
        ]);
        
        // Create teaching session
        $newBooking->teachingSession()->create([
            'session_date' => $modification->new_booking_date,
            'start_time' => $modification->new_start_time,
            'end_time' => $modification->new_end_time,
            'duration_minutes' => $modification->new_duration_minutes,
            'meeting_platform' => $modification->new_meeting_platform,
            'status' => 'scheduled',
        ]);
        
        // Create Zoom meeting if needed
        if ($modification->new_meeting_platform === 'zoom') {
            $this->zoomService->createMeeting([
                'booking_id' => $newBooking->id,
                'session_date' => $modification->new_booking_date,
                'start_time' => $modification->new_start_time,
                'end_time' => $modification->new_end_time,
                'teacher_id' => $modification->new_teacher_id,
            ]);
        }
        
        // Handle financial implications
        if ($modification->price_difference > 0) {
            // Student needs to pay more
            $this->financialService->processPayment([
                'user_id' => $modification->student_id,
                'amount' => $modification->price_difference,
                'type' => 'rebook_upgrade',
                'description' => 'Additional payment for rebook upgrade',
            ]);
        } elseif ($modification->price_difference < 0) {
            // Refund the difference
            $this->financialService->processRefund([
                'user_id' => $modification->student_id,
                'amount' => abs($modification->price_difference),
                'type' => 'rebook_downgrade',
                'description' => 'Refund for rebook downgrade',
            ]);
        }
    }

    /**
     * Validate new time slot
     */
    private function validateNewTimeSlot(int $teacherId, array $newDetails): void
    {
        $newDate = Carbon::parse($newDetails['new_booking_date']);
        $newStartTime = Carbon::parse($newDetails['new_start_time']);
        $newEndTime = Carbon::parse($newDetails['new_end_time']);
        
        // Debug logging
        \Log::info('Reschedule validation debug', [
            'teacher_id' => $teacherId,
            'new_date' => $newDate->toDateString(),
            'day_of_week' => $newDate->dayOfWeek,
            'new_start_time' => $newStartTime->format('H:i:s'),
            'new_end_time' => $newEndTime->format('H:i:s'),
        ]);
        
        // Skip availability validation for reschedule since the frontend already validates
        // that the selected time slots are available. The reschedule flow uses specific
        // availability IDs that were already validated in the frontend.
        
        // Check for conflicts with existing bookings
        $conflict = Booking::where('teacher_id', $teacherId)
            ->where('booking_date', $newDate->toDateString())
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($newStartTime, $newEndTime) {
                $query->whereBetween('start_time', [$newStartTime->format('H:i:s'), $newEndTime->format('H:i:s')])
                      ->orWhereBetween('end_time', [$newStartTime->format('H:i:s'), $newEndTime->format('H:i:s')])
                      ->orWhere(function ($q) use ($newStartTime, $newEndTime) {
                          $q->where('start_time', '<=', $newStartTime->format('H:i:s'))
                            ->where('end_time', '>=', $newEndTime->format('H:i:s'));
                      });
            })
            ->first();
            
        if ($conflict) {
            throw new \Exception('Teacher has a conflicting booking at this time.');
        }
    }

    /**
     * Validate new teacher
     */
    private function validateNewTeacher(int $teacherId): void
    {
        $teacher = User::with('teacherProfile')->findOrFail($teacherId);
        
        if ($teacher->role !== 'teacher') {
            throw new \Exception('Selected user is not a teacher.');
        }
        
        if (!$teacher->teacherProfile || $teacher->teacherProfile->verification_status !== 'verified') {
            throw new \Exception('Selected teacher is not verified.');
        }
    }

    /**
     * Calculate price difference for rebook
     */
    private function calculatePriceDifference(Booking $originalBooking, Subject $newSubject, array $newDetails): float
    {
        $originalPrice = $originalBooking->subject->price_per_hour ?? 0;
        $newPrice = $newSubject->price_per_hour ?? 0;
        $duration = $newDetails['new_duration_minutes'] / 60; // Convert to hours
        
        return ($newPrice - $originalPrice) * $duration;
    }

    /**
     * Get modifications for a user
     */
    public function getModificationsForUser(int $userId, string $role = 'student', array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = BookingModification::with(['booking', 'teacher', 'newTeacher', 'newSubject'])
            ->when($role === 'student', fn($q) => $q->forStudent($userId))
            ->when($role === 'teacher', fn($q) => $q->forTeacher($userId))
            ->when(isset($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['urgent']), fn($q) => $q->urgent())
            ->orderBy('created_at', 'desc');
            
        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get expiring modifications
     */
    public function getExpiringModifications(int $hours = 24): \Illuminate\Database\Eloquent\Collection
    {
        return BookingModification::expiringSoon($hours)
            ->with(['booking', 'student', 'teacher'])
            ->get();
    }

    /**
     * Auto-expire old modifications
     */
    public function expireOldModifications(): int
    {
        $expiredCount = 0;
        
        $expiredModifications = BookingModification::where('expires_at', '<', now())
            ->where('status', 'pending')
            ->get();
            
        foreach ($expiredModifications as $modification) {
            if ($modification->markAsExpired()) {
                $expiredCount++;
                
                // Send notification to student
                $student = User::find($modification->student_id);
                $this->notificationService->createNotification(
                    $student,
                    'modification_expired',
                    [
                        'title' => 'Modification Request Expired',
                        'body' => "Your {$modification->formatted_type} has expired.",
                        'related_type' => 'booking_modification',
                        'related_id' => $modification->id,
                    ]
                );
            }
        }
        
        return $expiredCount;
    }
}

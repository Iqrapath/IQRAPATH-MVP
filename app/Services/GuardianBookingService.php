<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Booking;
use App\Models\GuardianProfile;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class GuardianBookingService
{
    /**
     * Get all bookings for a guardian (their own + children's bookings)
     */
    public function getAllBookings(User $guardian): Collection
    {
        $studentIds = $this->getStudentIds($guardian);
        
        if (empty($studentIds)) {
            return collect();
        }

        return Booking::whereIn('student_id', $studentIds)
            ->with([
                'student',
                'teacher.teacherProfile',
                'subject.template',
                'teachingSession.teacherReviews' => function($query) use ($studentIds) {
                    $query->whereIn('student_id', $studentIds);
                },
                'bookingNotes' => function($query) {
                    $query->whereIn('note_type', ['teacher_note', 'student_note', 'student_review']);
                },
                'history' => function($query) {
                    $query->latest()->take(1);
                }
            ])
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();
    }

    /**
     * Get upcoming bookings for a guardian
     */
    public function getUpcomingBookings(User $guardian): Collection
    {
        $bookings = $this->getAllBookings($guardian);
        
        return $bookings->filter(function ($booking) {
            $bookingDateTime = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time->format('H:i:s'));
            return $bookingDateTime->isFuture() && in_array($booking->status, ['upcoming', 'approved']);
        });
    }

    /**
     * Get ongoing bookings for a guardian
     */
    public function getOngoingBookings(User $guardian): Collection
    {
        $bookings = $this->getAllBookings($guardian);
        
        return $bookings->filter(function ($booking) {
            // Check if there's a teaching session with 'in_progress' status
            $session = $booking->teachingSession;
            return $session && $session->status === 'in_progress';
        });
    }

    /**
     * Get completed bookings for a guardian
     */
    public function getCompletedBookings(User $guardian): Collection
    {
        $bookings = $this->getAllBookings($guardian);
        
        return $bookings->filter(function ($booking) {
            // Check if there's a teaching session with 'completed' status
            $session = $booking->teachingSession;
            return $session && $session->status === 'completed';
        });
    }

    /**
     * Get formatted bookings data for frontend
     */
    public function getFormattedBookings(User $guardian): array
    {
        $allBookings = $this->getAllBookings($guardian);
        
        $upcoming = $this->getUpcomingBookings($guardian);
        $ongoing = $this->getOngoingBookings($guardian);
        $completed = $this->getCompletedBookings($guardian);

        return [
            'upcoming' => $this->formatBookingsForFrontend($upcoming, $guardian),
            'ongoing' => $this->formatBookingsForFrontend($ongoing, $guardian),
            'completed' => $this->formatBookingsForFrontend($completed, $guardian),
            'stats' => [
                'total' => $allBookings->count(),
                'upcoming' => $upcoming->count(),
                'ongoing' => $ongoing->count(),
                'completed' => $completed->count(),
            ]
        ];
    }

    /**
     * Get student IDs for a guardian (children + guardian if they have student role)
     */
    public function getStudentIds(User $guardian): array
    {
        $childIds = [];
        
        // Get children's user IDs
        if ($guardian->guardianProfile) {
            $childIds = $guardian->guardianProfile->students->pluck('user_id')->toArray();
        }
        
        // Include guardian's own ID if they have student role
        if ($guardian->hasAnyRole('student')) {
            $childIds[] = $guardian->id;
        }
        
        return array_unique($childIds);
    }

    /**
     * Get children data for a guardian
     */
    public function getChildrenData(User $guardian): array
    {
        if (!$guardian->guardianProfile) {
            return [];
        }

        return $guardian->guardianProfile->students->map(function ($child) {
            return [
                'id' => $child->user_id,
                'name' => $child->user->name,
                'email' => $child->user->email,
            ];
        })->toArray();
    }

    /**
     * Check if guardian can book for themselves
     */
    public function canBookForSelf(User $guardian): bool
    {
        return $guardian->hasAnyRole('student');
    }

    /**
     * Check if guardian has children
     */
    public function hasChildren(User $guardian): bool
    {
        return $guardian->guardianProfile && $guardian->guardianProfile->students->count() > 0;
    }

    /**
     * Get booking options for a guardian
     */
    public function getBookingOptions(User $guardian): array
    {
        $options = [];
        
        if ($this->canBookForSelf($guardian)) {
            $options[] = [
                'value' => 'self',
                'label' => 'For myself',
                'description' => 'Book this class for your own learning'
            ];
        }
        
        if ($this->hasChildren($guardian)) {
            $options[] = [
                'value' => 'child',
                'label' => 'For one of my children',
                'description' => 'Book this class for one of your children',
                'children' => $this->getChildrenData($guardian)
            ];
        }
        
        return $options;
    }

    /**
     * Format bookings for frontend display
     */
    private function formatBookingsForFrontend(Collection $bookings, User $guardian): array
    {
        return $bookings->map(function ($booking) use ($guardian) {
            $teacher = $booking->teacher;
            $subject = $booking->subject;
            $session = $booking->teachingSession;
            $student = $booking->student;
            
            $subjectTemplate = $subject?->template;
            $teacherProfile = $teacher?->teacherProfile;
            
            // Get teacher and student notes from BookingNote
            $teacherNotes = $booking->bookingNotes->where('note_type', 'teacher_note')->first();
            $studentNotes = $booking->bookingNotes->where('note_type', 'student_note')->first();
            $studentReviewNote = $booking->bookingNotes->where('note_type', 'student_review')->first();
            
            // Get student review for this session
            $studentIds = $this->getStudentIds($guardian);
            $studentReview = $session?->teacherReviews->whereIn('student_id', $studentIds)->first();
            
            // Determine if this booking is for the guardian or a child
            $isGuardianBooking = $booking->student_id === $guardian->id;
            $studentName = $isGuardianBooking ? $guardian->name . ' (You)' : $student->name;
            
            return [
                'id' => $booking->id,
                'booking_uuid' => $booking->booking_uuid,
                'title' => $subjectTemplate?->name ?? $subject?->name ?? 'Unknown Subject',
                'teacher' => $teacher?->name ?? 'Unknown Teacher',
                'student_name' => $studentName,
                'student_id' => $booking->student_id,
                'is_guardian_booking' => $isGuardianBooking,
                'teacher_id' => $teacher?->id,
                'teacher_avatar' => $this->getTeacherInitials($teacher?->name ?? 'Unknown Teacher'),
                'subject' => $subjectTemplate?->name ?? $subject?->name ?? 'Unknown Subject',
                'date' => $booking->booking_date->format('d M Y'),
                'time' => $booking->start_time->format('H:i') . ' - ' . $booking->end_time->format('H:i'),
                'status' => ucfirst($booking->status),
                'imageUrl' => null,
                'meetingUrl' => $session?->zoom_join_url,
                'teacherNotes' => $teacherNotes?->note ?? null,
                'studentNotes' => $studentNotes?->note ?? null,
                'studentReview' => $studentReview ? [
                    'rating' => $studentReview->rating,
                    'comment' => $studentReview->comment,
                ] : null,
                'studentReviewNote' => $studentReviewNote?->note ?? null,
                'history' => $booking->history->first() ? [
                    'status' => $booking->history->first()->status,
                    'updated_at' => $booking->history->first()->updated_at->format('d M Y H:i'),
                ] : null,
            ];
        })->values()->toArray();
    }

    /**
     * Get dashboard stats for guardian
     */
    public function getDashboardStats(User $guardian): array
    {
        $bookings = $this->getFormattedBookings($guardian);
        
        return [
            'total_bookings' => $bookings['stats']['total'],
            'upcoming_bookings' => $bookings['stats']['upcoming'],
            'ongoing_bookings' => $bookings['stats']['ongoing'],
            'completed_bookings' => $bookings['stats']['completed'],
            'has_children' => $this->hasChildren($guardian),
            'can_book_for_self' => $this->canBookForSelf($guardian),
            'children_count' => $guardian->guardianProfile ? $guardian->guardianProfile->students->count() : 0,
        ];
    }

    /**
     * Get upcoming classes for dashboard widget
     */
    public function getUpcomingClassesForDashboard(User $guardian, int $limit = 5): array
    {
        $upcomingBookings = $this->getUpcomingBookings($guardian);
        
        return $upcomingBookings->take($limit)->map(function ($booking) {
            // Map booking status to component status
            // For upcoming classes, if they're scheduled and have a future date, they're confirmed
            $status = in_array($booking->status, ['approved', 'upcoming']) ? 'Confirmed' : 'Pending';
            
            // Format date and time
            $date = $booking->booking_date->format('l, F j, Y');
            $startTime = $booking->start_time->format('g:i A');
            $endTime = $booking->end_time->format('g:i A');
            $time = "{$startTime} - {$endTime}";
            
            // Get subject name and teacher name
            $subjectName = $booking->subject->template->name ?? 'Unknown Subject';
            $teacherName = $booking->teacher->name ?? 'Unknown Teacher';
            
            // Generate title (e.g., "Tajweed (Intermediate)")
            $title = $subjectName;
            
            // Get teacher avatar - only use actual image URLs, not initials
            $teacherAvatar = $booking->teacher->avatar ?? null;
            
            return [
                'id' => $booking->id,
                'title' => $title,
                'teacher' => $teacherName,
                'date' => $date,
                'time' => $time,
                'status' => $status,
                'imageUrl' => $teacherAvatar, // Only actual image URLs, null if no image
            ];
        })->toArray();
    }

    /**
     * Get ongoing classes for dashboard widget
     */
    public function getOngoingClassesForDashboard(User $guardian, int $limit = 5): array
    {
        $ongoingBookings = $this->getOngoingBookings($guardian);
        
        return $this->formatBookingsForFrontend(
            $ongoingBookings->take($limit),
            $guardian
        );
    }

    /**
     * Generate teacher initials from name
     */
    private function getTeacherInitials(string $name): string
    {
        $nameParts = explode(' ', $name);
        $initials = '';
        foreach ($nameParts as $part) {
            if (!empty($part)) {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }
        return $initials ?: 'UT'; // Default to 'UT' for Unknown Teacher
    }
}

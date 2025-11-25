<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\TeachingSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentSessionService
{
    /**
     * Get unified student session statistics.
     * This ensures all controllers use the same logic for calculating stats.
     */
    public function getStudentStats(int $studentId): array
    {
        // Get all bookings for the student
        $bookings = Booking::where('student_id', $studentId)->get();
        
        // Calculate stats using consistent logic
        $totalBookings = $bookings->count();
        
        $completedBookings = $bookings->filter(function ($booking) {
            return $booking->status === 'completed' || 
                   ($booking->booking_date < today() && 
                    in_array($booking->status, ['approved', 'upcoming']));
        })->count();
        
        $upcomingBookings = $bookings->filter(function ($booking) {
            return $booking->booking_date >= today() && 
                   in_array($booking->status, ['pending', 'approved', 'upcoming']);
        })->count();
        
        // Calculate ongoing sessions (sessions that are in progress)
        $ongoingBookings = $bookings->filter(function ($booking) {
            return $booking->teachingSession && 
                   in_array($booking->teachingSession->status, ['in_progress', 'ongoing']);
        })->count();
        
        return [
            'totalSessions' => $totalBookings,
            'completedSessions' => $completedBookings,
            'upcomingSessions' => $upcomingBookings,
            'ongoingSessions' => $ongoingBookings,
        ];
    }
    
    /**
     * Get student sessions with unified filtering and formatting.
     */
    public function getStudentSessions(int $studentId, string $filter = 'all', int $perPage = 10, int $page = 1): array
    {
        $query = Booking::with([
            'teacher.teacherProfile', 
            'subject.template', 
            'teachingSession', 
            'teachingSession.progress'
        ])
        ->where('student_id', $studentId)
        ->orderBy('booking_date', 'desc')
        ->orderBy('start_time', 'desc');
        
        // Apply filters based on booking status and date
        switch ($filter) {
            case 'completed':
                $query->where(function ($q) {
                    $q->where('status', 'completed')
                      ->orWhere(function ($subQ) {
                          $subQ->where('booking_date', '<', today())
                               ->whereIn('status', ['approved', 'upcoming']);
                      });
                });
                break;
            case 'upcoming':
                $query->where('booking_date', '>=', today())
                      ->whereIn('status', ['pending', 'approved', 'upcoming']);
                break;
            case 'ongoing':
                $query->whereHas('teachingSession', function ($q) {
                    $q->whereIn('status', ['in_progress', 'ongoing']);
                });
                break;
            case 'all':
            default:
                // Show all bookings
                break;
        }
        
        $bookings = $query->paginate($perPage, ['*'], 'page', $page);
        
        // Format bookings for frontend
        $formattedSessions = $bookings->getCollection()->map(function ($booking) {
            return $this->formatBookingForSessionList($booking);
        });
        
        $bookings->setCollection($formattedSessions);
        
        return [
            'sessions' => $bookings,
            'stats' => $this->getStudentStats($studentId),
        ];
    }
    
    /**
     * Get upcoming sessions for dashboard.
     */
    public function getUpcomingSessions(int $studentId, int $limit = 5): array
    {
        // Get approved teaching sessions
        $sessions = TeachingSession::with(['teacher', 'subject.template', 'booking'])
            ->where('student_id', $studentId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('session_date', '>=', Carbon::today())
            ->orderBy('session_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit($limit)
            ->get();
        
        // Get pending bookings that don't have teaching sessions yet
        $pendingBookings = Booking::with(['teacher', 'subject.template'])
            ->where('student_id', $studentId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('booking_date', '>=', Carbon::today())
            ->whereDoesntHave('teachingSession')
            ->orderBy('booking_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit($limit)
            ->get();
        
        $upcomingData = collect();
        
        // Process approved teaching sessions
        $upcomingData = $upcomingData->merge($sessions->map(function ($session) {
            return $this->formatSessionForList($session);
        }));
        
        // Process pending bookings
        $upcomingData = $upcomingData->merge($pendingBookings->map(function ($booking) {
            return $this->formatBookingForList($booking);
        }));
        
        // Sort by date and time
        return $upcomingData->sortBy([
            ['date', 'asc'],
            ['time', 'asc']
        ])->values()->toArray();
    }
    
    /**
     * Format session for list view.
     */
    private function formatSessionForList(TeachingSession $session): array
    {
        $subjectName = $session->subject?->template?->name ?? $session->subject?->name ?? 'Unknown Subject';
        
        // Calculate progress based on session progress or default values
        $progress = 0;
        if ($session->status === 'completed') {
            $progress = $session->progress?->completion_percentage ?? 100;
        } elseif ($session->status === 'in_progress') {
            $progress = $session->progress?->completion_percentage ?? 50;
        }
        
        // Get rating (could be from session feedback or teacher rating)
        $rating = $session->student_rating ?? $session->teacher?->teacherProfile?->average_rating ?? 0;
        
        return [
            'id' => $session->id,
            'session_uuid' => $session->session_uuid,
            'title' => $subjectName,
            'teacher' => $session->teacher?->name ?? 'Teacher Not Found',
            'teacher_avatar' => $session->teacher?->avatar ?? '/assets/images/default-avatar.jpg',
            'subject' => $subjectName,
            'date' => $session->session_date->format('M j, Y'),
            'time' => $session->start_time->format('g:i A') . ' - ' . $session->end_time->format('g:i A'),
            'duration' => $this->calculateDuration($session->start_time, $session->end_time),
            'status' => ucfirst($session->status),
            'meeting_link' => $session->zoom_join_url ?? $session->meeting_link,
            'completion_date' => $session->completion_date?->format('M j, Y'),
            'progress' => $progress,
            'rating' => round($rating, 1),
            'imageUrl' => $this->getSubjectImage($subjectName),
        ];
    }
    
    /**
     * Format booking for list view.
     */
    private function formatBookingForList(Booking $booking): array
    {
        $subjectName = $booking->subject?->template?->name ?? $booking->subject?->name ?? 'Unknown Subject';
        
        return [
            'id' => $booking->id,
            'session_uuid' => $booking->booking_uuid,
            'title' => $subjectName,
            'teacher' => $booking->teacher?->name ?? 'Teacher Not Found',
            'teacher_avatar' => $booking->teacher?->avatar ?? '/assets/images/default-avatar.jpg',
            'subject' => $subjectName,
            'date' => $booking->booking_date->format('M j, Y'),
            'time' => $booking->start_time->format('g:i A') . ' - ' . $booking->end_time->format('g:i A'),
            'duration' => $this->calculateDuration($booking->start_time, $booking->end_time),
            'status' => ucfirst($booking->status),
            'meeting_link' => null, // No meeting link for pending bookings
            'completion_date' => null,
            'progress' => 0,
            'rating' => 0,
            'imageUrl' => $this->getSubjectImage($subjectName),
        ];
    }
    
    /**
     * Format booking for session list view (same format as TeachingSession).
     */
    private function formatBookingForSessionList(Booking $booking): array
    {
        $subjectName = $booking->subject?->template?->name ?? $booking->subject?->name ?? 'Unknown Subject';
        
        // Get progress from teaching session if it exists
        $progress = 0;
        if ($booking->teachingSession) {
            if ($booking->teachingSession->status === 'completed') {
                $progress = $booking->teachingSession->progress?->completion_percentage ?? 100;
            } elseif ($booking->teachingSession->status === 'in_progress') {
                $progress = $booking->teachingSession->progress?->completion_percentage ?? 50;
            }
        }
        
        // Get rating from teaching session or teacher profile
        $rating = 0;
        if ($booking->teachingSession) {
            $rating = $booking->teachingSession->student_rating ?? $booking->teacher?->teacherProfile?->average_rating ?? 0;
        }
        
        // Get meeting link from teaching session if it exists
        $meetingLink = null;
        if ($booking->teachingSession && $booking->teachingSession->zoom_join_url) {
            $meetingLink = $booking->teachingSession->zoom_join_url;
        }
        
        // Determine completion date
        $completionDate = null;
        if ($booking->teachingSession && $booking->teachingSession->completion_date) {
            $completionDate = $booking->teachingSession->completion_date->format('M j, Y');
        }
        
        return [
            'id' => $booking->id,
            'session_uuid' => $booking->booking_uuid,
            'title' => $subjectName,
            'teacher' => $booking->teacher?->name ?? 'Teacher Not Found',
            'teacher_id' => $booking->teacher_id,
            'teacher_avatar' => $booking->teacher?->avatar ?? '/assets/images/default-avatar.jpg',
            'subject' => $subjectName,
            'date' => $booking->booking_date->format('M j, Y'),
            'time' => $booking->start_time->format('g:i A') . ' - ' . $booking->end_time->format('g:i A'),
            'duration' => $this->calculateDuration($booking->start_time, $booking->end_time),
            'status' => ucfirst($booking->status),
            'meeting_link' => $meetingLink,
            'completion_date' => $completionDate,
            'progress' => $progress,
            'rating' => $rating,
            'imageUrl' => $this->getSubjectImage($subjectName),
        ];
    }
    
    /**
     * Calculate session duration in minutes.
     */
    private function calculateDuration($startTime, $endTime): int
    {
        return (int) $startTime->diffInMinutes($endTime);
    }
    
    /**
     * Get subject-specific image based on subject name.
     */
    private function getSubjectImage(string $subjectName): string
    {
        $subject = strtolower($subjectName);
        
        if (str_contains($subject, 'tajweed') || str_contains($subject, 'quran')) {
            return '/assets/images/quran.png';
        }
        
        if (str_contains($subject, 'hadith')) {
            return '/assets/images/landing/Beautiful_quran.png';
        }
        
        if (str_contains($subject, 'fiqh')) {
            return '/assets/images/about/Arabic_Calligraphy_Asy_Syifa-removebg-preview 1.png';
        }
        
        if (str_contains($subject, 'arabic')) {
            return '/assets/images/landing/arabic-calligraphy.png';
        }
        
        // Default Quran image
        return '/assets/images/quran.png';
    }
}

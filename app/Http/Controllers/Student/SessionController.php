<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TeachingSession;
use App\Models\Booking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    /**
     * Display a listing of the student's sessions.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filter = $request->get('filter', 'all'); // all, completed, upcoming
        
        // Handle upcoming filter specially to include both sessions and pending bookings
        if ($filter === 'upcoming') {
            return $this->getUpcomingSessions($user->id, $request);
        }
        
        // Build base query for other filters
        $query = TeachingSession::with(['teacher.teacherProfile', 'subject.template', 'booking', 'progress'])
            ->where('student_id', $user->id)
            ->orderBy('session_date', 'desc')
            ->orderBy('start_time', 'desc');
        
        // Apply filters
        switch ($filter) {
            case 'completed':
                $query->where('status', 'completed');
                break;
            case 'all':
            default:
                // Total class tab should only show completed and ongoing classes (exclude upcoming)
                $query->whereIn('status', ['completed', 'ongoing', 'in_progress']);
                break;
        }
        
        $sessions = $query->paginate(10);
        
        // Format sessions for frontend
        $formattedSessions = $sessions->getCollection()->map(function ($session) {
            return $this->formatSessionForList($session);
        });
        
        $sessions->setCollection($formattedSessions);
        
        // Get stats for the header
        $stats = $this->getStudentStats($user->id);
        
        return Inertia::render('student/sessions/index', [
            'sessions' => $sessions,
            'filter' => $filter,
            'stats' => $stats,
        ]);
    }
    
    /**
     * Calculate session duration in minutes.
     */
    private function calculateDuration($startTime, $endTime): int
    {
        return $startTime->diffInMinutes($endTime);
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
        
        // Get subject-specific image
        $imageUrl = $this->getSubjectImage($subjectName);
        
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
            'imageUrl' => $imageUrl,
        ];
    }
    
    /**
     * Get upcoming sessions including both teaching sessions and pending bookings.
     */
    private function getUpcomingSessions(int $studentId, Request $request): Response
    {
        // Get approved teaching sessions
        $sessions = TeachingSession::with(['teacher.teacherProfile', 'subject.template', 'booking', 'progress'])
            ->where('student_id', $studentId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('session_date', '>=', now()->toDateString())
            ->orderBy('session_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
        
        // Get pending bookings that don't have teaching sessions yet
        $pendingBookings = Booking::with(['teacher.teacherProfile', 'subject.template'])
            ->where('student_id', $studentId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('booking_date', '>=', now()->toDateString())
            ->whereDoesntHave('teachingSession')
            ->orderBy('booking_date', 'asc')
            ->orderBy('start_time', 'asc')
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
        $sortedData = $upcomingData->sortBy([
            ['date', 'asc'],
            ['time', 'asc']
        ])->values();
        
        // Create pagination manually
        $perPage = 10;
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedData = $sortedData->slice($offset, $perPage);
        
        $total = $sortedData->count();
        $lastPage = ceil($total / $perPage);
        
        // Create pagination object
        $pagination = (object) [
            'data' => $paginatedData->toArray(),
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
            'links' => $this->generatePaginationLinks($currentPage, $lastPage, $request),
        ];
        
        // Get stats for the header
        $stats = $this->getStudentStats($studentId);
        
        return Inertia::render('student/sessions/index', [
            'sessions' => $pagination,
            'filter' => 'upcoming',
            'stats' => $stats,
        ]);
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
     * Generate pagination links.
     */
    private function generatePaginationLinks(int $currentPage, int $lastPage, Request $request): array
    {
        $links = [];
        
        // Previous link
        if ($currentPage > 1) {
            $links[] = [
                'url' => $request->fullUrlWithQuery(['page' => $currentPage - 1]),
                'label' => '&laquo; Previous',
                'active' => false,
            ];
        }
        
        // Page links
        for ($i = 1; $i <= $lastPage; $i++) {
            $links[] = [
                'url' => $request->fullUrlWithQuery(['page' => $i]),
                'label' => (string) $i,
                'active' => $i === $currentPage,
            ];
        }
        
        // Next link
        if ($currentPage < $lastPage) {
            $links[] = [
                'url' => $request->fullUrlWithQuery(['page' => $currentPage + 1]),
                'label' => 'Next &raquo;',
                'active' => false,
            ];
        }
        
        return $links;
    }

    /**
     * Get student statistics.
     */
    private function getStudentStats(int $studentId): array
    {
        // Get total sessions count
        $totalSessions = TeachingSession::where('student_id', $studentId)->count();
        
        // Get completed sessions count
        $completedSessions = TeachingSession::where('student_id', $studentId)
            ->where('status', 'completed')
            ->count();
        
        // Get upcoming sessions count (scheduled or confirmed teaching sessions)
        $upcomingSessions = TeachingSession::where('student_id', $studentId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('session_date', '>=', now()->toDateString())
            ->count();
        
        // Get pending bookings count (bookings without teaching sessions)
        $pendingBookings = Booking::where('student_id', $studentId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('booking_date', '>=', now()->toDateString())
            ->whereDoesntHave('teachingSession')
            ->count();
        
        return [
            'totalSessions' => $totalSessions,
            'completedSessions' => $completedSessions,
            'upcomingSessions' => $upcomingSessions + $pendingBookings, // Include both sessions and pending bookings
        ];
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
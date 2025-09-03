<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TeachingSession;
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
        
        // Build base query
        $query = TeachingSession::with(['teacher.teacherProfile', 'subject.template', 'booking', 'progress'])
            ->where('student_id', $user->id)
            ->orderBy('session_date', 'desc')
            ->orderBy('start_time', 'desc');
        
        // Apply filters
        switch ($filter) {
            case 'completed':
                $query->where('status', 'completed');
                break;
            case 'upcoming':
                $query->whereIn('status', ['scheduled', 'confirmed'])
                      ->where('session_date', '>=', now()->toDateString());
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
        
        // Get upcoming sessions count (scheduled or confirmed)
        $upcomingSessions = TeachingSession::where('student_id', $studentId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('session_date', '>=', now()->toDateString())
            ->count();
        
        return [
            'totalSessions' => $totalSessions,
            'completedSessions' => $completedSessions,
            'upcomingSessions' => $upcomingSessions,
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
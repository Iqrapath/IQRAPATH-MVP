<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\TeachingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TeacherStatsService
{
    /**
     * Get teacher statistics for dashboard.
     */
    public function getTeacherStats(int $teacherId): array
    {
        // Get active students (students who have booked sessions with this teacher)
        $activeStudents = Booking::where('teacher_id', $teacherId)
            ->whereHas('teachingSession', function ($query) {
                $query->whereIn('status', ['scheduled', 'confirmed', 'in_progress', 'completed']);
            })
            ->distinct('student_id')
            ->count('student_id');
        
        // Get upcoming sessions (sessions scheduled for today or later)
        $upcomingSessions = TeachingSession::where('teacher_id', $teacherId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('session_date', '>=', Carbon::today())
            ->count();
        
        // Get pending requests (bookings waiting for teacher approval)
        $pendingRequests = Booking::where('teacher_id', $teacherId)
            ->where('status', 'pending')
            ->count();
        
        return [
            'activeStudents' => $activeStudents,
            'upcomingSessions' => $upcomingSessions,
            'pendingRequests' => $pendingRequests,
        ];
    }
    
    /**
     * Get recent activity for teacher dashboard.
     */
    public function getRecentActivity(int $teacherId, int $limit = 5): array
    {
        // Get recent bookings
        $recentBookings = Booking::with(['student', 'subject.template'])
            ->where('teacher_id', $teacherId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($booking) {
                // Get subject name with proper fallback
                $subjectName = 'Unknown Subject';
                if ($booking->subject) {
                    if ($booking->subject->template) {
                        $subjectName = $booking->subject->template->name;
                    } elseif (isset($booking->subject->name)) {
                        $subjectName = $booking->subject->name;
                    }
                }

                return [
                    'id' => $booking->id,
                    'type' => 'booking',
                    'student_name' => $booking->student->name,
                    'subject' => $subjectName,
                    'date' => $booking->booking_date->format('M j, Y'),
                    'time' => $booking->start_time->format('g:i A'),
                    'status' => ucfirst($booking->status),
                    'created_at' => $booking->created_at->diffForHumans(),
                ];
            });
        
        return $recentBookings->toArray();
    }

    /**
     * Get upcoming sessions for teacher dashboard.
     */
    public function getUpcomingSessions(int $teacherId): array
    {
        return TeachingSession::with(['student', 'subject.template', 'student.studentProfile'])
            ->where('teacher_id', $teacherId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('session_date', '>=', now()->toDateString())
            ->orderBy('session_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($session) {
                // Get subject name with proper fallback
                $subjectName = 'Unknown Subject';
                if ($session->subject) {
                    if ($session->subject->template) {
                        $subjectName = $session->subject->template->name;
                    } elseif (isset($session->subject->name)) {
                        $subjectName = $session->subject->name;
                    }
                }

                return [
                    'id' => $session->id,
                    'session_uuid' => $session->session_uuid,
                    'student_name' => $session->student->name,
                    'student_avatar' => $session->student->studentProfile->profile_picture ?? null,
                    'subject' => $subjectName,
                    'date' => $session->session_date->format('Y-m-d'),
                    'start_time' => $session->start_time->format('H:i:s'),
                    'end_time' => $session->end_time->format('H:i:s'),
                    'time' => $session->start_time->format('g:i A') . ' - ' . $session->end_time->format('g:i A'),
                    'status' => ucfirst($session->status),
                    'meeting_platform' => $session->meeting_platform,
                    'meeting_link' => $session->meeting_link,
                    'zoom_join_url' => $session->zoom_join_url,
                    'google_meet_link' => $session->google_meet_link,
                    'student_notes' => $session->student_notes,
                    'teacher_notes' => $session->teacher_notes,
                ];
            })
            ->toArray();
    }

    /**
     * Get active students for teacher sessions page.
     */
    public function getActiveStudents(int $teacherId): array
    {
        return User::with(['studentProfile'])
            ->where('role', 'student')
            ->whereHas('studentBookings', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId)
                    ->whereHas('teachingSession', function ($sessionQuery) {
                        $sessionQuery->whereIn('status', ['scheduled', 'confirmed', 'in_progress', 'completed']);
                    });
            })
            ->get()
            ->map(function ($student) use ($teacherId) {
                // Get completed sessions count
                $completedSessions = TeachingSession::where('teacher_id', $teacherId)
                    ->where('student_id', $student->id)
                    ->where('status', 'completed')
                    ->count();

                // Get total sessions count
                $totalSessions = TeachingSession::where('teacher_id', $teacherId)
                    ->where('student_id', $student->id)
                    ->count();

                // Calculate progress percentage
                $progress = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0;

                // Calculate average rating from teacher reviews
                $averageRating = DB::table('teacher_reviews')
                    ->where('teacher_id', $teacherId)
                    ->where('student_id', $student->id)
                    ->avg('rating');
                
                // Ensure averageRating is numeric
                $averageRating = is_numeric($averageRating) ? (float) $averageRating : null;

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'avatar' => $student->studentProfile->profile_picture ?? null,
                    'level' => $student->studentProfile->grade_level ?? 'Beginner',
                    'sessionsCompleted' => $completedSessions,
                    'progress' => $progress,
                    'rating' => $averageRating ? round((float) $averageRating, 1) : 0,
                    'lastActive' => $student->last_active_at ? $student->last_active_at->diffForHumans() : 'Never',
                ];
            })
            ->toArray();
    }

    /**
     * Get upcoming sessions for teacher sessions page.
     */
    public function getUpcomingSessionsForSessionsPage(int $teacherId): array
    {
        return TeachingSession::with(['student', 'student.studentProfile', 'subject.template', 'teacher'])
            ->where('teacher_id', $teacherId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('session_date', '>=', now())
            ->orderBy('session_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($session) {
                // Get subject name with proper fallback
                $subjectName = 'Unknown Subject';
                if ($session->subject) {
                    if ($session->subject->template) {
                        $subjectName = $session->subject->template->name;
                    } elseif (isset($session->subject->name)) {
                        $subjectName = $session->subject->name;
                    }
                }

                // Calculate duration
                $duration = '1 Hour'; // Default
                if ($session->start_time && $session->end_time) {
                    $start = \Carbon\Carbon::parse($session->start_time);
                    $end = \Carbon\Carbon::parse($session->end_time);
                    $minutes = $start->diffInMinutes($end);
                    $hours = floor($minutes / 60);
                    $remainingMinutes = $minutes % 60;
                    
                    if ($hours > 0) {
                        $duration = $hours . ' Hour' . ($hours > 1 ? 's' : '');
                        if ($remainingMinutes > 0) {
                            $duration .= ' ' . $remainingMinutes . ' Min';
                        }
                    } else {
                        $duration = $minutes . ' Minutes';
                    }
                }

                return [
                    'id' => $session->id,
                    'session_uuid' => $session->session_uuid,
                    'student_name' => $session->student->name,
                    'student_avatar' => $session->student->studentProfile->profile_picture ?? null,
                    'subject' => $subjectName,
                    'teacher_name' => $session->teacher->name ?? 'Teacher',
                    'teacher_avatar' => $session->teacher->avatar ?? null,
                    'date' => $session->session_date->format('Y-m-d'),
                    'start_time' => $session->start_time->format('H:i:s'),
                    'end_time' => $session->end_time->format('H:i:s'),
                    'time' => $session->start_time->format('g:i A') . ' - ' . $session->end_time->format('g:i A'),
                    'duration' => $duration,
                    'status' => $session->status,
                    'meeting_platform' => $session->meeting_platform,
                    'meeting_link' => $session->meeting_link,
                    'zoom_join_url' => $session->zoom_join_url,
                    'google_meet_link' => $session->google_meet_link,
                    'student_notes' => $session->student_notes,
                    'teacher_notes' => $session->teacher_notes,
                    'student_rating' => $session->student_rating,
                    'teacher_rating' => $session->teacher_rating,
                ];
            })
            ->toArray();
    }

    /**
     * Get pending requests for teacher sessions page.
     */
    public function getPendingRequests(int $teacherId): array
    {
        return Booking::with(['student.studentProfile', 'subject.template'])
            ->where('teacher_id', $teacherId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($booking) {
                // Get subject name with proper fallback
                $subjectName = 'Unknown Subject';
                if ($booking->subject) {
                    if ($booking->subject->template) {
                        $subjectName = $booking->subject->template->name;
                    } elseif (isset($booking->subject->name)) {
                        $subjectName = $booking->subject->name;
                    }
                }

                return [
                    'id' => $booking->id,
                    'student' => [
                        'name' => $booking->student->name,
                        'avatar' => $booking->student->studentProfile->profile_picture ?? null,
                    ],
                    'note' => $booking->notes ?? 'No additional notes provided.',
                    'subject' => $subjectName,
                    'requestedDate' => $booking->booking_date->format('Y-m-d'),
                    'requestedTime' => $booking->start_time->format('g:i A') . ' - ' . $booking->end_time->format('g:i A'),
                    'status' => 'pending',
                ];
            })
            ->toArray();
    }

    /**
     * Get past sessions for teacher sessions page.
     */
    public function getPastSessionsForSessionsPage(int $teacherId): array
    {
        return TeachingSession::with(['student', 'student.studentProfile', 'subject.template', 'teacher'])
            ->where('teacher_id', $teacherId)
            ->whereIn('status', ['completed', 'cancelled', 'no-show'])
            ->orderBy('session_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function ($session) {
                // Get subject name with proper fallback
                $subjectName = 'Unknown Subject';
                if ($session->subject) {
                    if ($session->subject->template) {
                        $subjectName = $session->subject->template->name;
                    } elseif (isset($session->subject->name)) {
                        $subjectName = $session->subject->name;
                    }
                }

                // Calculate duration
                $duration = '1 Hour'; // Default
                if ($session->start_time && $session->end_time) {
                    $start = \Carbon\Carbon::parse($session->start_time);
                    $end = \Carbon\Carbon::parse($session->end_time);
                    $minutes = $start->diffInMinutes($end);
                    $hours = floor($minutes / 60);
                    $remainingMinutes = $minutes % 60;
                    
                    if ($hours > 0) {
                        $duration = $hours . ' Hour' . ($hours > 1 ? 's' : '');
                        if ($remainingMinutes > 0) {
                            $duration .= ' ' . $remainingMinutes . ' Min';
                        }
                    } else {
                        $duration = $minutes . ' Minutes';
                    }
                }

                return [
                    'id' => $session->id,
                    'session_uuid' => $session->session_uuid,
                    'date' => $session->session_date->format('Y-m-d'),
                    'start_time' => $session->start_time->format('g:i A'),
                    'end_time' => $session->end_time->format('g:i A'),
                    'subject' => $subjectName,
                    'student_name' => $session->student->name,
                    'student' => $session->student->name,
                    'student_avatar' => $session->student->studentProfile->profile_picture ?? null,
                    'status' => $session->status,
                    'duration' => $duration,
                    'rating' => $session->student_rating ?? $session->teacher_rating,
                    'feedback' => $session->student_notes ?? $session->teacher_notes,
                    'student_rating' => $session->student_rating,
                    'teacher_rating' => $session->teacher_rating,
                    'student_review' => $session->student_notes,
                    'teacher_review' => $session->teacher_notes,
                ];
            })
            ->toArray();
    }

    /**
     * Get detailed student profile data for modal display.
     */
    public function getDetailedStudentProfile(int $teacherId, int $studentId): array
    {
        $student = User::with(['studentProfile', 'studentBookings' => function ($query) use ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }])
        ->where('id', $studentId)
        ->where('role', 'student')
        ->first();

        if (!$student) {
            return [];
        }

        // Get completed sessions count
        $completedSessions = TeachingSession::where('teacher_id', $teacherId)
            ->where('student_id', $student->id)
            ->where('status', 'completed')
            ->count();

        // Get total sessions count
        $totalSessions = TeachingSession::where('teacher_id', $teacherId)
            ->where('student_id', $student->id)
            ->count();

        // Calculate progress percentage
        $progress = $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0;

        // Calculate average rating from teacher reviews
        $averageRating = DB::table('teacher_reviews')
            ->where('teacher_id', $teacherId)
            ->where('student_id', $student->id)
            ->avg('rating');
        
        $averageRating = is_numeric($averageRating) ? round((float) $averageRating, 1) : 0;

        // Get upcoming sessions
        $upcomingSessions = TeachingSession::with(['subject.template'])
            ->where('teacher_id', $teacherId)
            ->where('student_id', $student->id)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('session_date', '>=', now())
            ->orderBy('session_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($session) {
                // Get subject name with proper fallback
                $subjectName = 'Unknown Subject';
                if ($session->subject) {
                    if ($session->subject->template) {
                        $subjectName = $session->subject->template->name;
                    } elseif (isset($session->subject->name)) {
                        $subjectName = $session->subject->name;
                    }
                }

                return [
                    'time' => $session->start_time->format('g:i A'),
                    'endTime' => $session->end_time->format('g:i A'),
                    'day' => $session->session_date->format('l'),
                    'lesson' => $subjectName,
                    'status' => ucfirst($session->status),
                ];
            })
            ->toArray();

        // Get student's subjects from their bookings
        $subjects = $student->studentBookings()
            ->with('subject.template')
            ->get()
            ->pluck('subject.template.name')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return [
            'id' => $student->id,
            'name' => $student->name,
            'avatar' => $student->studentProfile->profile_picture ?? null,
            'level' => $student->studentProfile->grade_level ?? 'Beginner',
            'sessionsCompleted' => $completedSessions,
            'progress' => $progress,
            'rating' => $averageRating,
            'isOnline' => $student->last_active_at && $student->last_active_at->isAfter(now()->subMinutes(5)),
            // Additional fields for StudentProfileModal
            'age' => $student->studentProfile->age ?? null,
            'gender' => $student->studentProfile->gender ?? null,
            'location' => $student->studentProfile->location ?? null,
            'joinedDate' => $student->created_at->toISOString(),
            'preferredLearningTime' => $student->studentProfile->preferred_learning_time ?? null,
            'subjects' => $subjects,
            'learningGoal' => $student->studentProfile->learning_goal ?? null,
            'availableDays' => $student->studentProfile->available_days ? json_decode($student->studentProfile->available_days, true) : null,
            'upcomingSessions' => $upcomingSessions,
        ];
    }
}

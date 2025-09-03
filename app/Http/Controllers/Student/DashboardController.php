<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\SubjectTemplates;
use App\Models\TeachingSession;
use App\Models\TeacherProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the student dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;
        $learningSchedules = $user->studentLearningSchedules ?? collect();
        
        // Get student statistics
        $stats = $this->getStudentStats($user->id);
        
        // Get upcoming sessions for the student
        $upcomingSessions = $this->getUpcomingSessions($user->id);
        
        // Get recommended teachers
        $recommendedTeachers = $this->getRecommendedTeachers($user->id);
        
        return Inertia::render('student/dashboard', [
            'studentProfile' => $studentProfile,
            'guardian' => $studentProfile?->guardian,
            'user' => $user,
            'learningSchedules' => $learningSchedules,
            'availableSubjects' => SubjectTemplates::where('is_active', true)
                                                  ->orderBy('name')
                                                  ->pluck('name')
                                                  ->toArray(),
            'showOnboarding' => $request->session()->get('showOnboarding', false),
            // Real data for dashboard components
            'studentStats' => $stats,
            'upcomingSessions' => $upcomingSessions,
            'recommendedTeachers' => $recommendedTeachers,
        ]);
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
            ->where('session_date', '>=', Carbon::today())
            ->count();
        
        return [
            'totalSessions' => $totalSessions,
            'completedSessions' => $completedSessions,
            'upcomingSessions' => $upcomingSessions,
        ];
    }
    
    /**
     * Get upcoming sessions for the student.
     */
    private function getUpcomingSessions(int $studentId): array
    {
        $sessions = TeachingSession::with(['teacher', 'subject.template', 'booking'])
            ->where('student_id', $studentId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('session_date', '>=', Carbon::today())
            ->orderBy('session_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get();
        
        return $sessions->map(function ($session) {
            // Get subject name from subject template if available
            $subjectName = $session->subject?->template?->name ?? $session->subject?->name ?? 'Subject Not Found';
            
            return [
                'id' => $session->id,
                'title' => $subjectName,
                'teacher' => $session->teacher?->name ?? 'Teacher Not Found',
                'subject' => $subjectName,
                'date' => $session->session_date->format('l, F j, Y'),
                'time' => $session->start_time->format('g:i A') . ' - ' . $session->end_time->format('g:i A'),
                'status' => ucfirst($session->status),
                'imageUrl' => null, // Use initials instead of image
                'meetingUrl' => $session->zoom_join_url ?? $session->meeting_link,
                'session_uuid' => $session->session_uuid,
            ];
        })->toArray();
    }
    
    /**
     * Get recommended teachers for the student.
     */
    private function getRecommendedTeachers(int $studentId): array
    {
        // Get teachers with highest ratings and verified profiles
        $teachers = User::with(['teacherProfile.subjects.template'])
            ->where('role', 'teacher')
            ->whereHas('teacherProfile', function ($query) {
                $query->where('verified', true);
            })
            ->join('teacher_profiles', 'users.id', '=', 'teacher_profiles.user_id')
            ->orderBy('teacher_profiles.rating', 'desc')
            ->orderBy('teacher_profiles.reviews_count', 'desc')
            ->limit(6)
            ->get(['users.*']);
        
        return $teachers->map(function ($teacher) {
            $profile = $teacher->teacherProfile;
            
            // Get teacher's subjects from the profile's subjects
            $subjects = $profile->subjects->take(3)->map(function ($subject) {
                return $subject->template?->name ?? $subject->name ?? 'Unknown';
            })->filter()->implode(', ');
            
            return [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'subjects' => $subjects ?: 'Various Subjects',
                'location' => $teacher->location ?? 'Location Not Set',
                'rating' => $profile->rating ? (float) $profile->rating : 5.0,
                'price' => $profile->hourly_rate_ngn 
                    ? '₦' . number_format($profile->hourly_rate_ngn) . ' / session'
                    : '₦5,000 / session',
                'avatarUrl' => null, // Use initials instead of images
            ];
        })->toArray();
    }
}

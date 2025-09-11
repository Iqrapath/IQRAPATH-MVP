<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\SubjectTemplates;
use App\Models\TeachingSession;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\StudentSessionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private StudentSessionService $sessionService
    ) {}

    /**
     * Display the student dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;
        $learningSchedules = $user->studentLearningSchedules ?? collect();
        
        // Get student statistics using unified service
        $stats = $this->sessionService->getStudentStats($user->id);
        
        // Get upcoming sessions for the student using unified service
        $upcomingSessions = $this->sessionService->getUpcomingSessions($user->id);
        
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

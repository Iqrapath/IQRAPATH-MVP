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
        // Get all bookings for the student
        $bookings = Booking::where('student_id', $studentId)->get();
        
        // Get total bookings count
        $totalBookings = $bookings->count();
        
        // Get completed bookings count (same logic as my-bookings page)
        $completedBookings = $bookings->filter(function ($booking) {
            return $booking->status === 'completed' || 
                   ($booking->booking_date < today() && 
                    in_array($booking->status, ['approved', 'upcoming']));
        })->count();
        
        // Get upcoming bookings count (same logic as my-bookings page)
        $upcomingBookings = $bookings->filter(function ($booking) {
            return $booking->booking_date >= today() && 
                   in_array($booking->status, ['pending', 'approved', 'upcoming']);
        })->count();
        
        return [
            'totalSessions' => $totalBookings,
            'completedSessions' => $completedBookings,
            'upcomingSessions' => $upcomingBookings,
        ];
    }
    
    /**
     * Get upcoming sessions for the student.
     * This includes both approved teaching sessions and pending bookings.
     */
    private function getUpcomingSessions(int $studentId): array
    {
        // Get approved teaching sessions
        $sessions = TeachingSession::with(['teacher', 'subject.template', 'booking'])
            ->where('student_id', $studentId)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->where('session_date', '>=', Carbon::today())
            ->orderBy('session_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
        
        // Get pending bookings that don't have teaching sessions yet
        $pendingBookings = Booking::with(['teacher', 'subject.template'])
            ->where('student_id', $studentId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('booking_date', '>=', Carbon::today())
            ->whereDoesntHave('teachingSession')
            ->orderBy('booking_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
        
        $upcomingData = collect();
        
        // Process approved teaching sessions
        $upcomingData = $upcomingData->merge($sessions->map(function ($session) {
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
                'type' => 'session'
            ];
        }));
        
        // Process pending bookings
        $upcomingData = $upcomingData->merge($pendingBookings->map(function ($booking) {
            // Get subject name from subject template if available
            $subjectName = $booking->subject?->template?->name ?? $booking->subject?->name ?? 'Subject Not Found';
            
            return [
                'id' => $booking->id,
                'title' => $subjectName,
                'teacher' => $booking->teacher?->name ?? 'Teacher Not Found',
                'subject' => $subjectName,
                'date' => $booking->booking_date->format('l, F j, Y'),
                'time' => $booking->start_time->format('g:i A') . ' - ' . $booking->end_time->format('g:i A'),
                'status' => ucfirst($booking->status),
                'imageUrl' => null, // Use initials instead of image
                'meetingUrl' => null, // No meeting URL for pending bookings
                'session_uuid' => $booking->booking_uuid,
                'type' => 'booking'
            ];
        }));
        
        // Sort by date and time, then limit to 5
        return $upcomingData
            ->sortBy([
                ['date', 'asc'],
                ['time', 'asc']
            ])
            ->take(5)
            ->values()
            ->toArray();
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

<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\TeacherStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private TeacherStatsService $statsService
    ) {}

    /**
     * Display the teacher dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $teacherProfile = $user->teacherProfile;
        
        // Get teacher statistics
        $stats = $this->statsService->getTeacherStats($user->id);
        
        // Get upcoming sessions
        $upcomingSessions = $this->statsService->getUpcomingSessions($user->id);
        
        // Check if we should show verification success modal (only once)
        $showVerificationSuccess = false;
        
        // Only check if teacher is verified and we haven't shown the modal yet
        if ($teacherProfile && $teacherProfile->verified && !$request->session()->has('verification_modal_shown')) {
            $verificationRequest = $teacherProfile->verificationRequests()
                ->where('status', 'verified')
                ->latest()
                ->first();
            
            // Show modal if verified within last 7 days (but only once per session)
            if ($verificationRequest && $verificationRequest->reviewed_at) {
                $showVerificationSuccess = $verificationRequest->reviewed_at->isAfter(now()->subDays(7));
                
                // Mark that we've shown the modal in this session
                if ($showVerificationSuccess) {
                    $request->session()->put('verification_modal_shown', true);
                }
            }
        }
        
        return Inertia::render('teacher/dashboard', [
            'teacherProfile' => $teacherProfile,
            'user' => $user,
            'stats' => $stats,
            'upcomingSessions' => $upcomingSessions,
            'showVerificationSuccess' => $showVerificationSuccess,
        ]);
    }
}

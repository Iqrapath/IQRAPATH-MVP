<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the teacher dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $teacherProfile = $user->teacherProfile;
        
        // Check if teacher was recently verified (within last 24 hours)
        $recentlyVerified = false;
        if ($teacherProfile && $teacherProfile->verified) {
            $verificationRequest = $teacherProfile->verificationRequests()
                ->where('status', 'verified')
                ->latest()
                ->first();
            
            if ($verificationRequest && $verificationRequest->reviewed_at) {
                $recentlyVerified = $verificationRequest->reviewed_at->isAfter(now()->subDay());
            }
        }
        
        return Inertia::render('teacher/dashboard', [
            'teacherProfile' => $teacherProfile,
            'user' => $user,
            'showVerificationSuccess' => $recentlyVerified,
        ]);
    }
}

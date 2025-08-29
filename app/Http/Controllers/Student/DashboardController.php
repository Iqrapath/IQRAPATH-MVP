<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SubjectTemplates;
use Illuminate\Http\Request;
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
        ]);
    }
}

<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
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
        return Inertia::render('student/dashboard', [
            'studentProfile' => $request->user()->studentProfile,
            'guardian' => $request->user()->studentProfile?->guardian,
        ]);
    }
}

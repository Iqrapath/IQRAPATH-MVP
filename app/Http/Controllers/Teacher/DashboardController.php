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
        return Inertia::render('teacher/dashboard', [
            'teacherProfile' => $request->user()->teacherProfile,
        ]);
    }
}

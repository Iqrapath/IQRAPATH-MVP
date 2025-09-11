<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TeachingSession;
use App\Models\Booking;
use App\Services\StudentSessionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function __construct(
        private StudentSessionService $sessionService
    ) {}

    /**
     * Display a listing of the student's sessions.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filter = $request->get('filter', 'all'); // all, completed, upcoming, ongoing
        $page = $request->get('page', 1);
        
        // Use unified service to get sessions and stats
        $data = $this->sessionService->getStudentSessions($user->id, $filter, 10, $page);
        
        return Inertia::render('student/sessions/index', [
            'sessions' => $data['sessions'],
            'filter' => $filter,
            'stats' => $data['stats'],
        ]);
    }
    
}
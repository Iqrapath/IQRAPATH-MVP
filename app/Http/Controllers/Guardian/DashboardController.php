<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the guardian dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        return Inertia::render('guardian/dashboard', [
            'guardianProfile' => $user->guardianProfile,
            'students' => $user->guardianProfile?->students()->with('user')->get(),
        ]);
    }
}

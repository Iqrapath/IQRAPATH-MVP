<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnassignedController extends Controller
{
    /**
     * Display the waiting page for unassigned users.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('unassigned', [
            'user' => $request->user(),
        ]);
    }
}

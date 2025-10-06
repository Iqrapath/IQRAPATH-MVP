<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserLoggedIn;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        \Log::info('Login attempt', ['request_data' => $request->all()]);
        
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        
        // Dispatch the UserLoggedIn event
        event(new UserLoggedIn($user));

        // Check if there's a redirect parameter
        $redirectUrl = $request->get('redirect');
        \Log::info('Login redirect check', ['redirect_url' => $redirectUrl, 'user_role' => $user->role]);
        
        if ($redirectUrl) {
            \Log::info('Redirecting to', ['url' => $redirectUrl]);
            return redirect($redirectUrl);
        }

        // Redirect based on role
        if ($user->isUnassigned()) {
            \Log::info('Redirecting unassigned user to unassigned page');
            return redirect()->route('unassigned');
        }

        $roleRedirect = match ($user->role) {
            'super-admin' => redirect()->route('admin.dashboard'),
            'teacher' => redirect()->route('teacher.dashboard'),
            'student' => redirect()->route('student.dashboard'),
            'guardian' => redirect()->route('guardian.dashboard'),
            default => redirect()->route('dashboard'),
        };
        
        \Log::info('Redirecting based on role', ['role' => $user->role]);
        return $roleRedirect;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

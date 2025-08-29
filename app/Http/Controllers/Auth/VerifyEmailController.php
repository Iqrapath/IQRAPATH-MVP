<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        if ($user->hasVerifiedEmail()) {
            return $this->redirectToOnboarding($user);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->redirectToOnboarding($user);
    }

    /**
     * Redirect user to appropriate onboarding based on their role.
     */
    private function redirectToOnboarding($user): RedirectResponse
    {
        // Teachers go directly to teacher onboarding
        if ($user->role === 'teacher') {
            return redirect()->route('onboarding.teacher');
        }
        
        // Students/Guardians go to role selection
        if ($user->role === null) {
            return redirect()->route('onboarding');
        }
        
        // Users with assigned roles go to their specific onboarding
        return match ($user->role) {
            'student' => redirect()->route('onboarding.student'),
            'guardian' => redirect()->route('onboarding.guardian'),
            'super-admin' => redirect()->route('admin.dashboard'),
            default => redirect()->route('dashboard'),
        };
    }
}

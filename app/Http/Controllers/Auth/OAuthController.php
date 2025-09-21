<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OnboardingController;
use App\Models\User;
use App\Services\FinancialService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    public function __construct(
        private FinancialService $financialService,
        private NotificationService $notificationService
    ) {}

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        $intendedRole = $request->query('role', 'student-guardian');
        
        return Socialite::driver('google')
            ->with(['state' => $intendedRole])
            ->redirect();
    }

    /**
     * Redirect to Facebook OAuth
     */
    public function redirectToFacebook(Request $request): RedirectResponse
    {
        $intendedRole = $request->query('role', 'student-guardian');
        
        return Socialite::driver('facebook')
            ->with(['state' => $intendedRole])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver('google')->user();
            $intendedRole = $request->query('state', 'student-guardian');
            
            return $this->handleOAuthCallback($socialUser, 'google', $intendedRole);
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['oauth' => 'Google authentication failed. Please try again.']);
        }
    }

    /**
     * Handle Facebook OAuth callback
     */
    public function handleFacebookCallback(Request $request): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver('facebook')->user();
            $intendedRole = $request->query('state', 'student-guardian');
            
            return $this->handleOAuthCallback($socialUser, 'facebook', $intendedRole);
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['oauth' => 'Facebook authentication failed. Please try again.']);
        }
    }

    /**
     * Handle OAuth callback for both providers
     */
    private function handleOAuthCallback($socialUser, string $provider, string $intendedRole): RedirectResponse
    {
        return DB::transaction(function () use ($socialUser, $provider, $intendedRole) {
            // Check if user already exists
            $existingUser = User::where('email', $socialUser->getEmail())->first();

            if ($existingUser) {
                // User exists, log them in
                Auth::login($existingUser);
                
                return $this->redirectBasedOnRole($existingUser);
            }

            // Create new user
            $user = $this->createUserFromSocial($socialUser, $provider, $intendedRole);
            
            // Log the user in
            Auth::login($user);

            // Dispatch user registered event
            event(new UserRegistered($user));

            // Redirect based on intended role
            return $this->redirectBasedOnIntendedRole($user, $intendedRole);
        });
    }

    /**
     * Create user from social provider data
     */
    private function createUserFromSocial($socialUser, string $provider, string $intendedRole): User
    {
        $user = User::create([
            'name' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt(Str::random(32)), // Random password for OAuth users
            'role' => $this->determineUserRole($intendedRole),
            'status' => 'active',
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
        ]);

        // Create appropriate profile and wallet
        $this->createUserProfileAndWallet($user);

        return $user;
    }

    /**
     * Determine user role based on intended role
     */
    private function determineUserRole(string $intendedRole): string
    {
        return match ($intendedRole) {
            'teacher' => 'teacher',
            'student-guardian' => 'unassigned', // Will be assigned in role selection
            default => 'unassigned',
        };
    }

    /**
     * Create user profile and wallet based on role
     */
    private function createUserProfileAndWallet(User $user): void
    {
        if ($user->role === 'teacher') {
            // Use OnboardingController's method for consistency
            $onboardingController = new OnboardingController($this->financialService);
            $onboardingController->createUserProfileAndWallet($user, 'teacher');
        }
        // For unassigned users, profile and wallet will be created after role selection
    }

    /**
     * Redirect based on user's current role
     */
    private function redirectBasedOnRole(User $user): RedirectResponse
    {
        return match ($user->role) {
            'super-admin' => redirect()->route('admin.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            'teacher' => redirect()->route('teacher.dashboard'),
            'student' => redirect()->route('student.dashboard'),
            'guardian' => redirect()->route('guardian.dashboard'),
            'unassigned' => redirect()->route('onboarding.role-selection'),
            default => redirect()->route('dashboard'),
        };
    }

    /**
     * Redirect based on intended role during registration
     */
    private function redirectBasedOnIntendedRole(User $user, string $intendedRole): RedirectResponse
    {
        return match ($intendedRole) {
            'teacher' => redirect()->route('onboarding.teacher'),
            'student-guardian' => redirect()->route('onboarding.role-selection'),
            default => redirect()->route('onboarding.role-selection'),
        };
    }
}

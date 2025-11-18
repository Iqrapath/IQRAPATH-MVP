<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Events\UserRegistered;
use App\Exceptions\OAuthException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use GuzzleHttp\Exception\ClientException;

class OAuthController extends Controller
{
    public function __construct(
        private OAuthService $oauthService
    ) {}

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        // Use 'any' as default to indicate user can choose any role
        // 'teacher' = teacher registration
        // 'student-guardian' = student-guardian registration
        // 'any' = login page (can choose any role)
        $intendedRole = $request->query('role', 'any');
        
        // Store return URL if provided (for account linking from settings)
        if ($request->query('return_url')) {
            $returnUrl = $request->query('return_url');
            $isLinking = Auth::check(); // User is already logged in = account linking
            
            session([
                'oauth_return_url' => $returnUrl,
                'oauth_is_linking' => $isLinking,
                'oauth_linking_user_id' => $isLinking ? Auth::id() : null
            ]);
            session()->save(); // Force save session
            
            Log::info('OAuth return URL stored', [
                'return_url' => $returnUrl,
                'is_linking' => $isLinking,
                'user_id' => Auth::id(),
                'session_id' => session()->getId()
            ]);
        }
        
        // Generate and store secure state token
        $state = $this->generateSecureState($intendedRole);
        
        return Socialite::driver('google')
            ->with(['state' => $state])
            ->redirect();
    }

    /**
     * Redirect to Facebook OAuth
     */
    public function redirectToFacebook(Request $request): RedirectResponse
    {
        // Use 'any' as default to indicate user can choose any role
        $intendedRole = $request->query('role', 'any');
        
        // Store return URL if provided (for account linking from settings)
        if ($request->query('return_url')) {
            $returnUrl = $request->query('return_url');
            $isLinking = Auth::check(); // User is already logged in = account linking
            
            session([
                'oauth_return_url' => $returnUrl,
                'oauth_is_linking' => $isLinking,
                'oauth_linking_user_id' => $isLinking ? Auth::id() : null
            ]);
            session()->save(); // Force save session
            
            Log::info('OAuth return URL stored', [
                'return_url' => $returnUrl,
                'is_linking' => $isLinking,
                'user_id' => Auth::id(),
                'session_id' => session()->getId()
            ]);
        }
        
        // Generate and store secure state token
        $state = $this->generateSecureState($intendedRole);
        
        return Socialite::driver('facebook')
            ->with(['state' => $state])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            // Validate and decode state parameter
            $state = $request->query('state');
            $intendedRole = $this->validateAndDecodeState($state, 'google');
            
            $socialUser = Socialite::driver('google')->stateless()->user();
            
            return $this->handleOAuthCallback($socialUser, 'google', $intendedRole);
        } catch (InvalidStateException $e) {
            Log::warning('OAuth state mismatch', [
                'provider' => 'google',
                'error' => $e->getMessage(),
            ]);
            return $this->handleOAuthError('Authentication session expired. Please try again.');
        } catch (ClientException $e) {
            Log::error('Google OAuth provider error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            return $this->handleOAuthError('Google authentication is temporarily unavailable. Please try again later or use email/password login.');
        } catch (OAuthException $e) {
            return $this->handleOAuthError($e->getMessage());
        } catch (ValidationException $e) {
            return $this->handleOAuthError($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Unexpected Google OAuth error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleOAuthError('Google authentication failed. Please try again.');
        }
    }

    /**
     * Handle Facebook OAuth callback
     */
    public function handleFacebookCallback(Request $request): RedirectResponse
    {
        try {
            // Validate and decode state parameter
            $state = $request->query('state');
            $intendedRole = $this->validateAndDecodeState($state, 'facebook');
            
            $socialUser = Socialite::driver('facebook')->stateless()->user();
            
            return $this->handleOAuthCallback($socialUser, 'facebook', $intendedRole);
        } catch (InvalidStateException $e) {
            Log::warning('OAuth state mismatch', [
                'provider' => 'facebook',
                'error' => $e->getMessage(),
            ]);
            return $this->handleOAuthError('Authentication session expired. Please try again.');
        } catch (ClientException $e) {
            Log::error('Facebook OAuth provider error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            return $this->handleOAuthError('Facebook authentication is temporarily unavailable. Please try again later or use email/password login.');
        } catch (OAuthException $e) {
            return $this->handleOAuthError($e->getMessage());
        } catch (ValidationException $e) {
            return $this->handleOAuthError($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Unexpected Facebook OAuth error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->handleOAuthError('Facebook authentication failed. Please try again.');
        }
    }

    /**
     * Handle OAuth callback for both providers
     */
    private function handleOAuthCallback($socialUser, string $provider, string $intendedRole): RedirectResponse
    {
        // Get linking context from session BEFORE processing
        $returnUrl = session('oauth_return_url');
        $isLinking = session('oauth_is_linking', false);
        $linkingUserId = session('oauth_linking_user_id');
        
        Log::info('OAuth callback started', [
            'return_url' => $returnUrl,
            'is_linking' => $isLinking,
            'linking_user_id' => $linkingUserId,
            'current_user_id' => Auth::id(),
            'session_id' => session()->getId()
        ]);
        
        // Process authentication through OAuthService
        $user = $this->oauthService->processAuthentication($socialUser, $provider, $intendedRole);

        // Only log in if this is NOT an account linking operation
        if (!$isLinking) {
            Auth::login($user);
            
            // Dispatch user registered event if new user
            if ($user->wasRecentlyCreated) {
                event(new UserRegistered($user));
                
                // Store intended role in session for role selection page
                session(['oauth_intended_role' => $intendedRole]);
            }
        } else {
            // Verify the linking user matches the authenticated user
            if ($linkingUserId !== $user->id) {
                Log::warning('OAuth linking user mismatch', [
                    'expected_user_id' => $linkingUserId,
                    'actual_user_id' => $user->id,
                    'provider' => $provider
                ]);
                
                $errorMessage = 'Account linking failed. The OAuth account belongs to a different user.';
                
                Log::info('Redirecting with error', [
                    'error' => $errorMessage,
                    'return_url' => $returnUrl
                ]);
                
                // Clear OAuth session data
                session()->forget(['oauth_return_url', 'oauth_is_linking', 'oauth_linking_user_id']);
                
                // Use query parameter as fallback since session might not persist
                return redirect($returnUrl . '?oauth_error=' . urlencode($errorMessage));
            }
        }
        
        Log::info('OAuth callback redirect check', [
            'return_url' => $returnUrl,
            'session_id' => session()->getId(),
            'user_id' => $user->id,
            'was_recently_created' => $user->wasRecentlyCreated,
            'is_linking' => $isLinking
        ]);
        
        // If there's a return URL (account linking from settings)
        if ($returnUrl && $isLinking) {
            $successMessage = ucfirst($provider) . ' account linked successfully!';
            
            // Clear OAuth session data
            session()->forget(['oauth_return_url', 'oauth_is_linking', 'oauth_linking_user_id']);
            
            // Use query parameter as fallback since session might not persist
            return redirect($returnUrl . '?oauth_success=' . urlencode($successMessage));
        }

        // Clear any leftover session data
        session()->forget(['oauth_return_url', 'oauth_is_linking', 'oauth_linking_user_id']);

        // Determine redirect route using centralized logic
        $route = $this->oauthService->determineRedirectRoute($user, $intendedRole);

        return redirect()->route($route);
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

    /**
     * Handle OAuth error and redirect appropriately
     */
    private function handleOAuthError(string $message): RedirectResponse
    {
        // Check if this was an account linking attempt
        $returnUrl = session('oauth_return_url');
        
        // Clear OAuth session data
        session()->forget(['oauth_return_url', 'oauth_is_linking', 'oauth_linking_user_id']);
        
        if ($returnUrl) {
            // Return to settings with flash error
            return redirect($returnUrl)->with('error', $message);
        }
        
        // Regular login attempt - return to login page with form error
        return redirect()->route('login')->withErrors(['oauth' => $message]);
    }

    /**
     * Generate secure state token
     */
    private function generateSecureState(string $intendedRole): string
    {
        // Create cryptographically secure state token
        $token = bin2hex(random_bytes(16));
        
        // Store state in session with expiration (5 minutes)
        session()->put("oauth_state_{$token}", [
            'intended_role' => $intendedRole,
            'expires_at' => now()->addMinutes(5)->timestamp,
        ]);
        
        return $token;
    }

    /**
     * Validate and decode state parameter
     */
    private function validateAndDecodeState(?string $state, string $provider): string
    {
        if (!$state) {
            Log::warning('OAuth state parameter missing', [
                'provider' => $provider,
                'ip' => request()->ip(),
            ]);
            
            throw new OAuthException('Invalid authentication request. Please try again.');
        }

        // Retrieve state from session
        $stateData = session()->get("oauth_state_{$state}");
        
        if (!$stateData) {
            Log::warning('OAuth state not found in session', [
                'provider' => $provider,
                'state' => $state,
                'ip' => request()->ip(),
            ]);
            
            throw new OAuthException('Authentication session expired. Please try again.');
        }

        // Check if state has expired
        if ($stateData['expires_at'] < now()->timestamp) {
            session()->forget("oauth_state_{$state}");
            
            Log::warning('OAuth state expired', [
                'provider' => $provider,
                'state' => $state,
                'ip' => request()->ip(),
            ]);
            
            throw new OAuthException('Authentication session expired. Please try again.');
        }

        // Validate intended role
        $intendedRole = $stateData['intended_role'] ?? 'any';
        $validRoles = ['teacher', 'student-guardian', 'any'];
        
        if (!in_array($intendedRole, $validRoles)) {
            Log::warning('Invalid intended role in OAuth state', [
                'provider' => $provider,
                'intended_role' => $intendedRole,
                'ip' => request()->ip(),
            ]);
            
            $intendedRole = 'any'; // Default to safe value (can choose any role)
        }

        // Remove state from session (one-time use)
        session()->forget("oauth_state_{$state}");

        return $intendedRole;
    }

}

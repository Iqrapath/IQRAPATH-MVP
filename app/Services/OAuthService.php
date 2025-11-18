<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\OAuthException;
use App\Http\Controllers\OnboardingController;
use App\Models\OAuthAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OAuthService
{
    public function __construct(
        private UnifiedWalletService $walletService,
        private NotificationService $notificationService,
        private AvatarService $avatarService
    ) {}

    /**
     * Process OAuth authentication
     * 
     * @param object $socialUser
     * @param string $provider
     * @param string $intendedRole
     * @param int|null $linkingUserId User ID if this is an account linking operation
     * @return User
     * @throws OAuthException
     * @throws ValidationException
     */
    public function processAuthentication(
        object $socialUser,
        string $provider,
        string $intendedRole,
        ?int $linkingUserId = null
    ): User {
        // Validate OAuth provider data
        $this->validateProviderData($socialUser);

        // Log OAuth initiation
        $this->logAuditEvent('initiated', [
            'provider' => $provider,
            'email' => $socialUser->getEmail(),
            'intended_role' => $intendedRole,
            'linking_user_id' => $linkingUserId,
        ]);

        return DB::transaction(function () use ($socialUser, $provider, $intendedRole, $linkingUserId) {
            try {
                // Find existing user or create new one
                $user = $this->findOrCreateUser($socialUser, $provider, $intendedRole, $linkingUserId);

                // Log successful authentication
                $this->logAuditEvent('callback_success', [
                    'provider' => $provider,
                    'email' => $socialUser->getEmail(),
                    'user_id' => $user->id,
                    'intended_role' => $intendedRole,
                    'is_new_user' => $user->wasRecentlyCreated,
                    'was_linking' => $linkingUserId !== null,
                ]);

                return $user;
            } catch (\Exception $e) {
                // Log error
                $this->logAuditEvent('error', [
                    'provider' => $provider,
                    'email' => $socialUser->getEmail(),
                    'error' => $e->getMessage(),
                    'intended_role' => $intendedRole,
                    'linking_user_id' => $linkingUserId,
                ]);

                throw $e;
            }
        });
    }

    /**
     * Validate OAuth provider data
     * 
     * @param object $socialUser
     * @return void
     * @throws ValidationException
     */
    private function validateProviderData(object $socialUser): void
    {
        if (!$socialUser->getEmail()) {
            throw ValidationException::withMessages([
                'oauth' => 'Email not provided by OAuth provider'
            ]);
        }

        if (!filter_var($socialUser->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'oauth' => 'Invalid email format from OAuth provider'
            ]);
        }

        if (!$socialUser->getId()) {
            throw ValidationException::withMessages([
                'oauth' => 'Provider ID not available'
            ]);
        }

        if (!$socialUser->getName()) {
            throw ValidationException::withMessages([
                'oauth' => 'Name not provided by OAuth provider'
            ]);
        }
    }

    /**
     * Find existing user or create new one
     * 
     * @param object $socialUser
     * @param string $provider
     * @param string $intendedRole
     * @param int|null $linkingUserId User ID if this is an account linking operation
     * @return User
     * @throws OAuthException
     */
    private function findOrCreateUser(
        object $socialUser,
        string $provider,
        string $intendedRole,
        ?int $linkingUserId = null
    ): User {
        // If this is an account linking operation, use the specified user
        if ($linkingUserId) {
            $linkingUser = User::find($linkingUserId);
            
            if (!$linkingUser) {
                throw new OAuthException('User account not found for linking.');
            }
            
            // CRITICAL: Validate that OAuth email matches the user's email
            if ($linkingUser->email !== $socialUser->getEmail()) {
                $this->logAuditEvent('email_mismatch', [
                    'provider' => $provider,
                    'user_email' => $linkingUser->email,
                    'oauth_email' => $socialUser->getEmail(),
                    'user_id' => $linkingUserId,
                ]);
                
                throw new OAuthException(
                    "Email mismatch: Your {$provider} account email ({$socialUser->getEmail()}) " .
                    "does not match your account email ({$linkingUser->email}). " .
                    "Please use a {$provider} account with the same email address."
                );
            }
            
            // Check if this provider is already linked to another account
            $providerUser = User::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();
            
            if ($providerUser && $providerUser->id !== $linkingUserId) {
                throw new OAuthException(
                    "This {$provider} account is already linked to another user account."
                );
            }
            
            // Link the provider to the existing user
            return $this->handleEmailCollision($linkingUser, $socialUser, $provider);
        }
        
        // Check if user already exists by email
        $existingUser = User::where('email', $socialUser->getEmail())->first();

        if ($existingUser) {
            // Handle email collision
            return $this->handleEmailCollision($existingUser, $socialUser, $provider);
        }

        // Download and cache avatar
        $avatarPath = null;
        if ($socialUser->getAvatar()) {
            $avatarPath = $this->avatarService->downloadOAuthAvatar(
                $socialUser->getAvatar(),
                $provider,
                $socialUser->getId()
            );
        }

        // Create new user
        $user = User::create([
            'name' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt(Str::random(32)), // Random password for OAuth users
            'role' => $this->determineUserRole($intendedRole),
            'account_status' => 'active',
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar' => $avatarPath ?? $socialUser->getAvatar(), // Use local path or fallback to URL
        ]);

        // Initialize user resources (profile, wallet)
        $this->initializeUserResources($user, $this->determineUserRole($intendedRole));

        return $user;
    }

    /**
     * Handle email collision with existing account
     * 
     * @param User $existingUser
     * @param object $socialUser
     * @param string $provider
     * @return User
     * @throws OAuthException
     */
    private function handleEmailCollision(
        User $existingUser,
        object $socialUser,
        string $provider
    ): User {
        // Check if user already has OAuth provider linked
        if ($existingUser->provider && $existingUser->provider !== $provider) {
            // Provider mismatch - user trying to login with different provider
            $this->logAuditEvent('provider_mismatch', [
                'provider' => $provider,
                'existing_provider' => $existingUser->provider,
                'email' => $socialUser->getEmail(),
                'user_id' => $existingUser->id,
            ]);

            throw new OAuthException(
                "This email is registered with {$existingUser->provider}. " .
                "Please use {$existingUser->provider} to login, or use your password."
            );
        }

        // Link OAuth provider to existing account
        if (!$existingUser->provider) {
            $this->linkProvider($existingUser, $provider, $socialUser->getId());
            
            // Verify email since OAuth provider has verified it
            if (!$existingUser->email_verified_at) {
                $existingUser->update(['email_verified_at' => now()]);
            }
            
            // Download and update avatar if not set
            if (!$existingUser->avatar && $socialUser->getAvatar()) {
                $avatarPath = $this->avatarService->downloadOAuthAvatar(
                    $socialUser->getAvatar(),
                    $provider,
                    $socialUser->getId()
                );
                
                if ($avatarPath) {
                    $existingUser->update(['avatar' => $avatarPath]);
                } else {
                    // Fallback to URL if download fails
                    $existingUser->update(['avatar' => $socialUser->getAvatar()]);
                }
            }

            $this->logAuditEvent('account_linked', [
                'provider' => $provider,
                'email' => $socialUser->getEmail(),
                'user_id' => $existingUser->id,
            ]);
        }

        // Update last OAuth login timestamp and ensure email is verified
        $existingUser->update([
            'last_active_at' => now(),
            'email_verified_at' => $existingUser->email_verified_at ?? now(),
        ]);

        return $existingUser;
    }

    /**
     * Link OAuth provider to existing user
     * 
     * @param User $user
     * @param string $provider
     * @param string $providerId
     * @return void
     */
    private function linkProvider(
        User $user,
        string $provider,
        string $providerId
    ): void {
        $user->update([
            'provider' => $provider,
            'provider_id' => $providerId,
        ]);
    }

    /**
     * Initialize user resources (profile, wallet)
     * 
     * @param User $user
     * @param string $role
     * @return void
     * @throws \Exception
     */
    private function initializeUserResources(
        User $user,
        string $role
    ): void {
        // Only initialize resources for assigned roles
        if ($role === 'unassigned') {
            return;
        }

        try {
            // Use OnboardingController's method for consistency
            $onboardingController = new OnboardingController($this->walletService);
            $onboardingController->createUserProfileAndWallet($user, $role);
        } catch (\Exception $e) {
            Log::error('Failed to initialize user resources', [
                'user_id' => $user->id,
                'role' => $role,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to initialize user profile and wallet: ' . $e->getMessage());
        }
    }

    /**
     * Determine user role from intended role
     * 
     * @param string $intendedRole
     * @return string
     */
    private function determineUserRole(string $intendedRole): string
    {
        // Validate and sanitize intended role
        $validRoles = ['teacher', 'student-guardian', 'any'];
        
        if (!in_array($intendedRole, $validRoles)) {
            Log::warning('Invalid intended role provided', [
                'intended_role' => $intendedRole,
            ]);
            return 'unassigned';
        }

        return match ($intendedRole) {
            'teacher' => 'teacher',
            'student-guardian', 'any' => 'unassigned', // Will be assigned in role selection
            default => 'unassigned',
        };
    }

    /**
     * Log OAuth audit event
     * 
     * @param string $event
     * @param array $data
     * @return void
     */
    private function logAuditEvent(
        string $event,
        array $data
    ): void {
        try {
            OAuthAuditLog::create([
                'user_id' => $data['user_id'] ?? null,
                'event' => $event,
                'provider' => $data['provider'] ?? null,
                'provider_id' => $data['provider_id'] ?? null,
                'email' => $data['email'] ?? null,
                'intended_role' => $data['intended_role'] ?? null,
                'metadata' => json_encode(array_diff_key($data, array_flip([
                    'user_id', 'provider', 'provider_id', 'email', 'intended_role'
                ]))),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log OAuth audit event', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine redirect route for authenticated user
     * 
     * @param User $user
     * @param string $intendedRole
     * @return string
     */
    public function determineRedirectRoute(User $user, string $intendedRole): string
    {
        // For new users, redirect based on intended role
        if ($user->wasRecentlyCreated) {
            return match ($intendedRole) {
                'teacher' => 'onboarding.teacher',
                'student-guardian' => 'onboarding.role-selection',
                default => 'onboarding.role-selection',
            };
        }

        // For existing users, redirect based on current role
        return match ($user->role) {
            'super-admin', 'admin' => 'admin.dashboard',
            'teacher' => 'teacher.dashboard',
            'student' => 'student.dashboard',
            'guardian' => 'guardian.dashboard',
            'unassigned', null => 'onboarding.role-selection',
            default => 'dashboard',
        };
    }

    /**
     * Log rate limit violation
     * 
     * @param string $provider
     * @return void
     */
    public static function logRateLimitViolation(string $provider): void
    {
        try {
            OAuthAuditLog::create([
                'user_id' => null,
                'event' => 'rate_limit_exceeded',
                'provider' => $provider,
                'provider_id' => null,
                'email' => null,
                'intended_role' => null,
                'metadata' => json_encode([
                    'message' => 'OAuth callback rate limit exceeded',
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            Log::warning('OAuth rate limit exceeded', [
                'provider' => $provider,
                'ip' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log rate limit violation', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

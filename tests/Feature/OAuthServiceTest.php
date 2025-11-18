<?php

use App\Exceptions\OAuthException;
use App\Models\OAuthAuditLog;
use App\Models\User;
use App\Services\OAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->oauthService = app(OAuthService::class);
});

// Helper function to create mock social user
function createMockSocialUser(?string $email, ?string $id, ?string $name, ?string $avatar = null): object
{
    return new class($email, $id, $name, $avatar) {
        public function __construct(
            private ?string $email,
            private ?string $id,
            private ?string $name,
            private ?string $avatar
        ) {}
        
        public function getEmail(): ?string { return $this->email; }
        public function getId(): ?string { return $this->id; }
        public function getName(): ?string { return $this->name; }
        public function getAvatar(): ?string { return $this->avatar; }
    };
}

describe('OAuthService - Provider Data Validation', function () {
    it('validates provider data correctly', function () {
        $invalidUser = createMockSocialUser(null, '12345', 'Test User');
        
        expect(fn() => $this->oauthService->processAuthentication($invalidUser, 'google', 'teacher'))
            ->toThrow(ValidationException::class);
    });
    
    it('rejects invalid email format', function () {
        $invalidUser = createMockSocialUser('not-an-email', '12345', 'Test User');
        
        expect(fn() => $this->oauthService->processAuthentication($invalidUser, 'google', 'teacher'))
            ->toThrow(ValidationException::class);
    });
    
    it('rejects missing provider ID', function () {
        $invalidUser = createMockSocialUser('test@example.com', null, 'Test User');
        
        expect(fn() => $this->oauthService->processAuthentication($invalidUser, 'google', 'teacher'))
            ->toThrow(ValidationException::class);
    });
    
    it('rejects missing name', function () {
        $invalidUser = createMockSocialUser('test@example.com', '12345', null);
        
        expect(fn() => $this->oauthService->processAuthentication($invalidUser, 'google', 'teacher'))
            ->toThrow(ValidationException::class);
    });
});

describe('OAuthService - Email Collision Handling', function () {
    it('handles email collision with different provider', function () {
        $existingUser = User::factory()->create([
            'email' => 'test@example.com',
            'provider' => 'google',
            'provider_id' => '12345'
        ]);
        
        $socialUser = createMockSocialUser('test@example.com', '67890', 'Test User');
        
        expect(fn() => $this->oauthService->processAuthentication($socialUser, 'facebook', 'student'))
            ->toThrow(OAuthException::class);
            
        // Verify audit log was created
        $auditLog = OAuthAuditLog::where('event', 'provider_mismatch')->first();
        expect($auditLog)->not->toBeNull();
        expect($auditLog->provider)->toBe('facebook');
        expect($auditLog->email)->toBe('test@example.com');
    });
    
    it('links provider to existing password-only account', function () {
        $existingUser = User::factory()->create([
            'email' => 'test@example.com',
            'provider' => null,
            'provider_id' => null,
            'email_verified_at' => null,
        ]);
        
        $socialUser = createMockSocialUser('test@example.com', '12345', 'Test User');
        
        $user = $this->oauthService->processAuthentication($socialUser, 'google', 'student');
        
        expect($user->id)->toBe($existingUser->id);
        expect($user->provider)->toBe('google');
        expect($user->provider_id)->toBe('12345');
        expect($user->email_verified_at)->not->toBeNull();
        
        // Verify audit log
        $auditLog = OAuthAuditLog::where('event', 'account_linked')->first();
        expect($auditLog)->not->toBeNull();
    });
    
    it('allows login with same provider', function () {
        $existingUser = User::factory()->create([
            'email' => 'test@example.com',
            'provider' => 'google',
            'provider_id' => '12345',
            'role' => 'student',
        ]);
        
        $socialUser = createMockSocialUser('test@example.com', '12345', 'Test User');
        
        $user = $this->oauthService->processAuthentication($socialUser, 'google', 'student');
        
        expect($user->id)->toBe($existingUser->id);
        expect($user->provider)->toBe('google');
    });
});

describe('OAuthService - User Creation', function () {
    it('creates new user with teacher role', function () {
        $socialUser = createMockSocialUser('newteacher@example.com', '12345', 'New Teacher');
        
        $user = $this->oauthService->processAuthentication($socialUser, 'google', 'teacher');
        
        expect($user)->toBeInstanceOf(User::class);
        expect($user->email)->toBe('newteacher@example.com');
        expect($user->role)->toBe('teacher');
        expect($user->provider)->toBe('google');
        expect($user->provider_id)->toBe('12345');
        expect($user->email_verified_at)->not->toBeNull();
        expect($user->account_status)->toBe('active');
        
        // Verify profile and wallet were created
        expect($user->teacherProfile)->not->toBeNull();
        expect($user->teacherWallet)->not->toBeNull();
        expect($user->earnings)->not->toBeNull();
    });
    
    it('creates new user with unassigned role for student-guardian', function () {
        $socialUser = createMockSocialUser('newstudent@example.com', '67890', 'New Student');
        
        $user = $this->oauthService->processAuthentication($socialUser, 'google', 'student-guardian');
        
        expect($user->role)->toBe('unassigned');
        expect($user->email_verified_at)->not->toBeNull();
    });
    
    it('creates new user with unassigned role for any', function () {
        $socialUser = createMockSocialUser('anyuser@example.com', '11111', 'Any User');
        
        $user = $this->oauthService->processAuthentication($socialUser, 'google', 'any');
        
        expect($user->role)->toBe('unassigned');
    });
});

describe('OAuthService - Transaction Atomicity', function () {
    it('rolls back user creation if profile creation fails', function () {
        // Mock the OnboardingController to throw an exception
        $this->mock(\App\Http\Controllers\OnboardingController::class, function ($mock) {
            $mock->shouldReceive('createUserProfileAndWallet')
                ->andThrow(new \Exception('Profile creation failed'));
        });
        
        $socialUser = createMockSocialUser('failtest@example.com', '99999', 'Fail Test');
        
        try {
            $this->oauthService->processAuthentication($socialUser, 'google', 'teacher');
        } catch (\Exception $e) {
            // Expected to fail
        }
        
        // Verify no user was created
        $user = User::where('email', 'failtest@example.com')->first();
        expect($user)->toBeNull();
        
        // Verify error was logged
        $auditLog = OAuthAuditLog::where('event', 'error')
            ->where('email', 'failtest@example.com')
            ->first();
        expect($auditLog)->not->toBeNull();
    });
});

describe('OAuthService - Audit Logging', function () {
    it('logs OAuth initiation', function () {
        $socialUser = createMockSocialUser('audit@example.com', '55555', 'Audit Test');
        
        $this->oauthService->processAuthentication($socialUser, 'google', 'teacher');
        
        $auditLog = OAuthAuditLog::where('event', 'initiated')
            ->where('email', 'audit@example.com')
            ->first();
            
        expect($auditLog)->not->toBeNull();
        expect($auditLog->provider)->toBe('google');
        expect($auditLog->intended_role)->toBe('teacher');
    });
    
    it('logs successful callback', function () {
        $socialUser = createMockSocialUser('success@example.com', '66666', 'Success Test');
        
        $user = $this->oauthService->processAuthentication($socialUser, 'google', 'teacher');
        
        $auditLog = OAuthAuditLog::where('event', 'callback_success')
            ->where('email', 'success@example.com')
            ->first();
            
        expect($auditLog)->not->toBeNull();
        expect($auditLog->user_id)->toBe($user->id);
    });
});

describe('OAuthService - Redirect Logic', function () {
    it('redirects new teacher to onboarding', function () {
        $user = User::factory()->create([
            'role' => 'teacher',
        ]);
        $user->wasRecentlyCreated = true;
        
        $route = $this->oauthService->determineRedirectRoute($user, 'teacher');
        
        expect($route)->toBe('onboarding.teacher');
    });
    
    it('redirects new student-guardian to role selection', function () {
        $user = User::factory()->create([
            'role' => 'unassigned',
        ]);
        $user->wasRecentlyCreated = true;
        
        $route = $this->oauthService->determineRedirectRoute($user, 'student-guardian');
        
        expect($route)->toBe('onboarding.role-selection');
    });
    
    it('redirects existing student to dashboard', function () {
        $user = User::factory()->create([
            'role' => 'student',
        ]);
        $user->wasRecentlyCreated = false;
        
        $route = $this->oauthService->determineRedirectRoute($user, 'any');
        
        expect($route)->toBe('student.dashboard');
    });
    
    it('redirects unassigned user to role selection', function () {
        $user = User::factory()->create([
            'role' => 'unassigned',
        ]);
        $user->wasRecentlyCreated = false;
        
        $route = $this->oauthService->determineRedirectRoute($user, 'any');
        
        expect($route)->toBe('onboarding.role-selection');
    });
});


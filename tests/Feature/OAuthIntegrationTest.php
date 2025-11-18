<?php

use App\Models\OAuthAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

describe('OAuth Integration - Complete Flow', function () {
    it('completes full OAuth registration flow for teacher', function () {
        // Mock Socialite
        $socialiteUser = new SocialiteUser();
        $socialiteUser->id = '12345';
        $socialiteUser->email = 'newteacher@example.com';
        $socialiteUser->name = 'New Teacher';
        $socialiteUser->avatar = 'https://example.com/avatar.jpg';
        
        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($socialiteUser);
        
        // Mock HTTP for avatar download
        Http::fake([
            'example.com/*' => Http::response(file_get_contents(public_path('images/default-avatar.png')), 200, [
                'Content-Type' => 'image/png'
            ])
        ]);
        
        // Simulate OAuth callback with teacher role
        $response = $this->get(route('auth.google.callback', [
            'state' => session()->put('oauth_state_test123', [
                'intended_role' => 'teacher',
                'expires_at' => now()->addMinutes(5)->timestamp,
            ]) && 'test123'
        ]));
        
        // Verify user created
        $user = User::where('email', 'newteacher@example.com')->first();
        expect($user)->toBeInstanceOf(User::class);
        expect($user->role)->toBe('teacher');
        expect($user->email_verified_at)->not->toBeNull();
        expect($user->provider)->toBe('google');
        
        // Verify profile created
        expect($user->teacherProfile)->not->toBeNull();
        
        // Verify wallet created
        expect($user->teacherWallet)->not->toBeNull();
        
        // Verify earnings created
        expect($user->earnings)->not->toBeNull();
        
        // Verify audit log created
        $auditLog = OAuthAuditLog::where('email', 'newteacher@example.com')
            ->where('event', 'callback_success')
            ->first();
        expect($auditLog)->not->toBeNull();
        
        // Verify redirect
        $response->assertRedirect(route('onboarding.teacher'));
    });
    
    it('completes OAuth login for existing user', function () {
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'provider' => 'google',
            'provider_id' => '12345',
            'role' => 'student',
        ]);
        
        // Mock Socialite
        $socialiteUser = new SocialiteUser();
        $socialiteUser->id = '12345';
        $socialiteUser->email = 'existing@example.com';
        $socialiteUser->name = 'Existing User';
        $socialiteUser->avatar = null;
        
        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($socialiteUser);
        
        // Simulate OAuth callback
        $response = $this->get(route('auth.google.callback', [
            'state' => session()->put('oauth_state_test456', [
                'intended_role' => 'any',
                'expires_at' => now()->addMinutes(5)->timestamp,
            ]) && 'test456'
        ]));
        
        // Verify user is authenticated
        $this->assertAuthenticatedAs($existingUser);
        
        // Verify redirect to dashboard
        $response->assertRedirect(route('student.dashboard'));
    });
    
    it('handles email collision with password account', function () {
        // Create password-only user
        $existingUser = User::factory()->create([
            'email' => 'password@example.com',
            'provider' => null,
            'provider_id' => null,
            'role' => 'student',
            'email_verified_at' => null,
        ]);
        
        // Mock Socialite
        $socialiteUser = new SocialiteUser();
        $socialiteUser->id = '99999';
        $socialiteUser->email = 'password@example.com';
        $socialiteUser->name = 'Password User';
        $socialiteUser->avatar = null;
        
        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($socialiteUser);
        
        // Simulate OAuth callback
        $response = $this->get(route('auth.google.callback', [
            'state' => session()->put('oauth_state_test789', [
                'intended_role' => 'any',
                'expires_at' => now()->addMinutes(5)->timestamp,
            ]) && 'test789'
        ]));
        
        // Verify provider was linked
        $user = $existingUser->fresh();
        expect($user->provider)->toBe('google');
        expect($user->provider_id)->toBe('99999');
        expect($user->email_verified_at)->not->toBeNull();
        
        // Verify audit log
        $auditLog = OAuthAuditLog::where('event', 'account_linked')
            ->where('email', 'password@example.com')
            ->first();
        expect($auditLog)->not->toBeNull();
    });
    
    it('prevents login with different provider', function () {
        // Create user with Google
        User::factory()->create([
            'email' => 'google@example.com',
            'provider' => 'google',
            'provider_id' => '11111',
            'role' => 'student',
        ]);
        
        // Mock Socialite with Facebook
        $socialiteUser = new SocialiteUser();
        $socialiteUser->id = '22222';
        $socialiteUser->email = 'google@example.com';
        $socialiteUser->name = 'Google User';
        $socialiteUser->avatar = null;
        
        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($socialiteUser);
        
        // Simulate OAuth callback with Facebook
        $response = $this->get(route('auth.facebook.callback', [
            'state' => session()->put('oauth_state_test000', [
                'intended_role' => 'any',
                'expires_at' => now()->addMinutes(5)->timestamp,
            ]) && 'test000'
        ]));
        
        // Verify error redirect
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('oauth');
        
        // Verify audit log
        $auditLog = OAuthAuditLog::where('event', 'provider_mismatch')
            ->where('email', 'google@example.com')
            ->first();
        expect($auditLog)->not->toBeNull();
    });
});

describe('OAuth Integration - Rate Limiting', function () {
    it('enforces rate limit on OAuth callbacks', function () {
        // Mock Socialite
        $socialiteUser = new SocialiteUser();
        $socialiteUser->id = '12345';
        $socialiteUser->email = 'ratelimit@example.com';
        $socialiteUser->name = 'Rate Limit Test';
        $socialiteUser->avatar = null;
        
        Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($socialiteUser);
        
        // Make 11 requests (limit is 10)
        for ($i = 0; $i < 11; $i++) {
            $state = 'test' . $i;
            session()->put("oauth_state_{$state}", [
                'intended_role' => 'any',
                'expires_at' => now()->addMinutes(5)->timestamp,
            ]);
            
            $response = $this->get(route('auth.google.callback', ['state' => $state]));
            
            if ($i < 10) {
                // First 10 should succeed
                expect($response->status())->not->toBe(429);
            } else {
                // 11th should be rate limited
                $response->assertRedirect(route('login'));
                $response->assertSessionHasErrors('oauth');
            }
        }
        
        // Verify rate limit audit log
        $auditLog = OAuthAuditLog::where('event', 'rate_limit_exceeded')->first();
        expect($auditLog)->not->toBeNull();
    });
});

describe('OAuth Integration - State Validation', function () {
    it('rejects expired state', function () {
        // Create expired state
        session()->put('oauth_state_expired', [
            'intended_role' => 'teacher',
            'expires_at' => now()->subMinutes(1)->timestamp, // Expired
        ]);
        
        $response = $this->get(route('auth.google.callback', ['state' => 'expired']));
        
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('oauth');
    });
    
    it('rejects missing state', function () {
        $response = $this->get(route('auth.google.callback'));
        
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('oauth');
    });
    
    it('rejects invalid state', function () {
        $response = $this->get(route('auth.google.callback', ['state' => 'invalid']));
        
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('oauth');
    });
});

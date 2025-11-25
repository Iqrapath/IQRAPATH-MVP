<?php

use App\Models\User;
use App\Models\AuthorizationAuditLog;
use App\Services\AuthorizationAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->auditService = app(AuthorizationAuditService::class);
});

// Feature: messaging-authorization-audit, Property 12: Authorization Failure Logging
describe('Authorization Failure Logging', function () {
    it('logs all authorization failures with complete information', function () {
        $user = User::factory()->create(['role' => 'student']);
        
        $this->auditService->logAuthorizationAttempt(
            $user,
            'view_conversation',
            'Conversation',
            123,
            false,
            'not_participant'
        );
        
        $log = AuthorizationAuditLog::where('user_id', $user->id)->first();
        
        expect($log)->not->toBeNull()
            ->and($log->user_id)->toBe($user->id)
            ->and($log->action)->toBe('view_conversation')
            ->and($log->resource_type)->toBe('Conversation')
            ->and($log->resource_id)->toBe(123)
            ->and($log->granted)->toBeFalse()
            ->and($log->reason)->toBe('not_participant')
            ->and($log->ip_address)->not->toBeNull()
            ->and($log->created_at)->not->toBeNull();
    });

    it('logs authorization attempts with user role metadata', function () {
        $user = User::factory()->create(['role' => 'teacher']);
        
        $this->auditService->logAuthorizationAttempt(
            $user,
            'send_message',
            'Message',
            456,
            true,
            null
        );
        
        $log = AuthorizationAuditLog::where('user_id', $user->id)->latest()->first();
        
        expect($log)->not->toBeNull()
            ->and($log->granted)->toBeTrue()
            ->and($log->metadata)->toHaveKey('user_role')
            ->and($log->metadata['user_role'])->toBe('teacher');
    });
});

// Feature: messaging-authorization-audit, Property 13: Role Violation Logging
describe('Role Violation Logging', function () {
    it('logs role-based messaging violations with sender and recipient roles', function () {
        $sender = User::factory()->create(['role' => 'student']);
        $recipient = User::factory()->create(['role' => 'teacher']);
        
        $this->auditService->logRoleViolation(
            $sender,
            $recipient,
            'no_active_booking'
        );
        
        $log = AuthorizationAuditLog::where('user_id', $sender->id)
            ->where('action', 'create_conversation')
            ->first();
        
        expect($log)->not->toBeNull()
            ->and($log->granted)->toBeFalse()
            ->and($log->reason)->toBe('no_active_booking')
            ->and($log->metadata)->toHaveKey('sender_role')
            ->and($log->metadata)->toHaveKey('recipient_role')
            ->and($log->metadata['sender_role'])->toBe('student')
            ->and($log->metadata['recipient_role'])->toBe('teacher')
            ->and($log->metadata['violation_type'])->toBe('role_restriction');
    });

    it('logs violations for different role combinations', function () {
        $sender = User::factory()->create(['role' => 'guardian']);
        $recipient = User::factory()->create(['role' => 'admin']);
        
        $this->auditService->logRoleViolation(
            $sender,
            $recipient,
            'role_mismatch'
        );
        
        $log = AuthorizationAuditLog::where('user_id', $sender->id)->latest()->first();
        
        expect($log)->not->toBeNull()
            ->and($log->metadata['sender_role'])->toBe('guardian')
            ->and($log->metadata['recipient_role'])->toBe('admin');
    });
});

// Feature: messaging-authorization-audit, Property 14: Suspicious Activity Detection
describe('Suspicious Activity Detection', function () {
    it('flags accounts with multiple authorization failures', function () {
        $user = User::factory()->create();
        
        // Create 6 failed authorization attempts
        for ($i = 0; $i < 6; $i++) {
            $this->auditService->logAuthorizationAttempt(
                $user,
                'view_conversation',
                'Conversation',
                $i + 1,
                false,
                'not_participant'
            );
        }
        
        // Check for suspicious pattern (threshold: 5 failures in 10 minutes)
        $isSuspicious = $this->auditService->checkForSuspiciousPattern($user, 5, 10);
        
        expect($isSuspicious)->toBeTrue();
        
        // Verify suspicious activity was logged
        $suspiciousLog = AuthorizationAuditLog::where('user_id', $user->id)
            ->where('action', 'suspicious_activity')
            ->first();
        
        expect($suspiciousLog)->not->toBeNull()
            ->and($suspiciousLog->metadata['flagged'])->toBeTrue();
    });

    it('does not flag accounts below the threshold', function () {
        $user = User::factory()->create();
        
        // Create only 3 failures (below threshold of 5)
        for ($i = 0; $i < 3; $i++) {
            $this->auditService->logAuthorizationAttempt(
                $user,
                'view_conversation',
                'Conversation',
                $i + 1,
                false,
                'not_participant'
            );
        }
        
        $isSuspicious = $this->auditService->checkForSuspiciousPattern($user, 5, 10);
        
        expect($isSuspicious)->toBeFalse();
    });
});

// Feature: messaging-authorization-audit, Property 15: Admin Override Logging
describe('Admin Override Logging', function () {
    it('logs admin overrides with justification', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $justification = 'Emergency access required for investigation';
        
        $this->auditService->logAdminOverride(
            $admin,
            'view_conversation',
            'Conversation',
            789,
            $justification
        );
        
        $log = AuthorizationAuditLog::where('user_id', $admin->id)->latest()->first();
        
        expect($log)->not->toBeNull()
            ->and($log->granted)->toBeTrue()
            ->and($log->reason)->toBe('admin_override')
            ->and($log->metadata)->toHaveKey('justification')
            ->and($log->metadata['justification'])->toBe($justification)
            ->and($log->metadata)->toHaveKey('override')
            ->and($log->metadata['override'])->toBeTrue()
            ->and($log->metadata['admin_role'])->toBe('admin');
    });

    it('logs overrides for super-admins', function () {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);
        
        $this->auditService->logAdminOverride(
            $superAdmin,
            'delete_message',
            'Message',
            999,
            'Policy violation removal'
        );
        
        $log = AuthorizationAuditLog::where('user_id', $superAdmin->id)->latest()->first();
        
        expect($log)->not->toBeNull()
            ->and($log->metadata['admin_role'])->toBe('super-admin')
            ->and($log->metadata['override'])->toBeTrue();
    });
});

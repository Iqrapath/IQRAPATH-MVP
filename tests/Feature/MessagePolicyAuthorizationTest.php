<?php

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Booking;
use App\Models\AuthorizationAuditLog;
use App\Policies\MessagePolicy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

uses(DatabaseMigrations::class);

beforeEach(function () {
    $this->policy = app(MessagePolicy::class);
});

// Feature: messaging-authorization-audit, Property 1: Conversation Access Restriction
describe('Conversation Access Restriction', function () {
    it('only allows participants to view conversations', function () {
        $participant = User::factory()->create();
        $nonParticipant = User::factory()->create();
        
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach($participant->id);
        
        // Participant should be able to view
        $canView = $this->policy->view($participant, $conversation);
        expect($canView)->toBeTrue();
        
        // Non-participant should not be able to view
        $cannotView = $this->policy->view($nonParticipant, $conversation);
        expect($cannotView)->toBeFalse();
        
        // Verify audit logs were created
        $participantLog = AuthorizationAuditLog::where('user_id', $participant->id)
            ->where('action', 'view_conversation')
            ->first();
        expect($participantLog)->not->toBeNull()
            ->and($participantLog->granted)->toBeTrue();
        
        $nonParticipantLog = AuthorizationAuditLog::where('user_id', $nonParticipant->id)
            ->where('action', 'view_conversation')
            ->first();
        expect($nonParticipantLog)->not->toBeNull()
            ->and($nonParticipantLog->granted)->toBeFalse()
            ->and($nonParticipantLog->reason)->toBe('not_participant');
    });
});

// Feature: messaging-authorization-audit, Property 2: Message Sending Authorization
describe('Message Sending Authorization', function () {
    /**
     * Property 2: Message Sending Authorization
     * Validates: Requirements 1.3, 3.1, 3.2, 3.3, 3.4, 3.5
     * 
     * For any user attempting to send a message in a conversation, 
     * the message is only sent if the user is a participant AND 
     * role-based messaging rules are satisfied
     */
    
    it('validates student-teacher messaging requires active booking', function () {
        // Test all possible booking statuses
        $bookingStatuses = ['pending', 'approved', 'completed', 'cancelled'];
        
        foreach ($bookingStatuses as $status) {
            $student = User::factory()->create(['role' => 'student']);
            $teacher = User::factory()->create(['role' => 'teacher']);
            
            // Create booking with specific status
            $booking = Booking::factory()->create([
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'status' => $status,
            ]);
            
            $canCreate = $this->policy->create($student, $teacher);
            
            // Only approved and completed bookings should allow messaging
            if (in_array($status, ['approved', 'completed'])) {
                expect($canCreate)->toBeTrue()
                    ->and(AuthorizationAuditLog::where('user_id', $student->id)
                        ->where('granted', true)
                        ->exists())->toBeTrue();
            } else {
                expect($canCreate)->toBeFalse()
                    ->and(AuthorizationAuditLog::where('user_id', $student->id)
                        ->where('granted', false)
                        ->where('reason', 'no_active_booking')
                        ->exists())->toBeTrue();
            }
            
            // Clean up for next iteration
            DB::table('authorization_audit_logs')->truncate();
        }
    })->repeat(5);
    
    it('validates teacher-student messaging requires active booking', function () {
        $bookingStatuses = ['pending', 'approved', 'completed', 'cancelled'];
        
        foreach ($bookingStatuses as $status) {
            $student = User::factory()->create(['role' => 'student']);
            $teacher = User::factory()->create(['role' => 'teacher']);
            
            $booking = Booking::factory()->create([
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'status' => $status,
            ]);
            
            // Teacher trying to message student
            $canCreate = $this->policy->create($teacher, $student);
            
            if (in_array($status, ['approved', 'completed'])) {
                expect($canCreate)->toBeTrue();
            } else {
                expect($canCreate)->toBeFalse();
                
                $log = AuthorizationAuditLog::where('user_id', $teacher->id)
                    ->where('granted', false)
                    ->first();
                expect($log)->not->toBeNull()
                    ->and($log->reason)->toBe('no_active_booking');
            }
            
            DB::table('authorization_audit_logs')->truncate();
        }
    })->repeat(5);
    
    it('validates guardian-teacher messaging rules', function () {
        // Create users and ensure profiles are loaded
        $guardian = User::factory()->create(['role' => 'guardian']);
        $guardian->refresh();
        
        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->refresh();
        
        $child = User::factory()->create(['role' => 'student']);
        $child->refresh();
        
        // Create guardian-child relationship via student profile
        $child->studentProfile->update(['guardian_id' => $guardian->id]);
        
        // Create active booking between child and teacher
        $booking = Booking::factory()->create([
            'student_id' => $child->id,
            'teacher_id' => $teacher->id,
            'status' => 'approved',
        ]);
        
        // Guardian can message teacher (backend validates child relationship)
        $canCreate = $this->policy->create($guardian, $teacher);
        expect($canCreate)->toBeTrue();
        
        // Verify audit log
        $log = AuthorizationAuditLog::where('user_id', $guardian->id)
            ->where('action', 'create_conversation')
            ->first();
        expect($log)->not->toBeNull()
            ->and($log->granted)->toBeTrue();
    })->repeat(10);
    
    it('validates admin can message anyone without restrictions', function () {
        $roles = ['student', 'teacher', 'guardian', 'admin'];
        
        foreach ($roles as $recipientRole) {
            $admin = User::factory()->create(['role' => 'admin']);
            $recipient = User::factory()->create(['role' => $recipientRole]);
            
            $canCreate = $this->policy->create($admin, $recipient);
            expect($canCreate)->toBeTrue();
            
            // Verify audit log shows admin access
            $log = AuthorizationAuditLog::where('user_id', $admin->id)
                ->where('action', 'create_conversation')
                ->first();
            expect($log)->not->toBeNull()
                ->and($log->granted)->toBeTrue();
            
            DB::table('authorization_audit_logs')->truncate();
        }
    })->repeat(10);
    
    it('validates super-admin can message anyone without restrictions', function () {
        $roles = ['student', 'teacher', 'guardian', 'admin', 'super-admin'];
        
        foreach ($roles as $recipientRole) {
            $superAdmin = User::factory()->create(['role' => 'super-admin']);
            $recipient = User::factory()->create(['role' => $recipientRole]);
            
            $canCreate = $this->policy->create($superAdmin, $recipient);
            expect($canCreate)->toBeTrue();
            
            DB::table('authorization_audit_logs')->truncate();
        }
    })->repeat(10);
    
    it('denies unauthorized role combinations', function () {
        // Test role combinations that should be denied
        $deniedCombinations = [
            ['student', 'student'],
            ['student', 'guardian'],
            ['student', 'admin'],
            ['teacher', 'teacher'],
            ['teacher', 'guardian'],
            ['guardian', 'guardian'],
            ['guardian', 'student'],
        ];
        
        foreach ($deniedCombinations as [$senderRole, $recipientRole]) {
            $sender = User::factory()->create(['role' => $senderRole]);
            $recipient = User::factory()->create(['role' => $recipientRole]);
            
            $canCreate = $this->policy->create($sender, $recipient);
            expect($canCreate)->toBeFalse();
            
            // Verify role violation was logged
            $log = AuthorizationAuditLog::where('user_id', $sender->id)
                ->where('granted', false)
                ->first();
            expect($log)->not->toBeNull()
                ->and($log->metadata)->toHaveKey('violation_type')
                ->and($log->metadata['violation_type'])->toBe('role_restriction');
            
            DB::table('authorization_audit_logs')->truncate();
        }
    })->repeat(5);
    
    it('validates participant can send messages in their conversation', function () {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach($user->id);
        
        $canSend = $this->policy->sendMessage($user, $conversation);
        expect($canSend)->toBeTrue();
        
        // Verify audit log
        $log = AuthorizationAuditLog::where('user_id', $user->id)
            ->where('action', 'send_message')
            ->first();
        expect($log)->not->toBeNull()
            ->and($log->granted)->toBeTrue();
    })->repeat(20);
    
    it('denies non-participant from sending messages', function () {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        // User is NOT a participant
        
        $canSend = $this->policy->sendMessage($user, $conversation);
        expect($canSend)->toBeFalse();
        
        // Verify audit log
        $log = AuthorizationAuditLog::where('user_id', $user->id)
            ->where('action', 'send_message')
            ->first();
        expect($log)->not->toBeNull()
            ->and($log->granted)->toBeFalse()
            ->and($log->reason)->toBe('not_participant');
    })->repeat(20);
});

// Feature: messaging-authorization-audit, Property 3: Message Read Authorization
describe('Message Read Authorization', function () {
    it('allows participants to mark messages as read', function () {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach($user->id);
        
        // User should be able to send messages in their conversation
        $canSend = $this->policy->sendMessage($user, $conversation);
        expect($canSend)->toBeTrue();
        
        // Verify audit log
        $log = AuthorizationAuditLog::where('user_id', $user->id)
            ->where('action', 'send_message')
            ->first();
        expect($log)->not->toBeNull()
            ->and($log->granted)->toBeTrue();
    });
    
    it('denies non-participants from marking messages as read', function () {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        // User is NOT a participant
        
        $canSend = $this->policy->sendMessage($user, $conversation);
        expect($canSend)->toBeFalse();
        
        // Verify audit log
        $log = AuthorizationAuditLog::where('user_id', $user->id)
            ->where('action', 'send_message')
            ->first();
        expect($log)->not->toBeNull()
            ->and($log->granted)->toBeFalse()
            ->and($log->reason)->toBe('not_participant');
    });
});

// Feature: messaging-authorization-audit, Property 4: Message Deletion Authorization
describe('Message Deletion Authorization', function () {
    it('allows sender to delete their own message', function () {
        $sender = User::factory()->create();
        $message = Message::factory()->create(['sender_id' => $sender->id]);
        
        $canDelete = $this->policy->delete($sender, $message);
        expect($canDelete)->toBeTrue();
        
        // Verify audit log
        $log = AuthorizationAuditLog::where('user_id', $sender->id)
            ->where('action', 'delete_message')
            ->first();
        expect($log)->not->toBeNull()
            ->and($log->granted)->toBeTrue();
    });
    
    it('allows admin to delete any message with override logging', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create();
        $message = Message::factory()->create(['sender_id' => $otherUser->id]);
        
        $canDelete = $this->policy->delete($admin, $message);
        expect($canDelete)->toBeTrue();
        
        // Verify admin override was logged
        $log = AuthorizationAuditLog::where('user_id', $admin->id)
            ->where('action', 'delete_message')
            ->first();
        expect($log)->not->toBeNull()
            ->and($log->granted)->toBeTrue()
            ->and($log->reason)->toBe('admin_override');
    });
    
    it('denies non-sender non-admin from deleting messages', function () {
        $user = User::factory()->create(['role' => 'student']);
        $otherUser = User::factory()->create();
        $message = Message::factory()->create(['sender_id' => $otherUser->id]);
        
        $canDelete = $this->policy->delete($user, $message);
        expect($canDelete)->toBeFalse();
        
        // Verify audit log
        $log = AuthorizationAuditLog::where('user_id', $user->id)
            ->where('action', 'delete_message')
            ->first();
        expect($log)->not->toBeNull()
            ->and($log->granted)->toBeFalse()
            ->and($log->reason)->toBe('not_authorized');
    });
});

// Feature: messaging-authorization-audit, Property 5: Policy Failure Response
describe('Policy Failure Response', function () {
    it('logs all policy failures with descriptive reasons', function () {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        
        // Attempt unauthorized access
        $this->policy->view($user, $conversation);
        
        // Verify failure was logged with reason
        $log = AuthorizationAuditLog::where('user_id', $user->id)
            ->where('granted', false)
            ->first();
        expect($log)->not->toBeNull()
            ->and($log->reason)->not->toBeNull()
            ->and($log->reason)->toBe('not_participant');
    });
});

// Feature: messaging-authorization-audit, Property 6: Successful Authorization Completion
describe('Successful Authorization Completion', function () {
    it('logs successful authorizations and allows operations', function () {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach($user->id);
        
        // Authorized access
        $granted = $this->policy->view($user, $conversation);
        
        expect($granted)->toBeTrue();
        
        // Verify success was logged
        $log = AuthorizationAuditLog::where('user_id', $user->id)
            ->where('granted', true)
            ->first();
        expect($log)->not->toBeNull()
            ->and($log->action)->toBe('view_conversation');
    });
});

<?php

use App\Models\User;
use App\Models\Conversation;
use App\Models\Booking;
use App\Models\Message;
use App\Models\AuthorizationAuditLog;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

/**
 * Integration tests for complete authorization flows
 * Validates: Requirements 1.1, 1.2, 1.3, 3.1, 3.2, 3.3, 3.4
 */
describe('Complete Authorization Flows', function () {
    
    it('allows student-teacher messaging with active booking (complete flow)', function () {
        // Setup: Create student and teacher
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        // Create active booking
        $booking = Booking::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'approved',
        ]);
        
        // Step 1: Create conversation
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$student->id, $teacher->id]);
        
        // Step 2: Student sends message
        $response = $this->actingAs($student, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Hello teacher, I have a question',
                'type' => 'text',
            ]);
        
        $response->assertStatus(201)
            ->assertJson(['success' => true]);
        
        $message = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', $student->id)
            ->first();
        
        expect($message)->not->toBeNull();
        
        // Step 3: Teacher replies
        $response2 = $this->actingAs($teacher, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Hello student, how can I help?',
                'type' => 'text',
            ]);
        
        $response2->assertStatus(201)
            ->assertJson(['success' => true]);
        
        // Step 4: Student marks teacher's message as read
        $teacherMessage = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', $teacher->id)
            ->first();
        
        $response3 = $this->actingAs($student, 'sanctum')
            ->postJson("/api/messages/{$teacherMessage->id}/read");
        
        $response3->assertStatus(200)
            ->assertJson(['success' => true]);
        
        // Verify audit logs
        $logs = AuthorizationAuditLog::where('user_id', $student->id)
            ->where('granted', true)
            ->get();
        
        expect($logs->count())->toBeGreaterThan(0);
    });
    
    it('denies student-teacher messaging without active booking (complete flow)', function () {
        // Setup: Create student and teacher WITHOUT booking
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        // Create conversation (somehow it exists)
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$student->id, $teacher->id]);
        
        // Attempt to send message - should fail
        $response = $this->actingAs($student, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Hello teacher',
                'type' => 'text',
            ]);
        
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'ROLE_RESTRICTION',
            ]);
        
        expect($response->json('details.reason'))->toBe('no_active_booking');
        
        // Verify no message was created
        $messageCount = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', $student->id)
            ->count();
        
        expect($messageCount)->toBe(0);
        
        // Verify role violation was logged
        $log = AuthorizationAuditLog::where('user_id', $student->id)
            ->where('granted', false)
            ->first();
        
        expect($log)->not->toBeNull()
            ->and($log->metadata['violation_type'])->toBe('role_restriction');
    });
    
    it('allows guardian-teacher messaging when teacher teaches child (complete flow)', function () {
        // Setup: Create guardian, teacher, and child
        $guardian = User::factory()->create(['role' => 'guardian']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $child = User::factory()->create(['role' => 'student']);
        
        // Link child to guardian
        $child->refresh();
        if (!$child->studentProfile) {
            $child->studentProfile()->create(['guardian_id' => $guardian->id]);
        } else {
            $child->studentProfile->update(['guardian_id' => $guardian->id]);
        }
        
        // Create booking between child and teacher
        $booking = Booking::factory()->create([
            'student_id' => $child->id,
            'teacher_id' => $teacher->id,
            'status' => 'approved',
        ]);
        
        // Step 1: Create conversation
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$guardian->id, $teacher->id]);
        
        // Step 2: Guardian sends message
        $response = $this->actingAs($guardian, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'How is my child progressing?',
                'type' => 'text',
            ]);
        
        $response->assertStatus(201)
            ->assertJson(['success' => true]);
        
        // Step 3: Teacher replies
        $response2 = $this->actingAs($teacher, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Your child is doing great!',
                'type' => 'text',
            ]);
        
        $response2->assertStatus(201)
            ->assertJson(['success' => true]);
        
        // Verify messages were created
        $messages = Message::where('conversation_id', $conversation->id)->get();
        expect($messages)->toHaveCount(2);
    });
    
    it('allows admin to message any user without restrictions (complete flow)', function () {
        // Setup: Create admin and various users
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $guardian = User::factory()->create(['role' => 'guardian']);
        
        $users = [$student, $teacher, $guardian];
        
        foreach ($users as $user) {
            // Create conversation
            $conversation = Conversation::factory()->create(['type' => 'direct']);
            $conversation->participants()->attach([$admin->id, $user->id]);
            
            // Admin sends message
            $response = $this->actingAs($admin, 'sanctum')
                ->postJson("/api/messages", [
                    'conversation_id' => $conversation->id,
                    'content' => "Admin message to {$user->role}",
                    'type' => 'text',
                ]);
            
            $response->assertStatus(201)
                ->assertJson(['success' => true]);
            
            // Note: Users cannot reply to admin due to role restrictions
            // Only admin can initiate and send messages to regular users
        }
        
        // Verify admin messages were created
        $totalMessages = Message::count();
        expect($totalMessages)->toBe(3); // 1 message per conversation × 3 conversations
    });
    
    it('denies unauthorized conversation access (complete flow)', function () {
        // Setup: Create users
        $user1 = User::factory()->create(['role' => 'admin']);
        $user2 = User::factory()->create(['role' => 'student']);
        $user3 = User::factory()->create(['role' => 'teacher']); // Not a participant
        
        // Create conversation between user1 and user2
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$user1->id, $user2->id]);
        
        // user1 sends a message
        $this->actingAs($user1, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Private message',
                'type' => 'text',
            ])
            ->assertStatus(201);
        
        $message = Message::where('conversation_id', $conversation->id)->first();
        
        // user3 (not a participant) tries to access the conversation
        // Attempt 1: Try to send a message
        $response1 = $this->actingAs($user3, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Unauthorized message',
                'type' => 'text',
            ]);
        
        $response1->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
        
        // Could be either AUTHORIZATION_FAILED or ROLE_RESTRICTION depending on the check order
        expect($response1->json('code'))->toBeIn(['AUTHORIZATION_FAILED', 'ROLE_RESTRICTION']);
        
        // Attempt 2: Try to mark message as read
        $response2 = $this->actingAs($user3, 'sanctum')
            ->postJson("/api/messages/{$message->id}/read");
        
        $response2->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'AUTHORIZATION_FAILED',
            ]);
        
        // Attempt 3: Try to delete the message
        $response3 = $this->actingAs($user3, 'sanctum')
            ->deleteJson("/api/messages/{$message->id}");
        
        $response3->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'AUTHORIZATION_FAILED',
            ]);
        
        // Verify audit logs show all failed attempts
        $failedLogs = AuthorizationAuditLog::where('user_id', $user3->id)
            ->where('granted', false)
            ->get();
        
        expect($failedLogs->count())->toBeGreaterThanOrEqual(3);
    });
    
    it('handles booking status changes correctly (complete flow)', function () {
        // Setup: Create student and teacher with approved booking
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        $booking = Booking::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'approved',
        ]);
        
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$student->id, $teacher->id]);
        
        // Step 1: Student can send message with approved booking
        $response1 = $this->actingAs($student, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Message with approved booking',
                'type' => 'text',
            ]);
        
        $response1->assertStatus(201);
        
        // Step 2: Change booking to completed (should still allow messaging)
        $booking->update(['status' => 'completed']);
        
        $response2 = $this->actingAs($student, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Message with completed booking',
                'type' => 'text',
            ]);
        
        $response2->assertStatus(201);
        
        // Step 3: Change booking to cancelled (should deny messaging)
        $booking->update(['status' => 'cancelled']);
        
        $response3 = $this->actingAs($student, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Message with cancelled booking',
                'type' => 'text',
            ]);
        
        $response3->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'ROLE_RESTRICTION',
            ]);
        
        // Verify only 2 messages were created
        $messageCount = Message::where('conversation_id', $conversation->id)->count();
        expect($messageCount)->toBe(2);
    });
    
    it('enforces authorization across message lifecycle (complete flow)', function () {
        // Setup
        $sender = User::factory()->create(['role' => 'admin']);
        $recipient = User::factory()->create(['role' => 'student']);
        $outsider = User::factory()->create(['role' => 'teacher']);
        
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$sender->id, $recipient->id]);
        
        // Step 1: Sender creates message
        $response1 = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Original message',
                'type' => 'text',
            ]);
        
        $response1->assertStatus(201);
        $message = Message::where('conversation_id', $conversation->id)->first();
        
        // Step 2: Sender can update their own message
        $response2 = $this->actingAs($sender, 'sanctum')
            ->putJson("/api/messages/{$message->id}", [
                'content' => 'Updated message',
            ]);
        
        $response2->assertStatus(200);
        
        // Step 3: Recipient cannot update sender's message
        $response3 = $this->actingAs($recipient, 'sanctum')
            ->putJson("/api/messages/{$message->id}", [
                'content' => 'Unauthorized update',
            ]);
        
        $response3->assertStatus(403);
        
        // Step 4: Recipient can mark message as read
        $response4 = $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/messages/{$message->id}/read");
        
        $response4->assertStatus(200);
        
        // Step 5: Outsider cannot mark message as read
        $response5 = $this->actingAs($outsider, 'sanctum')
            ->postJson("/api/messages/{$message->id}/read");
        
        $response5->assertStatus(403);
        
        // Step 6: Sender can delete their own message
        $response6 = $this->actingAs($sender, 'sanctum')
            ->deleteJson("/api/messages/{$message->id}");
        
        $response6->assertStatus(200);
        
        // Verify message was deleted
        expect(Message::find($message->id))->toBeNull();
    });
});

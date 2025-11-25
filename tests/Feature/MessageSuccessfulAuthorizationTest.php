<?php

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Booking;
use App\Models\AuthorizationAuditLog;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

/**
 * Feature: messaging-authorization-audit, Property 6: Successful Authorization Completion
 * Validates: Requirements 4.4
 * 
 * For any authorized operation, when authorization succeeds, the system proceeds 
 * with the requested operation and completes it successfully
 */
describe('Successful Authorization Completion', function () {
    
    it('completes message sending when authorization succeeds', function () {
        // Use admin to bypass role restrictions
        $sender = User::factory()->create(['role' => 'admin']);
        $recipient = User::factory()->create(['role' => 'student']);
        
        // Create conversation with both as participants
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$sender->id, $recipient->id]);
        
        $response = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Test message content',
                'type' => 'text',
            ]);
        
        // Should succeed with 201
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'conversation_id',
                    'sender_id',
                    'content',
                    'type',
                ],
                'message',
            ]);
        
        // Verify message was actually created
        $message = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', $sender->id)
            ->where('content', 'Test message content')
            ->first();
        
        expect($message)->not->toBeNull()
            ->and($message->type)->toBe('text');
        
        // Verify authorization was logged as successful
        $log = AuthorizationAuditLog::where('user_id', $sender->id)
            ->where('action', 'send_message')
            ->where('granted', true)
            ->first();
        
        expect($log)->not->toBeNull();
    });
    
    it('completes message update when authorization succeeds', function () {
        $sender = User::factory()->create();
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'content' => 'Original content',
        ]);
        
        $response = $this->actingAs($sender, 'sanctum')
            ->putJson("/api/messages/{$message->id}", [
                'content' => 'Updated content',
            ]);
        
        // Should succeed with 200
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        
        // Verify message was actually updated
        $message->refresh();
        expect($message->content)->toBe('Updated content');
    });
    
    it('completes message deletion when authorization succeeds', function () {
        $sender = User::factory()->create();
        $message = Message::factory()->create(['sender_id' => $sender->id]);
        $messageId = $message->id;
        
        $response = $this->actingAs($sender, 'sanctum')
            ->deleteJson("/api/messages/{$messageId}");
        
        // Should succeed with 200
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        
        // Verify message was actually deleted
        expect(Message::find($messageId))->toBeNull();
        
        // Verify authorization was logged as successful
        $log = AuthorizationAuditLog::where('user_id', $sender->id)
            ->where('action', 'delete_message')
            ->where('granted', true)
            ->first();
        
        expect($log)->not->toBeNull();
    });
    
    it('completes mark as read when authorization succeeds', function () {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$sender->id, $recipient->id]);
        
        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
        ]);
        
        $response = $this->actingAs($recipient, 'sanctum')
            ->postJson("/api/messages/{$message->id}/read");
        
        // Should succeed with 200
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        
        // Verify message status was created
        $status = $message->statuses()
            ->where('user_id', $recipient->id)
            ->where('status', 'read')
            ->first();
        
        expect($status)->not->toBeNull();
    });
    
    it('completes admin message deletion with override logging', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create();
        $message = Message::factory()->create(['sender_id' => $otherUser->id]);
        $messageId = $message->id;
        
        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/messages/{$messageId}");
        
        // Should succeed with 200
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        
        // Verify message was actually deleted
        expect(Message::find($messageId))->toBeNull();
        
        // Verify admin override was logged
        $log = AuthorizationAuditLog::where('user_id', $admin->id)
            ->where('action', 'delete_message')
            ->where('granted', true)
            ->where('reason', 'admin_override')
            ->first();
        
        expect($log)->not->toBeNull();
    });
    
    it('completes student-teacher messaging with active booking', function () {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        // Create active booking
        Booking::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'approved',
        ]);
        
        // Create conversation
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$student->id, $teacher->id]);
        
        $response = $this->actingAs($student, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Hello teacher',
                'type' => 'text',
            ]);
        
        // Should succeed with 201
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);
        
        // Verify message was created
        $message = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', $student->id)
            ->first();
        
        expect($message)->not->toBeNull()
            ->and($message->content)->toBe('Hello teacher');
    });
    
    it('completes guardian-teacher messaging when teacher teaches child', function () {
        $guardian = User::factory()->create(['role' => 'guardian']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        $child = User::factory()->create(['role' => 'student']);
        
        // Ensure student profile exists and link child to guardian
        $child->refresh();
        if (!$child->studentProfile) {
            $child->studentProfile()->create(['guardian_id' => $guardian->id]);
        } else {
            $child->studentProfile->update(['guardian_id' => $guardian->id]);
        }
        
        // Create booking between child and teacher
        Booking::factory()->create([
            'student_id' => $child->id,
            'teacher_id' => $teacher->id,
            'status' => 'approved',
        ]);
        
        // Create conversation
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$guardian->id, $teacher->id]);
        
        $response = $this->actingAs($guardian, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Question about my child',
                'type' => 'text',
            ]);
        
        // Should succeed with 201
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);
        
        // Verify message was created
        $message = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', $guardian->id)
            ->first();
        
        expect($message)->not->toBeNull();
    });
    
    it('returns success response with complete data structure', function () {
        // Use admin to bypass role restrictions
        $user = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create(['role' => 'student']);
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$user->id, $otherUser->id]);
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Test message',
                'type' => 'text',
            ]);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'conversation_id',
                    'sender_id',
                    'content',
                    'type',
                    'created_at',
                    'updated_at',
                    'sender' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
                'message',
            ]);
        
        // Verify all required fields are present
        $data = $response->json('data');
        expect($data)->toHaveKeys([
            'id',
            'conversation_id',
            'sender_id',
            'content',
            'type',
            'sender',
        ]);
    });
});

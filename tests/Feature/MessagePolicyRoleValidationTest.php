<?php

use App\Models\User;
use App\Models\Conversation;
use App\Models\Booking;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

/**
 * Test that sendMessage policy validates role-based messaging rules
 * even when users are already participants in a conversation
 */
describe('Message Policy Role Validation', function () {
    
    it('denies student-student messaging even if conversation exists', function () {
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        
        // Create conversation with both students as participants
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$student1->id, $student2->id]);
        
        // Try to send message
        $response = $this->actingAs($student1, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Hello',
                'type' => 'text',
            ]);
        
        // Should be denied due to role restriction
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Forbidden',
            ]);
    });
    
    it('denies student-teacher messaging without booking even if conversation exists', function () {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        // Create conversation with both as participants
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$student->id, $teacher->id]);
        
        // No booking exists
        
        // Try to send message
        $response = $this->actingAs($student, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Hello teacher',
                'type' => 'text',
            ]);
        
        // Should be denied due to no active booking
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'ROLE_RESTRICTION',
            ]);
        
        expect($response->json('details.reason'))->toBe('no_active_booking');
    });
    
    it('allows student-teacher messaging with active booking', function () {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        // Create active booking
        Booking::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'approved',
        ]);
        
        // Create conversation
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$student->id, $teacher->id]);
        
        // Try to send message
        $response = $this->actingAs($student, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Hello teacher',
                'type' => 'text',
            ]);
        
        // Should succeed
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);
    });
    
    it('denies teacher-student messaging without booking even if conversation exists', function () {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        // Create conversation
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$student->id, $teacher->id]);
        
        // No booking exists
        
        // Try to send message as teacher
        $response = $this->actingAs($teacher, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Hello student',
                'type' => 'text',
            ]);
        
        // Should be denied
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'ROLE_RESTRICTION',
            ]);
    });
    
    it('allows admin to message anyone regardless of role rules', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        
        // Create conversation
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$admin->id, $student->id]);
        
        // Try to send message as admin
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Admin message',
                'type' => 'text',
            ]);
        
        // Should succeed
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);
    });
    
    it('allows messaging in group conversations without role validation', function () {
        $user1 = User::factory()->create(['role' => 'student']);
        $user2 = User::factory()->create(['role' => 'student']);
        $user3 = User::factory()->create(['role' => 'teacher']);
        
        // Create group conversation
        $conversation = Conversation::factory()->create(['type' => 'group']);
        $conversation->participants()->attach([$user1->id, $user2->id, $user3->id]);
        
        // Try to send message in group
        $response = $this->actingAs($user1, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Group message',
                'type' => 'text',
            ]);
        
        // Should succeed (group conversations don't enforce role rules)
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);
    });
    
    it('denies guardian-teacher messaging without child relationship', function () {
        $guardian = User::factory()->create(['role' => 'guardian']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        // Create conversation
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$guardian->id, $teacher->id]);
        
        // No child-teacher relationship exists
        
        // Try to send message
        $response = $this->actingAs($guardian, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Hello teacher',
                'type' => 'text',
            ]);
        
        // Should be denied
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'ROLE_RESTRICTION',
            ]);
    });
    
    it('allows guardian-teacher messaging when teacher teaches child', function () {
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
        Booking::factory()->create([
            'student_id' => $child->id,
            'teacher_id' => $teacher->id,
            'status' => 'approved',
        ]);
        
        // Create conversation
        $conversation = Conversation::factory()->create(['type' => 'direct']);
        $conversation->participants()->attach([$guardian->id, $teacher->id]);
        
        // Try to send message
        $response = $this->actingAs($guardian, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Question about my child',
                'type' => 'text',
            ]);
        
        // Should succeed
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);
    });
});

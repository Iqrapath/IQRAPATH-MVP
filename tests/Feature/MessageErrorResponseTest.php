<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Message Controller Error Responses', function () {
    beforeEach(function () {
        $this->student = User::factory()->create(['role' => 'student']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->admin = User::factory()->create(['role' => 'admin']);
    });

    describe('401 Unauthorized Response Format', function () {
        it('returns 401 when sending message without authentication', function () {
            $conversation = Conversation::factory()->create();

            $response = $this->postJson('/api/messages', [
                'conversation_id' => $conversation->id,
                'content' => 'Test message',
            ]);

            $response->assertStatus(401);
            // Laravel's auth middleware returns this format
            $response->assertJson([
                'message' => 'Unauthenticated.',
            ]);
        });

        it('returns 401 when updating message without authentication', function () {
            $message = Message::factory()->create();

            $response = $this->putJson("/api/messages/{$message->id}", [
                'content' => 'Updated content',
            ]);

            $response->assertStatus(401);
            $response->assertJson([
                'message' => 'Unauthenticated.',
            ]);
        });

        it('returns 401 when deleting message without authentication', function () {
            $message = Message::factory()->create();

            $response = $this->deleteJson("/api/messages/{$message->id}");

            $response->assertStatus(401);
            $response->assertJson([
                'message' => 'Unauthenticated.',
            ]);
        });

        it('returns 401 when marking message as read without authentication', function () {
            $message = Message::factory()->create();

            $response = $this->postJson("/api/messages/{$message->id}/read");

            $response->assertStatus(401);
            $response->assertJson([
                'message' => 'Unauthenticated.',
            ]);
        });

        it('returns 401 when viewing conversations without authentication', function () {
            $response = $this->getJson('/api/conversations');

            $response->assertStatus(401);
            $response->assertJson([
                'message' => 'Unauthenticated.',
            ]);
        });

        it('returns 401 when creating conversation without authentication', function () {
            $response = $this->postJson('/api/conversations', [
                'recipient_id' => $this->teacher->id,
            ]);

            $response->assertStatus(401);
            $response->assertJson([
                'message' => 'Unauthenticated.',
            ]);
        });
    });

    describe('403 Forbidden Response Format', function () {
        it('returns 403 when sending message in conversation user is not participant of', function () {
            $otherUser = User::factory()->create(['role' => 'student']);
            $conversation = Conversation::factory()->create();
            $conversation->participants()->attach([$this->teacher->id, $otherUser->id]);

            $this->actingAs($this->student);

            $response = $this->postJson('/api/messages', [
                'conversation_id' => $conversation->id,
                'content' => 'Test message',
            ]);

            $response->assertStatus(403);
            // Role restriction is checked first, so we get role violation error
            $response->assertJson([
                'success' => false,
                'error' => 'Forbidden',
                'code' => 'ROLE_RESTRICTION',
                'details' => [
                    'your_role' => 'student',
                    'recipient_role' => 'teacher',
                ],
            ]);
        });

        it('returns 403 when updating message user did not send', function () {
            $message = Message::factory()->create([
                'sender_id' => $this->teacher->id,
            ]);

            $this->actingAs($this->student);

            $response = $this->putJson("/api/messages/{$message->id}", [
                'content' => 'Updated content',
            ]);

            $response->assertStatus(403);
            $response->assertJson([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not authorized to update this message',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'reason' => 'not_sender',
                    'resource_type' => 'Message',
                    'resource_id' => $message->id,
                ],
            ]);
        });

        it('returns 403 when deleting message user did not send and is not admin', function () {
            $message = Message::factory()->create([
                'sender_id' => $this->teacher->id,
            ]);

            $this->actingAs($this->student);

            $response = $this->deleteJson("/api/messages/{$message->id}");

            $response->assertStatus(403);
            $response->assertJson([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not authorized to delete this message',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'resource_type' => 'Message',
                    'resource_id' => $message->id,
                ],
            ]);
        });

        it('returns 403 when viewing conversation user is not participant of', function () {
            $otherUser = User::factory()->create(['role' => 'student']);
            $conversation = Conversation::factory()->create();
            $conversation->participants()->attach([$this->teacher->id, $otherUser->id]);

            $this->actingAs($this->student);

            $response = $this->getJson("/api/conversations/{$conversation->id}");

            $response->assertStatus(403);
            $response->assertJson([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not authorized to view this conversation',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'reason' => 'not_participant',
                    'resource_type' => 'Conversation',
                    'resource_id' => $conversation->id,
                ],
            ]);
        });

        it('returns 403 when archiving conversation user is not participant of', function () {
            $otherUser = User::factory()->create(['role' => 'student']);
            $conversation = Conversation::factory()->create();
            $conversation->participants()->attach([$this->teacher->id, $otherUser->id]);

            $this->actingAs($this->student);

            $response = $this->postJson("/api/conversations/{$conversation->id}/archive");

            $response->assertStatus(403);
            $response->assertJson([
                'success' => false,
                'error' => 'Forbidden',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'reason' => 'not_participant',
                    'resource_type' => 'Conversation',
                ],
            ]);
        });
    });

    describe('403 Role Violation Response Format', function () {
        it('returns 403 with role violation details when student tries to message teacher without booking', function () {
            $this->actingAs($this->student);

            $response = $this->postJson('/api/conversations', [
                'recipient_id' => $this->teacher->id,
            ]);

            $response->assertStatus(403);
            $response->assertJson([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You cannot message this user',
                'code' => 'ROLE_RESTRICTION',
                'details' => [
                    'reason' => 'no_active_booking',
                    'your_role' => 'student',
                    'recipient_role' => 'teacher',
                ],
            ]);
        });

        it('returns 403 with role violation details when teacher tries to message student without booking', function () {
            $this->actingAs($this->teacher);

            $response = $this->postJson('/api/conversations', [
                'recipient_id' => $this->student->id,
            ]);

            $response->assertStatus(403);
            $response->assertJson([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You cannot message this user',
                'code' => 'ROLE_RESTRICTION',
                'details' => [
                    'reason' => 'no_active_booking',
                    'your_role' => 'teacher',
                    'recipient_role' => 'student',
                ],
            ]);
        });

        it('returns 403 with role violation details when guardian tries to message teacher not teaching their child', function () {
            $guardian = User::factory()->create(['role' => 'guardian']);
            
            $this->actingAs($guardian);

            $response = $this->postJson('/api/conversations', [
                'recipient_id' => $this->teacher->id,
            ]);

            $response->assertStatus(403);
            $response->assertJson([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You cannot message this user',
                'code' => 'ROLE_RESTRICTION',
                'details' => [
                    'reason' => 'teacher_not_teaching_child',
                    'your_role' => 'guardian',
                    'recipient_role' => 'teacher',
                ],
            ]);
        })->skip('Guardian children relationship not implemented yet');

        it('allows admin to message anyone without role restrictions', function () {
            $this->actingAs($this->admin);

            $response = $this->postJson('/api/conversations', [
                'recipient_id' => $this->teacher->id,
            ]);

            $response->assertStatus(201);
            $response->assertJson([
                'success' => true,
            ]);
        });
    });
});

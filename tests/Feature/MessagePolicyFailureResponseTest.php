<?php

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Booking;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

uses(DatabaseMigrations::class);

/**
 * Feature: messaging-authorization-audit, Property 5: Policy Failure Response
 * Validates: Requirements 4.2
 * 
 * For any failed policy check, the system returns a 403 Forbidden response 
 * with a descriptive error message
 */
describe('Policy Failure Response', function () {
    
    it('returns 403 with descriptive error when non-participant tries to send message', function () {
        $user = User::factory()->create(['role' => 'admin']); // Use admin to avoid role restrictions
        $otherUser = User::factory()->create(['role' => 'student']);
        
        // Create conversation without user as participant
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach($otherUser->id);
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Test message',
                'type' => 'text',
            ]);
        
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Forbidden',
            ])
            ->assertJsonStructure([
                'success',
                'error',
                'message',
                'code',
                'details' => [
                    'reason',
                    'resource_type',
                    'resource_id',
                ],
            ]);
        
        // Verify the error message is descriptive
        expect($response->json('message'))->toContain('not authorized')
            ->and($response->json('details.reason'))->toBe('not_participant')
            ->and($response->json('details.resource_type'))->toBe('Conversation');
    });
    
    it('returns 403 with descriptive error for various authorization failures', function () {
        // Test non-sender trying to delete message
        $sender = User::factory()->create();
        $nonSender = User::factory()->create(['role' => 'student']);
        $message = Message::factory()->create(['sender_id' => $sender->id]);
        
        $response = $this->actingAs($nonSender, 'sanctum')
            ->deleteJson("/api/messages/{$message->id}");
        
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Forbidden',
            ])
            ->assertJsonStructure([
                'success',
                'error',
                'message',
                'code',
                'details' => [
                    'reason',
                    'resource_type',
                    'resource_id',
                ],
            ]);
        
        // Verify error details are descriptive
        expect($response->json('message'))->not->toBeEmpty()
            ->and($response->json('details.reason'))->toBe('not_sender')
            ->and($response->json('details.resource_type'))->toBe('Message');
    });
    
    it('returns 403 with descriptive error when non-sender tries to update message', function () {
        $sender = User::factory()->create();
        $otherUser = User::factory()->create();
        
        $message = Message::factory()->create(['sender_id' => $sender->id]);
        
        $response = $this->actingAs($otherUser, 'sanctum')
            ->putJson("/api/messages/{$message->id}", [
                'content' => 'Updated content',
            ]);
        
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Forbidden',
                'code' => 'AUTHORIZATION_FAILED',
            ])
            ->assertJsonStructure([
                'success',
                'error',
                'message',
                'code',
                'details' => [
                    'reason',
                    'resource_type',
                    'resource_id',
                ],
            ]);
        
        expect($response->json('message'))->toContain('not authorized')
            ->and($response->json('details.reason'))->toBe('not_sender')
            ->and($response->json('details.resource_type'))->toBe('Message');
    });
    
    it('returns 403 with descriptive error when non-sender non-admin tries to delete message', function () {
        $sender = User::factory()->create();
        $otherUser = User::factory()->create(['role' => 'student']);
        
        $message = Message::factory()->create(['sender_id' => $sender->id]);
        
        $response = $this->actingAs($otherUser, 'sanctum')
            ->deleteJson("/api/messages/{$message->id}");
        
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Forbidden',
                'code' => 'AUTHORIZATION_FAILED',
            ])
            ->assertJsonStructure([
                'success',
                'error',
                'message',
                'code',
                'details' => [
                    'reason',
                    'resource_type',
                    'resource_id',
                ],
            ]);
        
        expect($response->json('message'))->toContain('not authorized')
            ->and($response->json('details.reason'))->toBe('not_sender')
            ->and($response->json('details.resource_type'))->toBe('Message');
    });
    
    it('returns 403 with descriptive error when non-participant tries to mark message as read', function () {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $nonParticipant = User::factory()->create();
        
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach([$sender->id, $recipient->id]);
        
        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
        ]);
        
        $response = $this->actingAs($nonParticipant, 'sanctum')
            ->postJson("/api/messages/{$message->id}/read");
        
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Forbidden',
                'code' => 'AUTHORIZATION_FAILED',
            ])
            ->assertJsonStructure([
                'success',
                'error',
                'message',
                'code',
                'details' => [
                    'reason',
                    'resource_type',
                    'resource_id',
                ],
            ]);
        
        expect($response->json('message'))->toContain('not authorized')
            ->and($response->json('details.reason'))->toBe('not_recipient')
            ->and($response->json('details.resource_type'))->toBe('Message');
    });
    
    it('returns 401 when unauthenticated user tries to send message', function () {
        $conversation = Conversation::factory()->create();
        
        $response = $this->postJson("/api/messages", [
            'conversation_id' => $conversation->id,
            'content' => 'Test message',
            'type' => 'text',
        ]);
        
        // Laravel Sanctum returns 401 with simple message
        $response->assertStatus(401)
            ->assertJsonStructure([
                'message',
            ]);
        
        expect($response->json('message'))->toContain('Unauthenticated');
    });
    
    it('returns consistent error format across all authorization failures', function () {
        $user = User::factory()->create(['role' => 'student']);
        $conversation = Conversation::factory()->create();
        $message = Message::factory()->create();
        
        // Test multiple endpoints
        $endpoints = [
            ['method' => 'post', 'url' => '/api/messages', 'data' => ['conversation_id' => $conversation->id, 'content' => 'test']],
            ['method' => 'put', 'url' => "/api/messages/{$message->id}", 'data' => ['content' => 'test']],
            ['method' => 'delete', 'url' => "/api/messages/{$message->id}", 'data' => []],
            ['method' => 'post', 'url' => "/api/messages/{$message->id}/read", 'data' => []],
        ];
        
        foreach ($endpoints as $endpoint) {
            $response = $this->actingAs($user, 'sanctum')
                ->{$endpoint['method'] . 'Json'}($endpoint['url'], $endpoint['data']);
            
            // All should return 403 with consistent structure
            $response->assertStatus(403)
                ->assertJsonStructure([
                    'success',
                    'error',
                    'message',
                    'code',
                    'details',
                ]);
            
            // Verify required fields are present
            expect($response->json('success'))->toBeFalse()
                ->and($response->json('error'))->toBe('Forbidden')
                ->and($response->json('message'))->not->toBeEmpty()
                ->and($response->json('code'))->not->toBeEmpty()
                ->and($response->json('details'))->toBeArray();
        }
    });
    
    it('includes resource context in all error responses', function () {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'Test message',
            ]);
        
        $response->assertStatus(403);
        
        // Verify resource context is included
        $details = $response->json('details');
        expect($details)->toHaveKey('resource_type')
            ->and($details)->toHaveKey('resource_id')
            ->and($details['resource_type'])->toBeIn(['Conversation', 'Message'])
            ->and($details['resource_id'])->toBeInt();
    });
    
    it('provides actionable error messages for different failure scenarios', function () {
        // Scenario 1: Non-participant trying to send message
        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = Conversation::factory()->create();
        $conversation->participants()->attach(User::factory()->create()->id);
        
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/messages", [
                'conversation_id' => $conversation->id,
                'content' => 'test',
            ]);
        
        $response->assertStatus(403);
        expect($response->json('message'))->not->toBeEmpty()
            ->and(strlen($response->json('message')))->toBeGreaterThan(10)
            ->and($response->json('details.reason'))->toBe('not_participant');
        
        // Scenario 2: Non-sender trying to update message
        $sender = User::factory()->create();
        $otherUser = User::factory()->create();
        $message = Message::factory()->create(['sender_id' => $sender->id]);
        
        $response2 = $this->actingAs($otherUser, 'sanctum')
            ->putJson("/api/messages/{$message->id}", [
                'content' => 'updated content',
            ]);
        
        $response2->assertStatus(403);
        expect($response2->json('message'))->not->toBeEmpty()
            ->and(strlen($response2->json('message')))->toBeGreaterThan(10)
            ->and($response2->json('details.reason'))->toBe('not_sender');
    });
});

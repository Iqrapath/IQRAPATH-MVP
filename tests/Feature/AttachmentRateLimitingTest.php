<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    /**
     * Test rate limiting on attachment uploads
     * 
     * **Property: Rate limiting prevents abuse**
     * **Validates: Requirements 4.1**
     */
    public function test_rate_limiting_on_attachment_uploads()
    {
        $user = User::factory()->create(['role' => 'student']);
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Rate Limit Test'
        ]);
        $conversation->participants()->attach($user->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'Test message',
            'type' => 'text'
        ]);

        $this->actingAs($user);

        // Make 20 requests (should all succeed)
        for ($i = 0; $i < 20; $i++) {
            $file = UploadedFile::fake()->create("test{$i}.jpg", 100);
            
            $response = $this->postJson("/api/messages/{$message->id}/attachments", [
                'file' => $file,
                'attachment_type' => 'image'
            ]);

            $response->assertStatus(201);
        }

        // 21st request should be rate limited
        $file = UploadedFile::fake()->create('test21.jpg', 100);
        
        $response = $this->postJson("/api/messages/{$message->id}/attachments", [
            'file' => $file,
            'attachment_type' => 'image'
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    /**
     * Test storage quota enforcement
     * 
     * **Property: Storage quota prevents excessive usage**
     * **Validates: Requirements 4.1**
     */
    public function test_storage_quota_enforcement()
    {
        $user = User::factory()->create(['role' => 'student']);
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Quota Test'
        ]);
        $conversation->participants()->attach($user->id);

        // Create attachments that consume most of the quota (100MB for students)
        // We'll create 9 attachments of 10MB each (90MB total)
        for ($i = 0; $i < 9; $i++) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'content' => "Test message {$i}",
                'type' => 'text'
            ]);

            MessageAttachment::create([
                'message_id' => $message->id,
                'filename' => "file{$i}.jpg",
                'original_filename' => "file{$i}.jpg",
                'file_path' => "test/file{$i}.jpg",
                'file_size' => 10 * 1024 * 1024, // 10MB
                'mime_type' => 'image/jpeg',
                'attachment_type' => 'image'
            ]);
        }

        $this->actingAs($user);

        // Try to upload another 11MB file (would exceed 100MB quota)
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'Final test message',
            'type' => 'text'
        ]);

        $file = UploadedFile::fake()->create('large-file.jpg', 11 * 1024); // 11MB

        $response = $this->postJson("/api/messages/{$message->id}/attachments", [
            'file' => $file,
            'attachment_type' => 'image'
        ]);

        $response->assertStatus(413); // Payload Too Large
        $response->assertJson([
            'success' => false,
            'error_code' => 'QUOTA_EXCEEDED'
        ]);
        $response->assertJsonStructure([
            'data' => [
                'quota',
                'used',
                'remaining',
                'formatted_quota',
                'formatted_used',
                'formatted_remaining'
            ]
        ]);
    }

    /**
     * Test different quota limits for different roles
     * 
     * **Property: Role-based quota enforcement**
     * **Validates: Requirements 4.1**
     */
    public function test_role_based_quota_limits()
    {
        $roles = [
            'student' => 100 * 1024 * 1024,    // 100MB
            'teacher' => 500 * 1024 * 1024,    // 500MB
            'guardian' => 100 * 1024 * 1024,   // 100MB
            'admin' => 1024 * 1024 * 1024,     // 1GB
        ];

        foreach ($roles as $role => $expectedQuota) {
            $user = User::factory()->create(['role' => $role]);
            $conversation = Conversation::create([
                'type' => 'direct',
                'subject' => "Quota Test for {$role}"
            ]);
            $conversation->participants()->attach($user->id);

            // Create a message
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'content' => 'Test message',
                'type' => 'text'
            ]);

            // Create an attachment that fills the quota
            MessageAttachment::create([
                'message_id' => $message->id,
                'filename' => 'full-quota.jpg',
                'original_filename' => 'full-quota.jpg',
                'file_path' => 'test/full-quota.jpg',
                'file_size' => $expectedQuota,
                'mime_type' => 'image/jpeg',
                'attachment_type' => 'image'
            ]);

            $this->actingAs($user);

            // Try to upload another file (should fail)
            $newMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'content' => 'Another message',
                'type' => 'text'
            ]);

            $file = UploadedFile::fake()->create('test.jpg', 100);

            $response = $this->postJson("/api/messages/{$newMessage->id}/attachments", [
                'file' => $file,
                'attachment_type' => 'image'
            ]);

            $response->assertStatus(413);
            $response->assertJsonPath('data.quota', $expectedQuota);
        }
    }

    /**
     * Test quota check only counts user's own attachments
     * 
     * **Property: Quota isolation per user**
     * **Validates: Requirements 4.1**
     */
    public function test_quota_only_counts_own_attachments()
    {
        $user1 = User::factory()->create(['role' => 'student']);
        $user2 = User::factory()->create(['role' => 'student']);
        
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Quota Isolation Test'
        ]);
        $conversation->participants()->attach([$user1->id, $user2->id]);

        // User 2 uploads files (should not affect user 1's quota)
        for ($i = 0; $i < 5; $i++) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user2->id,
                'content' => "User 2 message {$i}",
                'type' => 'text'
            ]);

            MessageAttachment::create([
                'message_id' => $message->id,
                'filename' => "user2-file{$i}.jpg",
                'original_filename' => "user2-file{$i}.jpg",
                'file_path' => "test/user2-file{$i}.jpg",
                'file_size' => 10 * 1024 * 1024, // 10MB each
                'mime_type' => 'image/jpeg',
                'attachment_type' => 'image'
            ]);
        }

        // User 1 should still be able to upload (their quota is separate)
        $this->actingAs($user1);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user1->id,
            'content' => 'User 1 message',
            'type' => 'text'
        ]);

        $file = UploadedFile::fake()->create('user1-file.jpg', 100);

        $response = $this->postJson("/api/messages/{$message->id}/attachments", [
            'file' => $file,
            'attachment_type' => 'image'
        ]);

        $response->assertStatus(201); // Should succeed
    }
}


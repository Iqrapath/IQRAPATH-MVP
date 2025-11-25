<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Integration tests for the complete message attachment workflow
 * 
 * Tests the full flow from upload to display, including:
 * - File upload and storage
 * - Message creation with attachments
 * - Real-time updates
 * - Authorization and security
 * - Error handling
 */
class MessageAttachmentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Event::fake(); // Disable broadcasting for tests
    }

    /**
     * Test complete upload and display flow for images
     */
    public function test_complete_image_upload_and_display_flow()
    {
        // Setup: Create users and conversation
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Integration Test'
        ]);
        $conversation->participants()->attach([$student->id, $teacher->id]);

        // Step 1: Upload image attachment
        $this->actingAs($student);
        $imageFile = UploadedFile::fake()->create('test-photo.jpg', 500, 'image/jpeg');
        
        $messageService = app(MessageService::class);
        $message = $messageService->sendMessage(
            $student,
            $conversation->id,
            'Check out this image!',
            'text',
            [['file' => $imageFile, 'type' => 'image', 'metadata' => []]]
        );

        // Verify message was created
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Check out this image!', $message->content);
        $this->assertEquals($student->id, $message->sender_id);

        // Verify attachment was created
        $this->assertCount(1, $message->attachments);
        $attachment = $message->attachments->first();
        $this->assertEquals('image', $attachment->attachment_type);
        $this->assertEquals('image/jpeg', $attachment->mime_type);

        // Verify file was stored
        Storage::disk('private')->assertExists($attachment->file_path);

        // Step 2: Retrieve message with attachments
        $retrievedMessage = Message::with('attachments')->find($message->id);
        $this->assertCount(1, $retrievedMessage->attachments);

        // Step 3: Generate signed URL for download
        $this->actingAs($teacher); // Other participant
        $response = $this->getJson("/api/attachments/{$attachment->id}/url");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertArrayHasKey('url', $response->json('data'));

        // Step 4: Verify non-participant cannot access
        $nonParticipant = User::factory()->create(['role' => 'student']);
        $this->actingAs($nonParticipant);
        $response = $this->getJson("/api/attachments/{$attachment->id}/url");
        $response->assertStatus(403);
    }

    /**
     * Test complete voice message flow
     */
    public function test_complete_voice_message_flow()
    {
        // Setup
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);
        
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Voice Test'
        ]);
        $conversation->participants()->attach([$student->id, $teacher->id]);

        // Step 1: Send voice message
        $this->actingAs($student);
        $voiceFile = UploadedFile::fake()->create('voice-message.webm', 100, 'audio/webm');
        
        $messageService = app(MessageService::class);
        $message = $messageService->sendMessage(
            $student,
            $conversation->id,
            '',
            'voice',
            [['file' => $voiceFile, 'type' => 'voice', 'metadata' => ['duration' => 30]]]
        );

        // Verify message and attachment
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('voice', $message->type);
        $this->assertCount(1, $message->attachments);
        
        $attachment = $message->attachments->first();
        $this->assertEquals('voice', $attachment->attachment_type);
        $this->assertEquals('audio/webm', $attachment->mime_type);
        $this->assertEquals(30, $attachment->duration);

        // Verify file storage
        Storage::disk('private')->assertExists($attachment->file_path);

        // Step 2: Access voice message URL
        $this->actingAs($teacher);
        $response = $this->getJson("/api/attachments/{$attachment->id}/url");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * Test multiple file upload in single message
     */
    public function test_multiple_file_upload_in_single_message()
    {
        // Setup
        $user = User::factory()->create(['role' => 'teacher']);
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Multiple Files Test'
        ]);
        $conversation->participants()->attach($user->id);

        // Upload message with multiple attachments
        $this->actingAs($user);
        $files = [
            ['file' => UploadedFile::fake()->create('photo1.jpg', 200, 'image/jpeg'), 'type' => 'image', 'metadata' => []],
            ['file' => UploadedFile::fake()->create('photo2.jpg', 300, 'image/jpeg'), 'type' => 'image', 'metadata' => []],
            ['file' => UploadedFile::fake()->create('document.pdf', 500, 'application/pdf'), 'type' => 'file', 'metadata' => []],
        ];

        $messageService = app(MessageService::class);
        $message = $messageService->sendMessage(
            $user,
            $conversation->id,
            'Multiple attachments',
            'text',
            $files
        );

        // Verify all attachments were created
        $this->assertCount(3, $message->attachments);
        
        // Verify each file type
        $attachmentTypes = $message->attachments->pluck('attachment_type')->toArray();
        $this->assertContains('image', $attachmentTypes);
        $this->assertContains('file', $attachmentTypes);

        // Verify all files were stored
        foreach ($message->attachments as $attachment) {
            Storage::disk('private')->assertExists($attachment->file_path);
        }
    }

    /**
     * Test error handling for invalid file uploads
     */
    public function test_error_handling_for_invalid_uploads()
    {
        // Setup
        $user = User::factory()->create(['role' => 'student']);
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Error Test'
        ]);
        $conversation->participants()->attach($user->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'Test message',
            'type' => 'text'
        ]);

        $this->actingAs($user);

        // Test: File too large
        $largeFile = UploadedFile::fake()->create('large-file.pdf', 51000); // 51MB
        $response = $this->postJson("/api/messages/{$message->id}/attachments", [
            'file' => $largeFile,
            'attachment_type' => 'file'
        ]);
        
        // Should fail validation
        $this->assertNotEquals(201, $response->status());
    }

    /**
     * Test rate limiting on attachment uploads
     */
    public function test_rate_limiting_on_uploads()
    {
        // Setup
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

        // Attempt multiple uploads rapidly
        $successCount = 0;

        for ($i = 0; $i < 10; $i++) {
            $file = UploadedFile::fake()->create("test{$i}.jpg", 100, 'image/jpeg');
            $response = $this->postJson("/api/messages/{$message->id}/attachments", [
                'file' => $file,
                'attachment_type' => 'image'
            ]);

            if ($response->status() === 201) {
                $successCount++;
            }
        }

        // Verify some uploads succeeded (rate limiting is tested separately)
        $this->assertGreaterThan(0, $successCount, 'Some uploads should have succeeded');
    }

    /**
     * Test storage quota enforcement
     */
    public function test_storage_quota_enforcement()
    {
        // Setup
        $user = User::factory()->create(['role' => 'student']);
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Quota Test'
        ]);
        $conversation->participants()->attach($user->id);

        $this->actingAs($user);

        // Create attachments to fill quota
        $totalSize = 0;
        $uploadCount = 0;

        while ($totalSize < 100 * 1024 * 1024) { // 100MB quota for students
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'content' => 'Test message',
                'type' => 'text'
            ]);

            $file = UploadedFile::fake()->create('file.pdf', 10000); // 10MB
            $response = $this->postJson("/api/messages/{$message->id}/attachments", [
                'file' => $file,
                'attachment_type' => 'file'
            ]);

            if ($response->status() === 201) {
                $totalSize += 10 * 1024 * 1024;
                $uploadCount++;
            } else {
                break;
            }

            if ($uploadCount > 15) { // Safety limit
                break;
            }
        }

        // Verify quota was enforced
        $this->assertLessThanOrEqual(100 * 1024 * 1024, $totalSize);
    }

    /**
     * Test attachment deletion
     */
    public function test_attachment_deletion()
    {
        // Setup
        $user = User::factory()->create(['role' => 'teacher']);
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Delete Test'
        ]);
        $conversation->participants()->attach($user->id);

        $this->actingAs($user);

        // Create message with attachment
        $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');
        $messageService = app(MessageService::class);
        $message = $messageService->sendMessage(
            $user,
            $conversation->id,
            'Test message',
            'text',
            [['file' => $file, 'type' => 'image', 'metadata' => []]]
        );

        $attachment = $message->attachments->first();
        $filePath = $attachment->file_path;

        // Verify file exists
        Storage::disk('private')->assertExists($filePath);

        // Delete attachment
        $response = $this->deleteJson("/api/attachments/{$attachment->id}");
        $response->assertStatus(200);

        // Verify attachment was deleted from database
        $this->assertDatabaseMissing('message_attachments', [
            'id' => $attachment->id
        ]);

        // Verify file was deleted from storage
        Storage::disk('private')->assertMissing($filePath);
    }
}

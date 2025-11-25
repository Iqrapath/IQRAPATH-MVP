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

class MessageAttachmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    /**
     * **Feature: message-attachments, Property 17: Authorization check**
     * **Validates: Requirements 4.5**
     *
     * Property: For any attachment download request, only conversation participants should be able to access the file
     */
    public function test_authorization_check_property()
    {
        // Test that participants CAN access attachments
        for ($i = 0; $i < 10; $i++) {
            // Create conversation with participants
            $participant1 = User::factory()->create(['role' => 'student']);
            $participant2 = User::factory()->create(['role' => 'teacher']);
            $nonParticipant = User::factory()->create(['role' => 'student']);

            $conversation = Conversation::create([
                'type' => 'direct',
                'subject' => 'Test Conversation'
            ]);

            $conversation->participants()->attach([$participant1->id, $participant2->id]);

            // Create message with attachment
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $participant1->id,
                'content' => 'Test message',
                'type' => 'text'
            ]);

            $attachment = MessageAttachment::create([
                'message_id' => $message->id,
                'filename' => 'test.jpg',
                'original_filename' => 'test.jpg',
                'file_path' => 'message-attachments/image/2025/11/test.jpg',
                'file_size' => 1024,
                'mime_type' => 'image/jpeg',
                'attachment_type' => 'image'
            ]);

            // Test participant 1 can access
            $this->actingAs($participant1);
            $response = $this->getJson("/api/attachments/{$attachment->id}/url");
            $response->assertStatus(200);
            $response->assertJson(['success' => true]);

            // Test participant 2 can access
            $this->actingAs($participant2);
            $response = $this->getJson("/api/attachments/{$attachment->id}/url");
            $response->assertStatus(200);
            $response->assertJson(['success' => true]);

            // Test non-participant CANNOT access
            $this->actingAs($nonParticipant);
            $response = $this->getJson("/api/attachments/{$attachment->id}/url");
            $response->assertStatus(403);
            $response->assertJson([
                'success' => false,
                'message' => 'Unauthorized to access this attachment'
            ]);
        }
    }

    /**
     * Test that only message sender can delete attachments
     */
    public function test_only_sender_can_delete_attachment()
    {
        for ($i = 0; $i < 5; $i++) {
            $sender = User::factory()->create(['role' => 'student']);
            $otherParticipant = User::factory()->create(['role' => 'teacher']);

            $conversation = Conversation::create([
                'type' => 'direct',
                'subject' => 'Test Conversation'
            ]);

            $conversation->participants()->attach([$sender->id, $otherParticipant->id]);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'content' => 'Test message',
                'type' => 'text'
            ]);

            $attachment = MessageAttachment::create([
                'message_id' => $message->id,
                'filename' => 'test.jpg',
                'original_filename' => 'test.jpg',
                'file_path' => 'message-attachments/image/2025/11/test.jpg',
                'file_size' => 1024,
                'mime_type' => 'image/jpeg',
                'attachment_type' => 'image'
            ]);

            // Sender can delete
            $this->actingAs($sender);
            $response = $this->deleteJson("/api/attachments/{$attachment->id}");
            $response->assertStatus(200);
            $response->assertJson(['success' => true]);

            // Create another attachment for the other participant test
            $attachment2 = MessageAttachment::create([
                'message_id' => $message->id,
                'filename' => 'test2.jpg',
                'original_filename' => 'test2.jpg',
                'file_path' => 'message-attachments/image/2025/11/test2.jpg',
                'file_size' => 1024,
                'mime_type' => 'image/jpeg',
                'attachment_type' => 'image'
            ]);

            // Other participant cannot delete
            $this->actingAs($otherParticipant);
            $response = $this->deleteJson("/api/attachments/{$attachment2->id}");
            $response->assertStatus(403);
            $response->assertJson([
                'success' => false,
                'message' => 'Unauthorized to delete this attachment'
            ]);
        }
    }

    /**
     * Test that only participants can upload attachments
     */
    public function test_only_participants_can_upload_attachments()
    {
        for ($i = 0; $i < 5; $i++) {
            $participant = User::factory()->create(['role' => 'student']);
            $nonParticipant = User::factory()->create(['role' => 'student']);

            $conversation = Conversation::create([
                'type' => 'direct',
                'subject' => 'Test Conversation'
            ]);

            $conversation->participants()->attach($participant->id);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $participant->id,
                'content' => 'Test message',
                'type' => 'text'
            ]);

            $file = UploadedFile::fake()->create('test.jpg', 100);

            // Participant can upload
            $this->actingAs($participant);
            $response = $this->postJson("/api/messages/{$message->id}/attachments", [
                'file' => $file,
                'attachment_type' => 'image'
            ]);
            $response->assertStatus(201);
            $response->assertJson(['success' => true]);

            // Non-participant cannot upload
            $file2 = UploadedFile::fake()->create('test2.jpg', 100);
            $this->actingAs($nonParticipant);
            $response = $this->postJson("/api/messages/{$message->id}/attachments", [
                'file' => $file2,
                'attachment_type' => 'image'
            ]);
            $response->assertStatus(403);
            $response->assertJson([
                'success' => false,
                'message' => 'Unauthorized to upload attachments to this message'
            ]);
        }
    }
}

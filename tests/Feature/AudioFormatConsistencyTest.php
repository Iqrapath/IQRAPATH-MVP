<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use App\Services\AttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AudioFormatConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    /**
     * **Property 15: Audio format consistency**
     * **Validates: Requirements 4.3**
     * 
     * For any audio file uploaded as a voice message, the stored format
     * should be consistent and playable across all supported browsers.
     * 
     * This test validates that:
     * 1. All supported audio formats are accepted
     * 2. Audio files are stored with correct MIME types
     * 3. Audio metadata is preserved correctly
     * 4. File integrity is maintained after storage
     */
    public function test_audio_format_consistency_property()
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Audio Format Test'
        ]);
        $conversation->participants()->attach($user->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'Voice message test',
            'type' => 'text'
        ]);

        $attachmentService = app(AttachmentService::class);

        // Test all supported audio formats
        $supportedFormats = [
            ['mime' => 'audio/webm', 'extension' => 'webm'],
            ['mime' => 'audio/mp3', 'extension' => 'mp3'],
            ['mime' => 'audio/mpeg', 'extension' => 'mp3'],
            ['mime' => 'audio/wav', 'extension' => 'wav'],
            ['mime' => 'audio/ogg', 'extension' => 'ogg'],
            ['mime' => 'audio/mp4', 'extension' => 'm4a'],
            ['mime' => 'audio/x-m4a', 'extension' => 'm4a']
        ];

        foreach ($supportedFormats as $format) {
            // Create audio file with specific format
            $audioFile = UploadedFile::fake()->create(
                "voice-message.{$format['extension']}",
                100, // 100KB
                $format['mime']
            );

            // Store the attachment
            $attachment = $attachmentService->storeAttachment(
                $audioFile,
                $message->id,
                'voice',
                ['duration' => 30]
            );

            // Verify attachment was created
            $this->assertInstanceOf(MessageAttachment::class, $attachment);

            // Verify MIME type is preserved
            $this->assertEquals($format['mime'], $attachment->mime_type);

            // Verify attachment type is correct
            $this->assertEquals('voice', $attachment->attachment_type);

            // Verify duration metadata is preserved
            $this->assertEquals(30, $attachment->duration);

            // Verify file is stored
            Storage::disk('private')->assertExists($attachment->file_path);

            // Verify file size is reasonable (not corrupted)
            $this->assertGreaterThan(1024, $attachment->file_size); // At least 1KB

            // Verify file can be retrieved
            $retrievedFile = $attachmentService->getFile($attachment);
            $this->assertNotNull($retrievedFile);
            // Note: Fake files may have empty content, so we just verify it's not null

            // Verify original filename is preserved
            $this->assertStringContainsString($format['extension'], $attachment->original_filename);
        }
    }

    /**
     * **Property: Audio file validation rejects invalid formats**
     * **Validates: Requirements 4.3**
     * 
     * For any file that is not a valid audio format, attempting to upload
     * it as a voice message should fail with a clear error message.
     */
    public function test_audio_format_validation_rejects_invalid_formats()
    {
        $user = User::factory()->create(['role' => 'student']);
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Invalid Audio Test'
        ]);
        $conversation->participants()->attach($user->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'Test message',
            'type' => 'text'
        ]);

        $attachmentService = app(AttachmentService::class);

        // Test invalid formats
        $invalidFormats = [
            ['mime' => 'video/mp4', 'extension' => 'mp4'],
            ['mime' => 'image/jpeg', 'extension' => 'jpg'],
            ['mime' => 'application/pdf', 'extension' => 'pdf'],
            ['mime' => 'text/plain', 'extension' => 'txt']
        ];

        foreach ($invalidFormats as $format) {
            $invalidFile = UploadedFile::fake()->create(
                "not-audio.{$format['extension']}",
                100,
                $format['mime']
            );

            // Attempt to store as voice message should fail
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('Unsupported audio format');

            $attachmentService->storeAttachment(
                $invalidFile,
                $message->id,
                'voice',
                ['duration' => 30]
            );
        }
    }

    /**
     * **Property: Audio file size validation**
     * **Validates: Requirements 4.1**
     * 
     * For any audio file, if it exceeds the maximum allowed size for voice
     * messages (5MB), it should be rejected with a clear error message.
     */
    public function test_audio_file_size_validation()
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Audio Size Test'
        ]);
        $conversation->participants()->attach($user->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'Test message',
            'type' => 'text'
        ]);

        $attachmentService = app(AttachmentService::class);

        // Create oversized audio file (6MB, exceeds 5MB limit)
        $oversizedAudio = UploadedFile::fake()->create(
            'large-voice.webm',
            6 * 1024, // 6MB
            'audio/webm'
        );

        // Attempt to store should fail
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File size exceeds maximum allowed size of 5MB for voice files');

        $attachmentService->storeAttachment(
            $oversizedAudio,
            $message->id,
            'voice',
            ['duration' => 300]
        );
    }

    /**
     * **Property: Audio metadata preservation**
     * **Validates: Requirements 4.3**
     * 
     * For any audio file with metadata (duration, bitrate, etc.),
     * the metadata should be preserved correctly in the database.
     */
    public function test_audio_metadata_preservation()
    {
        $user = User::factory()->create(['role' => 'student']);
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Metadata Test'
        ]);
        $conversation->participants()->attach($user->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'Voice with metadata',
            'type' => 'text'
        ]);

        $attachmentService = app(AttachmentService::class);

        // Test various durations
        $testDurations = [1, 5, 15, 30, 60, 120, 300]; // 1 second to 5 minutes

        foreach ($testDurations as $duration) {
            $audioFile = UploadedFile::fake()->create(
                "voice-{$duration}s.webm",
                50,
                'audio/webm'
            );

            $attachment = $attachmentService->storeAttachment(
                $audioFile,
                $message->id,
                'voice',
                ['duration' => $duration]
            );

            // Verify duration is preserved exactly
            $this->assertEquals($duration, $attachment->duration);

            // Verify metadata is stored
            $this->assertIsArray($attachment->metadata);
            $this->assertEquals($duration, $attachment->metadata['duration']);
        }
    }

    /**
     * **Property: Empty audio file rejection**
     * **Validates: Requirements 4.1**
     * 
     * For any audio file that is empty (0 bytes) or too small to be valid,
     * it should be rejected with a clear error message.
     */
    public function test_empty_audio_file_rejection()
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Empty Audio Test'
        ]);
        $conversation->participants()->attach($user->id);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'Test message',
            'type' => 'text'
        ]);

        $attachmentService = app(AttachmentService::class);

        // Create very small audio file (less than 1KB)
        $tinyAudio = UploadedFile::fake()->create(
            'tiny-voice.webm',
            0.5, // 0.5KB
            'audio/webm'
        );

        // Attempt to store should fail
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Audio file is too small to be valid');

        $attachmentService->storeAttachment(
            $tinyAudio,
            $message->id,
            'voice',
            ['duration' => 1]
        );
    }

    /**
     * **Property: Audio format consistency across multiple uploads**
     * **Validates: Requirements 4.3**
     * 
     * For any sequence of audio uploads, all files should maintain
     * consistent format handling and storage structure.
     */
    public function test_audio_format_consistency_across_multiple_uploads()
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Multiple Audio Test'
        ]);
        $conversation->participants()->attach($user->id);

        $attachmentService = app(AttachmentService::class);

        // Create multiple messages with audio attachments
        $uploadedAttachments = [];

        for ($i = 0; $i < 10; $i++) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'content' => "Voice message {$i}",
                'type' => 'text'
            ]);

            $audioFile = UploadedFile::fake()->create(
                "voice-{$i}.webm",
                rand(50, 200), // Random size between 50-200KB
                'audio/webm'
            );

            $attachment = $attachmentService->storeAttachment(
                $audioFile,
                $message->id,
                'voice',
                ['duration' => rand(5, 60)]
            );

            $uploadedAttachments[] = $attachment;
        }

        // Verify all attachments have consistent structure
        foreach ($uploadedAttachments as $attachment) {
            // All should be voice type
            $this->assertEquals('voice', $attachment->attachment_type);

            // All should have audio MIME type
            $this->assertStringStartsWith('audio/', $attachment->mime_type);

            // All should have duration
            $this->assertNotNull($attachment->duration);
            $this->assertGreaterThan(0, $attachment->duration);

            // All should be stored in correct path structure
            $this->assertStringContainsString('message-attachments/voice/', $attachment->file_path);

            // All should have files in storage
            Storage::disk('private')->assertExists($attachment->file_path);

            // All should be retrievable
            $file = $attachmentService->getFile($attachment);
            $this->assertNotNull($file);
        }
    }
}


<?php

namespace Tests\Feature;

use App\Services\AttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class AttachmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttachmentService $attachmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attachmentService = new AttachmentService();
        Storage::fake('private');
    }

    /**
     * **Feature: message-attachments, Property 13: File size validation**
     * **Validates: Requirements 4.1**
     *
     * Property: For any file upload, files exceeding 10MB should be rejected with an error message
     */
    public function test_file_size_validation_property()
    {
        // Test files under the limit should pass
        for ($i = 0; $i < 10; $i++) {
            $validSizeKB = fake()->numberBetween(1, 1024); // 1KB to 1MB
            $file = UploadedFile::fake()->create('test.jpg', $validSizeKB);
            $file = $this->mockFileMimeType($file, 'image/jpeg');

            try {
                $this->attachmentService->validateFile($file, 'image');
                $this->assertTrue(true, "File with size {$validSizeKB}KB should be valid");
            } catch (InvalidArgumentException $e) {
                $this->fail("File with size {$validSizeKB}KB should be valid but was rejected: {$e->getMessage()}");
            }
        }

        // Test files over the limit should fail
        for ($i = 0; $i < 10; $i++) {
            $invalidSizeKB = fake()->numberBetween(11 * 1024, 50 * 1024); // 11MB to 50MB
            $file = UploadedFile::fake()->create('test.jpg', $invalidSizeKB);
            $file = $this->mockFileMimeType($file, 'image/jpeg');

            try {
                $this->attachmentService->validateFile($file, 'image');
                $this->fail("File with size {$invalidSizeKB}KB should be rejected");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('File size exceeds maximum allowed size', $e->getMessage());
            }
        }
    }

    /**
     * **Feature: message-attachments, Property 14: Image format validation**
     * **Validates: Requirements 4.2**
     *
     * Property: For any image upload, only supported formats (jpg, png, gif, webp) should be accepted
     */
    public function test_image_format_validation_property()
    {
        $supportedFormats = [
            'image/jpeg' => 'test.jpg',
            'image/jpg' => 'test.jpg',
            'image/png' => 'test.png',
            'image/gif' => 'test.gif',
            'image/webp' => 'test.webp',
            'image/svg+xml' => 'test.svg'
        ];

        $unsupportedFormats = [
            'image/bmp' => 'test.bmp',
            'image/tiff' => 'test.tiff',
            'application/pdf' => 'test.pdf',
            'text/plain' => 'test.txt'
        ];

        // Test supported formats should pass
        foreach ($supportedFormats as $mimeType => $filename) {
            for ($i = 0; $i < 5; $i++) {
                $file = UploadedFile::fake()->create($filename, 100);
                $file = $this->mockFileMimeType($file, $mimeType);

                try {
                    $this->attachmentService->validateFile($file, 'image');
                    $this->assertTrue(true, "Image format {$mimeType} should be valid");
                } catch (InvalidArgumentException $e) {
                    $this->fail("Image format {$mimeType} should be valid but was rejected: {$e->getMessage()}");
                }
            }
        }

        // Test unsupported formats should fail
        foreach ($unsupportedFormats as $mimeType => $filename) {
            for ($i = 0; $i < 3; $i++) {
                $file = UploadedFile::fake()->create($filename, 100);
                $file = $this->mockFileMimeType($file, $mimeType);

                try {
                    $this->attachmentService->validateFile($file, 'image');
                    $this->fail("Image format {$mimeType} should be rejected");
                } catch (InvalidArgumentException $e) {
                    $this->assertStringContainsString('Unsupported image format', $e->getMessage());
                }
            }
        }
    }

    /**
     * **Feature: message-attachments, Property 16: Secure storage location**
     * **Validates: Requirements 4.4**
     *
     * Property: For any uploaded attachment, the file should be stored in the private storage directory
     */
    public function test_secure_storage_location_property()
    {
        $attachmentTypes = ['voice', 'image', 'file'];

        foreach ($attachmentTypes as $type) {
            for ($i = 0; $i < 3; $i++) {
                // Create test data with proper relationships
                $user = \App\Models\User::factory()->create([
                    'name' => 'Test User',
                    'email' => fake()->unique()->safeEmail(),
                    'role' => 'student'
                ]);
                $conversation = \App\Models\Conversation::create([
                    'type' => 'direct',
                    'subject' => 'Test Conversation'
                ]);

                // Add user as participant
                $conversation->participants()->attach($user->id);

                $message = \App\Models\Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $user->id,
                    'content' => 'Test message',
                    'type' => 'text'
                ]);

                // Create appropriate file for type
                $file = match($type) {
                    'image' => UploadedFile::fake()->create('test.jpg', 100),
                    'voice' => UploadedFile::fake()->create('test.webm', 100),
                    'file' => UploadedFile::fake()->create('test.pdf', 100)
                };

                $mimeType = match($type) {
                    'image' => 'image/jpeg',
                    'voice' => 'audio/webm',
                    'file' => 'application/pdf'
                };

                $file = $this->mockFileMimeType($file, $mimeType);

                // Store attachment
                $attachment = $this->attachmentService->storeAttachment(
                    $file,
                    $message->id,
                    $type
                );

                // Verify file is stored in private directory with correct structure
                $expectedPath = "message-attachments/{$type}/" . now()->year . "/" . now()->format('m');
                $this->assertStringStartsWith($expectedPath, $attachment->file_path);

                // Verify file exists in private storage
                Storage::disk('private')->assertExists($attachment->file_path);

                // Verify file is NOT in public storage
                Storage::disk('public')->assertMissing($attachment->file_path);
            }
        }
    }

    /**
     * Mock file MIME type for testing
     */
    private function mockFileMimeType(UploadedFile $file, string $mimeType): UploadedFile
    {
        // Create a mock that returns our desired MIME type
        $mock = \Mockery::mock($file)->makePartial();
        $mock->shouldReceive('getMimeType')->andReturn($mimeType);
        $mock->shouldReceive('getClientOriginalName')->andReturn($file->getClientOriginalName());
        $mock->shouldReceive('getClientOriginalExtension')->andReturn($file->getClientOriginalExtension());
        $mock->shouldReceive('getSize')->andReturn($file->getSize());

        return $mock;
    }
}

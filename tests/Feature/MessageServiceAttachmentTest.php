<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessageServiceAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private MessageService $messageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->messageService = app(MessageService::class);
        Storage::fake('private');
        
        // Fake broadcasting to avoid Pusher connection errors
        \Illuminate\Support\Facades\Event::fake([
            \App\Events\MessageSent::class,
            \App\Events\MessageRead::class,
        ]);
    }
    
    /**
     * Mock file MIME type for testing
     */
    private function mockFileMimeType(UploadedFile $file, string $mimeType): UploadedFile
    {
        $mock = \Mockery::mock($file)->makePartial();
        $mock->shouldReceive('getMimeType')->andReturn($mimeType);
        $mock->shouldReceive('getClientOriginalName')->andReturn($file->getClientOriginalName());
        $mock->shouldReceive('getClientOriginalExtension')->andReturn($file->getClientOriginalExtension());
        $mock->shouldReceive('getSize')->andReturn($file->getSize());
        
        return $mock;
    }

    /**
     * **Feature: message-attachments, Property 6: All images uploaded**
     * **Validates: Requirements 2.3**
     *
     * Property: For any set of images sent with a message, all images should be successfully uploaded and linked to the message
     */
    public function test_all_images_uploaded_property()
    {
        for ($i = 0; $i < 10; $i++) {
            // Create conversation with participants
            $sender = User::factory()->create(['role' => 'student']);
            $recipient = User::factory()->create(['role' => 'teacher']);

            $conversation = Conversation::create([
                'type' => 'direct',
                'subject' => 'Test Conversation'
            ]);

            $conversation->participants()->attach([$sender->id, $recipient->id]);

            // Generate random number of images (1-5)
            $imageCount = fake()->numberBetween(1, 5);
            $files = [];

            for ($j = 0; $j < $imageCount; $j++) {
                $file = UploadedFile::fake()->create("image{$j}.jpg", 100);
                $file = $this->mockFileMimeType($file, 'image/jpeg');
                
                $files[] = [
                    'file' => $file,
                    'type' => 'image',
                    'metadata' => []
                ];
            }

            // Send message with images
            $message = $this->messageService->sendMessage(
                $sender,
                $conversation->id,
                'Message with images',
                'text',
                $files
            );

            // Verify all images were uploaded
            $this->assertCount($imageCount, $message->attachments);

            // Verify each attachment is properly stored
            foreach ($message->attachments as $attachment) {
                $this->assertEquals('image', $attachment->attachment_type);
                $this->assertNotNull($attachment->file_path);
                $this->assertGreaterThan(0, $attachment->file_size);
                
                // Verify file exists in storage
                Storage::disk('private')->assertExists($attachment->file_path);
            }
        }
    }

    /**
     * **Feature: message-attachments, Property 10: All files uploaded**
     * **Validates: Requirements 3.3**
     *
     * Property: For any set of files sent with a message, all files should be successfully uploaded and linked to the message
     */
    public function test_all_files_uploaded_property()
    {
        for ($i = 0; $i < 10; $i++) {
            // Create conversation with participants
            $sender = User::factory()->create(['role' => 'teacher']);
            $recipient = User::factory()->create(['role' => 'student']);

            $conversation = Conversation::create([
                'type' => 'direct',
                'subject' => 'Test Conversation'
            ]);

            $conversation->participants()->attach([$sender->id, $recipient->id]);

            // Generate random number of files (1-5) with mixed types
            $fileCount = fake()->numberBetween(1, 5);
            $files = [];
            $fileTypes = ['image', 'file', 'voice'];

            for ($j = 0; $j < $fileCount; $j++) {
                $type = $fileTypes[array_rand($fileTypes)];
                
                $fileData = match($type) {
                    'image' => [
                        'file' => $this->mockFileMimeType(
                            UploadedFile::fake()->create("image{$j}.jpg", 100),
                            'image/jpeg'
                        ),
                        'type' => 'image',
                        'metadata' => []
                    ],
                    'voice' => [
                        'file' => $this->mockFileMimeType(
                            UploadedFile::fake()->create("voice{$j}.webm", 50),
                            'audio/webm'
                        ),
                        'type' => 'voice',
                        'metadata' => ['duration' => fake()->numberBetween(1, 60)]
                    ],
                    'file' => [
                        'file' => $this->mockFileMimeType(
                            UploadedFile::fake()->create("document{$j}.pdf", 200),
                            'application/pdf'
                        ),
                        'type' => 'file',
                        'metadata' => []
                    ]
                };
                
                $files[] = $fileData;
            }

            // Send message with multiple file types
            $message = $this->messageService->sendMessage(
                $sender,
                $conversation->id,
                'Message with multiple attachments',
                'text',
                $files
            );

            // Verify all files were uploaded
            $this->assertCount($fileCount, $message->attachments);

            // Verify each attachment is properly stored
            foreach ($message->attachments as $index => $attachment) {
                $this->assertNotNull($attachment->file_path);
                $this->assertGreaterThan(0, $attachment->file_size);
                $this->assertContains($attachment->attachment_type, ['image', 'voice', 'file']);
                
                // Verify file exists in storage
                Storage::disk('private')->assertExists($attachment->file_path);
                
                // Verify voice attachments have duration
                if ($attachment->attachment_type === 'voice') {
                    $this->assertNotNull($attachment->duration);
                    $this->assertGreaterThan(0, $attachment->duration);
                }
            }
        }
    }

    /**
     * Test that message with no attachments works correctly
     */
    public function test_message_without_attachments()
    {
        $sender = User::factory()->create(['role' => 'student']);
        $recipient = User::factory()->create(['role' => 'teacher']);

        $conversation = Conversation::create([
            'type' => 'direct',
            'subject' => 'Test Conversation'
        ]);

        $conversation->participants()->attach([$sender->id, $recipient->id]);

        // Send message without attachments
        $message = $this->messageService->sendMessage(
            $sender,
            $conversation->id,
            'Simple text message',
            'text',
            []
        );

        // Verify no attachments
        $this->assertCount(0, $message->attachments);
        $this->assertEquals('Simple text message', $message->content);
    }

    /**
     * Test that voice message with duration metadata is stored correctly
     */
    public function test_voice_message_with_duration()
    {
        for ($i = 0; $i < 5; $i++) {
            $sender = User::factory()->create(['role' => 'student']);
            $recipient = User::factory()->create(['role' => 'teacher']);

            $conversation = Conversation::create([
                'type' => 'direct',
                'subject' => 'Test Conversation'
            ]);

            $conversation->participants()->attach([$sender->id, $recipient->id]);

            $duration = fake()->numberBetween(1, 120);
            
            $file = UploadedFile::fake()->create('voice.webm', 100);
            $file = $this->mockFileMimeType($file, 'audio/webm');
            
            $files = [[
                'file' => $file,
                'type' => 'voice',
                'metadata' => ['duration' => $duration]
            ]];

            $message = $this->messageService->sendMessage(
                $sender,
                $conversation->id,
                'Voice message',
                'text',
                $files
            );

            $this->assertCount(1, $message->attachments);
            $attachment = $message->attachments->first();
            
            $this->assertEquals('voice', $attachment->attachment_type);
            $this->assertEquals($duration, $attachment->duration);
        }
    }
}

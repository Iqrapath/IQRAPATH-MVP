<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    /**
     * Seed conversations and messages for testing
     */
    public function run(): void
    {
        $messageService = app(MessageService::class);
        
        // Get some users
        $students = User::where('role', 'student')->take(3)->get();
        $teachers = User::where('role', 'teacher')->take(3)->get();
        $admin = User::where('role', 'admin')->first();

        if ($students->isEmpty() || $teachers->isEmpty()) {
            $this->command->warn('Not enough users to create messages. Run UserSeeder first.');
            return;
        }

        // Create student-teacher conversations (with active bookings)
        foreach ($students as $index => $student) {
            $teacher = $teachers[$index % $teachers->count()];

            // Use getOrCreateConversation to prevent duplicates
            $conversation = $messageService->getOrCreateConversation(
                [$student->id, $teacher->id],
                'direct'
            );
            
            // Update subject if not set
            if (!$conversation->subject) {
                $firstSubject = $teacher->subjects()->first();
                $subjectName = $firstSubject ? $firstSubject->name : 'lessons';
                $conversation->update(['subject' => "Discussion about {$subjectName}"]);
            }

            // Create some messages
            $messages = [
                [
                    'sender_id' => $student->id,
                    'content' => "Hello! I'm looking forward to our lesson tomorrow.",
                    'type' => 'text',
                ],
                [
                    'sender_id' => $teacher->id,
                    'content' => "Great! I've prepared some materials for you. What topics would you like to focus on?",
                    'type' => 'text',
                ],
                [
                    'sender_id' => $student->id,
                    'content' => "I'd like to work on Tajweed rules, especially the rules of Noon Sakinah.",
                    'type' => 'text',
                ],
                [
                    'sender_id' => $teacher->id,
                    'content' => "Perfect! We'll cover Ikhfa, Idgham, Iqlab, and Izhar. See you tomorrow!",
                    'type' => 'text',
                ],
            ];

            foreach ($messages as $messageData) {
                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $messageData['sender_id'],
                    'content' => $messageData['content'],
                    'type' => $messageData['type'],
                ]);

                // Mark as read if sent by student (teacher has read it)
                if ($messageData['sender_id'] === $student->id) {
                    $message->statuses()->create([
                        'user_id' => $teacher->id,
                        'status' => 'read',
                        'status_at' => now()->subMinutes(rand(1, 60)),
                    ]);
                } else {
                    // Student hasn't read teacher's message yet
                    $message->statuses()->create([
                        'user_id' => $student->id,
                        'status' => 'delivered',
                        'status_at' => now(),
                    ]);
                }
            }

            $this->command->info("Created conversation between {$student->name} and {$teacher->name}");
        }

        // Create admin conversations
        if ($admin) {
            foreach ($students->take(2) as $student) {
                // Use getOrCreateConversation to prevent duplicates
                $conversation = $messageService->getOrCreateConversation(
                    [$student->id, $admin->id],
                    'direct'
                );
                
                // Update subject if not set
                if (!$conversation->subject) {
                    $conversation->update(['subject' => 'Support Request']);
                }

                $messages = [
                    [
                        'sender_id' => $student->id,
                        'content' => "Hi, I have a question about my subscription.",
                        'type' => 'text',
                    ],
                    [
                        'sender_id' => $admin->id,
                        'content' => "Hello! I'd be happy to help. What would you like to know?",
                        'type' => 'text',
                    ],
                ];

                foreach ($messages as $messageData) {
                    Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => $messageData['sender_id'],
                        'content' => $messageData['content'],
                        'type' => $messageData['type'],
                    ]);
                }

                $this->command->info("Created admin conversation with {$student->name}");
            }
        }

        $this->command->info('Message seeding completed!');
    }
}

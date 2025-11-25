<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use App\Models\Booking;
use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Events\TypingIndicator;
use App\Events\MessageDeleted;
use App\Events\ConversationArchived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class MessageService
{
    public function __construct(
        private NotificationService $notificationService,
        private AttachmentService $attachmentService
    ) {}

    /**
     * Get or create a conversation between users.
     *
     * @param  array  $participantIds
     * @param  string  $type
     * @param  string|null  $contextType
     * @param  int|null  $contextId
     * @return Conversation
     */
    public function getOrCreateConversation(
        array $participantIds,
        string $type = 'direct',
        ?string $contextType = null,
        ?int $contextId = null
    ): Conversation {
        // For direct conversations, check if one already exists
        if ($type === 'direct' && count($participantIds) === 2) {
            $existing = Conversation::where('type', 'direct')
                ->whereHas('participants', function($q) use ($participantIds) {
                    $q->whereIn('user_id', $participantIds);
                }, '=', 2)
                ->whereDoesntHave('participants', function($q) use ($participantIds) {
                    $q->whereNotIn('user_id', $participantIds);
                })
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($participantIds, $type, $contextType, $contextId) {
            $conversation = Conversation::create([
                'type' => $type,
                'context_type' => $contextType,
                'context_id' => $contextId,
            ]);

            $conversation->participants()->attach($participantIds);

            return $conversation;
        });
    }

    /**
     * Get user's conversations with pagination.
     *
     * @param  User  $user
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function getUserConversations(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return Conversation::forUser($user->id)
            ->notArchived($user->id)
            ->with([
                'latestMessage.sender',
                'participants' => fn($q) => $q->where('user_id', '!=', $user->id),
            ])
            ->orderByDesc(function($query) {
                $query->select('created_at')
                    ->from('messages')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->latest()
                    ->limit(1);
            })
            ->paginate($perPage);
    }

    /**
     * Get messages in a conversation with pagination.
     *
     * @param  int  $conversationId
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function getConversationMessages(int $conversationId, int $perPage = 50): LengthAwarePaginator
    {
        return Message::inConversation($conversationId)
            ->with(['sender', 'attachments', 'statuses'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Archive a conversation for a user.
     *
     * @param  User  $user
     * @param  int  $conversationId
     * @return void
     */
    public function archiveConversation(User $user, int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);
        $conversation->participants()
            ->updateExistingPivot($user->id, ['is_archived' => true]);
        
        // Broadcast archive event to user's private channel
        broadcast(new ConversationArchived($conversation, $user, true));
    }

    /**
     * Unarchive a conversation for a user.
     *
     * @param  User  $user
     * @param  int  $conversationId
     * @return void
     */
    public function unarchiveConversation(User $user, int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);
        $conversation->participants()
            ->updateExistingPivot($user->id, ['is_archived' => false]);
        
        // Broadcast unarchive event to user's private channel
        broadcast(new ConversationArchived($conversation, $user, false));
    }

    /**
     * Mute a conversation for a user.
     *
     * @param  User  $user
     * @param  int  $conversationId
     * @return void
     */
    public function muteConversation(User $user, int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);
        $conversation->participants()
            ->updateExistingPivot($user->id, ['is_muted' => true]);
    }

    /**
     * Unmute a conversation for a user.
     *
     * @param  User  $user
     * @param  int  $conversationId
     * @return void
     */
    public function unmuteConversation(User $user, int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);
        $conversation->participants()
            ->updateExistingPivot($user->id, ['is_muted' => false]);
    }

    /**
     * Send a message in a conversation.
     *
     * @param  User  $sender
     * @param  int  $conversationId
     * @param  string  $content
     * @param  string  $type
     * @param  array  $files Array of UploadedFile instances with attachment metadata
     * @return Message
     * @throws \Exception
     */
    public function sendMessage(
        User $sender,
        int $conversationId,
        string $content,
        string $type = 'text',
        array $files = []
    ): Message {
        return DB::transaction(function () use ($sender, $conversationId, $content, $type, $files) {
            $conversation = Conversation::with('participants')->findOrFail($conversationId);

            // Verify sender is a participant
            if (!$conversation->participants()->where('user_id', $sender->id)->exists()) {
                throw new \Exception('User is not a participant in this conversation');
            }

            // Create message
            $message = Message::create([
                'conversation_id' => $conversationId,
                'sender_id' => $sender->id,
                'content' => $content,
                'type' => $type,
            ]);

            // Handle file attachments using AttachmentService
            foreach ($files as $fileData) {
                if (isset($fileData['file']) && $fileData['file'] instanceof UploadedFile) {
                    $attachmentType = $fileData['type'] ?? 'file';
                    $metadata = $fileData['metadata'] ?? [];
                    
                    $this->attachmentService->storeAttachment(
                        $fileData['file'],
                        $message->id,
                        $attachmentType,
                        $metadata
                    );
                }
            }

            // Reload message with attachments
            $message->load('attachments');

            // Create status records for all participants except sender
            $recipients = $conversation->participants()
                ->where('user_id', '!=', $sender->id)
                ->get();

            foreach ($recipients as $recipient) {
                $message->statuses()->create([
                    'user_id' => $recipient->id,
                    'status' => 'sent',
                    'status_at' => now(),
                ]);

                // Check if conversation is muted for this recipient
                $isMuted = $conversation->participants()
                    ->where('user_id', $recipient->id)
                    ->first()
                    ?->pivot
                    ?->is_muted ?? false;

                // Send notification if not muted
                if (!$isMuted) {
                    $notificationBody = $content;
                    
                    // Customize notification based on attachment type
                    if ($message->attachments->isNotEmpty()) {
                        $firstAttachment = $message->attachments->first();
                        $notificationBody = match($firstAttachment->attachment_type) {
                            'voice' => '🎤 Voice message',
                            'image' => '📷 Image',
                            'file' => '📎 File attachment',
                            default => $content
                        };
                    }
                    
                    $this->notificationService->createNotification(
                        $recipient,
                        'message',
                        [
                            'title' => "New message from {$sender->name}",
                            'body' => substr($notificationBody, 0, 100),
                            'sender_id' => $sender->id,
                            'sender_name' => $sender->name,
                            'conversation_id' => $conversationId,
                            'message_id' => $message->id,
                        ],
                        'info'
                    );
                }
            }

            // Broadcast message to conversation channel
            broadcast(new MessageSent($message))->toOthers();

            return $message;
        });
    }

    /**
     * Mark messages as read in a conversation.
     *
     * @param  User  $user
     * @param  int  $conversationId
     * @return void
     */
    public function markAsRead(User $user, int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Update participant's last_read_at
        $conversation->participants()
            ->updateExistingPivot($user->id, [
                'last_read_at' => now(),
            ]);

        // Update message statuses
        $unreadMessages = Message::inConversation($conversationId)
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('statuses', function($q) use ($user) {
                $q->where('user_id', $user->id)->where('status', 'read');
            })
            ->get();

        foreach ($unreadMessages as $message) {
            $message->statuses()->updateOrCreate(
                ['user_id' => $user->id, 'message_id' => $message->id],
                ['status' => 'read', 'status_at' => now()]
            );
            
            // Broadcast read receipt
            broadcast(new MessageRead($message, $user))->toOthers();
        }
    }

    /**
     * Mark a specific message as read.
     *
     * @param  User  $user
     * @param  int  $messageId
     * @return void
     */
    public function markMessageAsRead(User $user, int $messageId): void
    {
        $message = Message::findOrFail($messageId);

        $message->statuses()->updateOrCreate(
            ['user_id' => $user->id, 'message_id' => $message->id],
            ['status' => 'read', 'status_at' => now()]
        );
        
        // Broadcast read receipt
        broadcast(new MessageRead($message, $user))->toOthers();
    }

    /**
     * Mark all messages as read for a user.
     *
     * @param  User  $user
     * @return void
     */
    public function markAllAsRead(User $user): void
    {
        $conversations = Conversation::forUser($user->id)->get();

        foreach ($conversations as $conversation) {
            $this->markAsRead($user, $conversation->id);
        }
    }

    /**
     * Search messages for a user.
     *
     * @param  User  $user
     * @param  string  $query
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function searchMessages(User $user, string $query, int $perPage = 20): LengthAwarePaginator
    {
        return Message::whereHas('conversation.participants', fn($q) => $q->where('user_id', $user->id))
            ->where('content', 'like', "%{$query}%")
            ->with(['conversation', 'sender'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Search conversations by participant name.
     *
     * @param  User  $user
     * @param  string  $participantName
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function searchByParticipant(User $user, string $participantName, int $perPage = 20): LengthAwarePaginator
    {
        return Conversation::forUser($user->id)
            ->whereHas('participants', function($q) use ($participantName, $user) {
                $q->where('user_id', '!=', $user->id)
                    ->where('name', 'like', "%{$participantName}%");
            })
            ->with(['latestMessage.sender', 'participants'])
            ->orderByDesc(function($query) {
                $query->select('created_at')
                    ->from('messages')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->latest()
                    ->limit(1);
            })
            ->paginate($perPage);
    }

    /**
     * Search messages by date range.
     *
     * @param  User  $user
     * @param  Carbon  $startDate
     * @param  Carbon  $endDate
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function searchByDateRange(User $user, Carbon $startDate, Carbon $endDate, int $perPage = 20): LengthAwarePaginator
    {
        return Message::whereHas('conversation.participants', fn($q) => $q->where('user_id', $user->id))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['conversation', 'sender'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Validate if a user can message another user based on role rules.
     *
     * @param  User  $sender
     * @param  User  $recipient
     * @return bool
     */
    public function canUserMessage(User $sender, User $recipient): bool
    {
        // Students can message their teachers
        if ($sender->role === 'student' && $recipient->role === 'teacher') {
            return $this->hasActiveSession($sender, $recipient);
        }

        // Teachers can message their students
        if ($sender->role === 'teacher' && $recipient->role === 'student') {
            return $this->hasActiveSession($recipient, $sender);
        }

        // Guardians can message their children's teachers
        if ($sender->role === 'guardian' && $recipient->role === 'teacher') {
            return $this->isTeacherOfGuardiansChild($sender, $recipient);
        }

        // Teachers can message guardians of their students
        if ($sender->role === 'teacher' && $recipient->role === 'guardian') {
            return $this->isTeacherOfGuardiansChild($recipient, $sender);
        }

        // Admins can message anyone
        if (in_array($sender->role, ['admin', 'super-admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Check if student and teacher have an active session.
     *
     * @param  User  $student
     * @param  User  $teacher
     * @return bool
     */
    private function hasActiveSession(User $student, User $teacher): bool
    {
        return Booking::where('student_id', $student->id)
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', ['pending', 'approved', 'completed', 'upcoming', 'in_progress'])
            ->exists();
    }

    /**
     * Check if teacher teaches any of guardian's children.
     *
     * @param  User  $guardian
     * @param  User  $teacher
     * @return bool
     */
    private function isTeacherOfGuardiansChild(User $guardian, User $teacher): bool
    {
        $childIds = $guardian->children()->pluck('id');
        
        return Booking::whereIn('student_id', $childIds)
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', ['approved', 'completed'])
            ->exists();
    }

    /**
     * Broadcast typing indicator.
     *
     * @param  User  $user
     * @param  int  $conversationId
     * @param  bool  $isTyping
     * @return void
     */
    public function broadcastTypingIndicator(User $user, int $conversationId, bool $isTyping): void
    {
        $conversation = Conversation::findOrFail($conversationId);
        
        // Verify user is a participant
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            throw new \Exception('User is not a participant in this conversation');
        }
        
        // Broadcast typing indicator to others in the conversation
        broadcast(new TypingIndicator($conversationId, $user, $isTyping))->toOthers();
    }
}

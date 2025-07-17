<?php

namespace App\Services;

use App\Models\User;
use App\Models\Message;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MessageService
{
    /**
     * Send a message from one user to another
     *
     * @param User $sender
     * @param User $recipient
     * @param string $content
     * @param string $type
     * @param array|null $metadata
     * @return Message
     */
    public function sendMessage(User $sender, User $recipient, string $content, string $type = 'text', ?array $metadata = null): Message
    {
        return Message::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'content' => $content,
            'type' => $type,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get messages between two users
     *
     * @param User $user1
     * @param User $user2
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getMessagesBetweenUsers(User $user1, User $user2, int $perPage = 15): LengthAwarePaginator
    {
        return Message::where(function ($query) use ($user1, $user2) {
                $query->where('sender_id', $user1->id)
                    ->where('recipient_id', $user2->id);
            })
            ->orWhere(function ($query) use ($user1, $user2) {
                $query->where('sender_id', $user2->id)
                    ->where('recipient_id', $user1->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get all messages for a user
     *
     * @param User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserMessages(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Message::where('sender_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get unread messages for a user
     *
     * @param User $user
     * @return Collection
     */
    public function getUnreadMessages(User $user): Collection
    {
        return Message::where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get unread message count for a user
     *
     * @param User $user
     * @return int
     */
    public function getUnreadMessageCount(User $user): int
    {
        return Message::where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Mark a message as read
     *
     * @param Message $message
     * @return bool
     */
    public function markAsRead(Message $message): bool
    {
        return $message->markAsRead()->save();
    }

    /**
     * Mark all messages as read for a user
     *
     * @param User $user
     * @return bool
     */
    public function markAllAsRead(User $user): bool
    {
        return Message::where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Delete a message
     *
     * @param Message $message
     * @return bool
     */
    public function deleteMessage(Message $message): bool
    {
        return $message->delete();
    }
} 
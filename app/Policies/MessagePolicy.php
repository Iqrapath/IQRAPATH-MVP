<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\MessageService;

class MessagePolicy
{
    public function __construct(
        private MessageService $messageService
    ) {}

    /**
     * Determine if the user can view any conversations.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view their own conversations
        return true;
    }

    /**
     * Determine if the user can view a specific conversation.
     *
     * @param  User  $user
     * @param  Conversation  $conversation
     * @return bool
     */
    public function view(User $user, Conversation $conversation): bool
    {
        // User must be a participant in the conversation
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if the user can create a conversation with another user.
     *
     * @param  User  $user
     * @param  User  $recipient
     * @return bool
     */
    public function create(User $user, User $recipient): bool
    {
        // Use MessageService to validate role-based messaging rules
        return $this->messageService->canUserMessage($user, $recipient);
    }

    /**
     * Determine if the user can update a message.
     *
     * @param  User  $user
     * @param  Message  $message
     * @return bool
     */
    public function update(User $user, Message $message): bool
    {
        // Only the sender can update their own message
        return $user->id === $message->sender_id;
    }

    /**
     * Determine if the user can delete a message.
     *
     * @param  User  $user
     * @param  Message  $message
     * @return bool
     */
    public function delete(User $user, Message $message): bool
    {
        // Sender can delete their own message, or admins can delete any message
        return $user->id === $message->sender_id 
            || in_array($user->role, ['admin', 'super-admin']);
    }

    /**
     * Determine if the user can send a message in a conversation.
     *
     * @param  User  $user
     * @param  Conversation  $conversation
     * @return bool
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        // User must be a participant in the conversation
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if the user can archive a conversation.
     *
     * @param  User  $user
     * @param  Conversation  $conversation
     * @return bool
     */
    public function archive(User $user, Conversation $conversation): bool
    {
        // User must be a participant to archive
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if the user can mute a conversation.
     *
     * @param  User  $user
     * @param  Conversation  $conversation
     * @return bool
     */
    public function mute(User $user, Conversation $conversation): bool
    {
        // User must be a participant to mute
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }
}

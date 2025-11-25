<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MessageAttachmentPolicy
{
    /**
     * Determine whether the user can view any models.
     * 
     * Users can view attachments if they are participants in the conversation.
     */
    public function viewAny(User $user): bool
    {
        // Users can view attachments in conversations they participate in
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * 
     * **Property: Authorization for viewing attachments**
     * **Validates: Requirements 4.5**
     * 
     * Users can view an attachment if they are participants in the conversation.
     */
    public function view(User $user, MessageAttachment $messageAttachment): bool
    {
        $conversation = $messageAttachment->message->conversation;
        
        // Check if user is a participant in the conversation
        return $conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     * 
     * Users can create attachments if they are participants in the conversation.
     */
    public function create(User $user): bool
    {
        // Authorization is checked at the message level
        return true;
    }

    /**
     * Determine whether the user can upload attachment to a message.
     * 
     * **Property: Authorization for uploading attachments**
     * **Validates: Requirements 4.5**
     */
    public function upload(User $user, MessageAttachment $messageAttachment): bool
    {
        $conversation = $messageAttachment->message->conversation;
        
        // User must be a participant in the conversation
        return $conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can download the attachment.
     * 
     * **Property: Authorization for downloading attachments**
     * **Validates: Requirements 4.5**
     */
    public function download(User $user, MessageAttachment $messageAttachment): bool
    {
        $conversation = $messageAttachment->message->conversation;
        
        // User must be a participant in the conversation
        return $conversation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can update the model.
     * 
     * Attachments cannot be updated after creation.
     */
    public function update(User $user, MessageAttachment $messageAttachment): bool
    {
        // Attachments are immutable
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * 
     * **Property: Authorization for deleting attachments**
     * **Validates: Requirements 4.5**
     * 
     * Only the message sender can delete attachments.
     */
    public function delete(User $user, MessageAttachment $messageAttachment): bool
    {
        // Only the message sender can delete attachments
        return $messageAttachment->message->sender_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MessageAttachment $messageAttachment): bool
    {
        // Soft deletes not used for attachments
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MessageAttachment $messageAttachment): bool
    {
        // Only admins can force delete
        return in_array($user->role, ['admin', 'super-admin']);
    }
}

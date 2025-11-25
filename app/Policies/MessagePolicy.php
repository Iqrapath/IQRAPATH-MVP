<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\MessageService;
use App\Services\AuthorizationAuditService;

class MessagePolicy
{
    public function __construct(
        private MessageService $messageService,
        private AuthorizationAuditService $auditService
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
        $granted = $conversation->participants()->where('user_id', $user->id)->exists();
        
        // Log authorization attempt
        $this->auditService->logAuthorizationAttempt(
            $user,
            'view_conversation',
            'Conversation',
            $conversation->id,
            $granted,
            $granted ? null : 'not_participant'
        );
        
        // Check for suspicious patterns on failure
        if (!$granted) {
            $this->auditService->checkForSuspiciousPattern($user);
        }
        
        return $granted;
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
        $granted = $this->messageService->canUserMessage($user, $recipient);
        
        // Log role violation if denied
        if (!$granted) {
            $this->auditService->logRoleViolation(
                $user,
                $recipient,
                $this->getRoleViolationReason($user, $recipient)
            );
            
            // Check for suspicious patterns
            $this->auditService->checkForSuspiciousPattern($user);
        } else {
            // Log successful authorization
            $this->auditService->logAuthorizationAttempt(
                $user,
                'create_conversation',
                'User',
                $recipient->id,
                true,
                null
            );
        }
        
        return $granted;
    }
    
    /**
     * Get the reason for role-based messaging violation.
     *
     * @param  User  $sender
     * @param  User  $recipient
     * @return string
     */
    private function getRoleViolationReason(User $sender, User $recipient): string
    {
        if ($sender->role === 'student' && $recipient->role === 'teacher') {
            return 'no_active_booking';
        }
        
        if ($sender->role === 'teacher' && $recipient->role === 'student') {
            return 'no_active_booking';
        }
        
        if ($sender->role === 'guardian' && $recipient->role === 'teacher') {
            return 'teacher_not_teaching_child';
        }
        
        if ($sender->role === 'teacher' && $recipient->role === 'guardian') {
            return 'not_teaching_guardians_child';
        }
        
        return 'role_restriction';
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
        $granted = $user->id === $message->sender_id;
        
        // Log authorization attempt
        $this->auditService->logAuthorizationAttempt(
            $user,
            'update_message',
            'Message',
            $message->id,
            $granted,
            $granted ? null : 'not_sender'
        );
        
        if (!$granted) {
            $this->auditService->checkForSuspiciousPattern($user);
        }
        
        return $granted;
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
        $isSender = $user->id === $message->sender_id;
        $isAdmin = in_array($user->role, ['admin', 'super-admin']);
        $granted = $isSender || $isAdmin;
        
        // Log admin override if admin is deleting someone else's message
        if ($granted && $isAdmin && !$isSender) {
            $this->auditService->logAdminOverride(
                $user,
                'delete_message',
                'Message',
                $message->id,
                'Admin deletion of user message'
            );
        } else {
            // Log regular authorization attempt
            $this->auditService->logAuthorizationAttempt(
                $user,
                'delete_message',
                'Message',
                $message->id,
                $granted,
                $granted ? null : 'not_authorized'
            );
            
            if (!$granted) {
                $this->auditService->checkForSuspiciousPattern($user);
            }
        }
        
        return $granted;
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
        // First check: User must be a participant in the conversation
        $isParticipant = $conversation->participants()->where('user_id', $user->id)->exists();
        
        if (!$isParticipant) {
            $this->auditService->logAuthorizationAttempt(
                $user,
                'send_message',
                'Conversation',
                $conversation->id,
                false,
                'not_participant'
            );
            $this->auditService->checkForSuspiciousPattern($user);
            return false;
        }
        
        // Second check: Validate role-based messaging rules
        // Get the other participant(s) in the conversation
        $otherParticipants = $conversation->participants()
            ->where('user_id', '!=', $user->id)
            ->get();
        
        // For direct conversations (2 participants), validate role-based rules
        if ($conversation->type === 'direct' && $otherParticipants->count() === 1) {
            $otherUser = $otherParticipants->first();
            $canMessage = $this->messageService->canUserMessage($user, $otherUser);
            
            if (!$canMessage) {
                // Log role violation
                $this->auditService->logRoleViolation(
                    $user,
                    $otherUser,
                    $this->getRoleViolationReason($user, $otherUser)
                );
                $this->auditService->checkForSuspiciousPattern($user);
                return false;
            }
        }
        
        // All checks passed
        $this->auditService->logAuthorizationAttempt(
            $user,
            'send_message',
            'Conversation',
            $conversation->id,
            true,
            null
        );
        
        return true;
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
        $granted = $conversation->participants()->where('user_id', $user->id)->exists();
        
        // Log authorization attempt
        $this->auditService->logAuthorizationAttempt(
            $user,
            'archive_conversation',
            'Conversation',
            $conversation->id,
            $granted,
            $granted ? null : 'not_participant'
        );
        
        if (!$granted) {
            $this->auditService->checkForSuspiciousPattern($user);
        }
        
        return $granted;
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
        $granted = $conversation->participants()->where('user_id', $user->id)->exists();
        
        // Log authorization attempt
        $this->auditService->logAuthorizationAttempt(
            $user,
            'mute_conversation',
            'Conversation',
            $conversation->id,
            $granted,
            $granted ? null : 'not_participant'
        );
        
        if (!$granted) {
            $this->auditService->checkForSuspiciousPattern($user);
        }
        
        return $granted;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MessageController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private MessageService $messageService
    ) {}

    /**
     * Send a message in a conversation.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Check authentication first
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to perform this action',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required|string|max:10000',
            'type' => 'sometimes|in:text,image,file,voice,system',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file|max:10240', // 10MB max per file
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);
        
        try {
            $this->authorize('sendMessage', $conversation);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            // Check if it's a role violation
            $user = $request->user();
            $otherParticipant = $conversation->participants()
                ->where('user_id', '!=', $user->id)
                ->first();
            
            if ($otherParticipant && !$this->messageService->canUserMessage($user, $otherParticipant)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Forbidden',
                    'message' => 'You cannot message this user',
                    'code' => 'ROLE_RESTRICTION',
                    'details' => [
                        'reason' => $this->getRoleViolationReason($user, $otherParticipant),
                        'your_role' => $user->role,
                        'recipient_role' => $otherParticipant->role,
                    ],
                ], 403);
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not authorized to send messages in this conversation',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'reason' => 'not_participant',
                    'resource_type' => 'Conversation',
                    'resource_id' => $conversation->id,
                ],
            ], 403);
        }

        try {
            // Transform uploaded files into the format expected by MessageService
            $attachments = [];
            $uploadedFiles = $request->file('attachments', []);
            $messageType = $request->input('type', 'text');
            
            foreach ($uploadedFiles as $file) {
                $attachments[] = [
                    'file' => $file,
                    'type' => $messageType === 'image' ? 'image' : ($messageType === 'voice' ? 'voice' : 'file'),
                    'metadata' => []
                ];
            }
            
            $message = $this->messageService->sendMessage(
                $request->user(),
                (int) $request->conversation_id,
                $request->content,
                $messageType,
                $attachments
            );

            return response()->json([
                'success' => true,
                'data' => $message->load(['sender', 'attachments']),
                'message' => 'Message sent successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update a message.
     *
     * @param  Request  $request
     * @param  int  $messageId
     * @return JsonResponse
     */
    public function update(Request $request, int $messageId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to perform this action',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        $message = Message::findOrFail($messageId);
        
        try {
            $this->authorize('update', $message);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not authorized to update this message',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'reason' => 'not_sender',
                    'resource_type' => 'Message',
                    'resource_id' => $message->id,
                ],
            ], 403);
        }

        $message->update([
            'content' => $request->content,
        ]);

        return response()->json([
            'success' => true,
            'data' => $message->load(['sender', 'attachments']),
            'message' => 'Message updated successfully',
        ]);
    }

    /**
     * Delete a message.
     *
     * @param  Request  $request
     * @param  int  $messageId
     * @return JsonResponse
     */
    public function destroy(Request $request, int $messageId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to perform this action',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        $message = Message::findOrFail($messageId);
        
        try {
            $this->authorize('delete', $message);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not authorized to delete this message',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'reason' => $request->user()->id === $message->sender_id ? 'insufficient_permissions' : 'not_sender',
                    'resource_type' => 'Message',
                    'resource_id' => $message->id,
                ],
            ], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully',
        ]);
    }

    /**
     * Mark a specific message as read.
     *
     * @param  Request  $request
     * @param  int  $messageId
     * @return JsonResponse
     */
    public function markAsRead(Request $request, int $messageId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to perform this action',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        $message = Message::findOrFail($messageId);
        
        // Verify user is a participant in the conversation
        $conversation = $message->conversation;
        
        try {
            $this->authorize('view', $conversation);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not authorized to mark this message as read',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'reason' => 'not_recipient',
                    'resource_type' => 'Message',
                    'resource_id' => $message->id,
                ],
            ], 403);
        }

        $this->messageService->markMessageAsRead($request->user(), $messageId);

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read',
        ]);
    }

    /**
     * Mark all messages as read for the user.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to perform this action',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        $this->messageService->markAllAsRead($request->user());

        return response()->json([
            'success' => true,
            'message' => 'All messages marked as read',
        ]);
    }

    /**
     * Get role violation reason for error response.
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
}

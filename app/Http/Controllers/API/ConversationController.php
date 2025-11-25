<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ConversationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private MessageService $messageService
    ) {}

    /**
     * Get user's conversations.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to perform this action',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        try {
            $this->authorize('viewAny', Conversation::class);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not authorized to view conversations',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'reason' => 'insufficient_permissions',
                    'resource_type' => 'Conversation',
                ],
            ], 403);
        }

        $conversations = $this->messageService->getUserConversations(
            $request->user(),
            $request->input('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ]);
    }

    /**
     * Get a specific conversation with messages.
     *
     * @param  Request  $request
     * @param  int  $conversationId
     * @return JsonResponse
     */
    public function show(Request $request, int $conversationId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to perform this action',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        $conversation = Conversation::with('participants')->findOrFail($conversationId);
        
        try {
            $this->authorize('view', $conversation);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not authorized to view this conversation',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'reason' => 'not_participant',
                    'resource_type' => 'Conversation',
                    'resource_id' => $conversation->id,
                ],
            ], 403);
        }

        $messages = $this->messageService->getConversationMessages(
            $conversationId,
            $request->input('per_page', 50)
        );

        // Mark messages as read
        $this->messageService->markAsRead($request->user(), $conversationId);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation,
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * Create a new conversation.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
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
            'recipient_id' => 'required|exists:users,id',
            'type' => 'sometimes|in:direct,group',
            'context_type' => 'nullable|string',
            'context_id' => 'nullable|integer',
        ]);

        $recipient = User::findOrFail($request->recipient_id);
        
        // Check authorization
        try {
            $this->authorize('create', [Conversation::class, $recipient]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $user = $request->user();
            
            // Check if it's a role violation
            if (!$this->messageService->canUserMessage($user, $recipient)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Forbidden',
                    'message' => 'You cannot message this user',
                    'code' => 'ROLE_RESTRICTION',
                    'details' => [
                        'reason' => $this->getRoleViolationReason($user, $recipient),
                        'your_role' => $user->role,
                        'recipient_role' => $recipient->role,
                    ],
                ], 403);
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not authorized to create a conversation with this user',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'reason' => 'insufficient_permissions',
                    'resource_type' => 'Conversation',
                ],
            ], 403);
        }

        $conversation = $this->messageService->getOrCreateConversation(
            [$request->user()->id, $request->recipient_id],
            $request->input('type', 'direct'),
            $request->input('context_type'),
            $request->input('context_id')
        );

        return response()->json([
            'success' => true,
            'data' => $conversation->load('participants'),
            'message' => 'Conversation created successfully',
        ], 201);
    }

    /**
     * Archive a conversation.
     *
     * @param  Request  $request
     * @param  int  $conversationId
     * @return JsonResponse
     */
    public function archive(Request $request, int $conversationId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to perform this action',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        $conversation = Conversation::findOrFail($conversationId);
        
        try {
            $this->authorize('archive', $conversation);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not authorized to archive this conversation',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'reason' => 'not_participant',
                    'resource_type' => 'Conversation',
                    'resource_id' => $conversation->id,
                ],
            ], 403);
        }

        $this->messageService->archiveConversation($request->user(), $conversationId);

        return response()->json([
            'success' => true,
            'message' => 'Conversation archived successfully',
        ]);
    }

    /**
     * Unarchive a conversation.
     *
     * @param  Request  $request
     * @param  int  $conversationId
     * @return JsonResponse
     */
    public function unarchive(Request $request, int $conversationId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to perform this action',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        $conversation = Conversation::findOrFail($conversationId);
        
        try {
            $this->authorize('archive', $conversation);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not authorized to unarchive this conversation',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'reason' => 'not_participant',
                    'resource_type' => 'Conversation',
                    'resource_id' => $conversation->id,
                ],
            ], 403);
        }

        $this->messageService->unarchiveConversation($request->user(), $conversationId);

        return response()->json([
            'success' => true,
            'message' => 'Conversation unarchived successfully',
        ]);
    }

    /**
     * Mute a conversation.
     *
     * @param  Request  $request
     * @param  int  $conversationId
     * @return JsonResponse
     */
    public function mute(Request $request, int $conversationId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to perform this action',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        $conversation = Conversation::findOrFail($conversationId);
        
        try {
            $this->authorize('mute', $conversation);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not authorized to mute this conversation',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'reason' => 'not_participant',
                    'resource_type' => 'Conversation',
                    'resource_id' => $conversation->id,
                ],
            ], 403);
        }

        $this->messageService->muteConversation($request->user(), $conversationId);

        return response()->json([
            'success' => true,
            'message' => 'Conversation muted successfully',
        ]);
    }

    /**
     * Unmute a conversation.
     *
     * @param  Request  $request
     * @param  int  $conversationId
     * @return JsonResponse
     */
    public function unmute(Request $request, int $conversationId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to perform this action',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        $conversation = Conversation::findOrFail($conversationId);
        
        try {
            $this->authorize('mute', $conversation);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not authorized to unmute this conversation',
                'code' => 'AUTHORIZATION_FAILED',
                'details' => [
                    'reason' => 'not_participant',
                    'resource_type' => 'Conversation',
                    'resource_id' => $conversation->id,
                ],
            ], 403);
        }

        $this->messageService->unmuteConversation($request->user(), $conversationId);

        return response()->json([
            'success' => true,
            'message' => 'Conversation unmuted successfully',
        ]);
    }

    /**
     * Send typing indicator for a conversation.
     *
     * @param  Request  $request
     * @param  int  $conversationId
     * @return JsonResponse
     */
    public function typing(Request $request, int $conversationId): JsonResponse
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
            'is_typing' => 'required|boolean',
        ]);

        $conversation = Conversation::findOrFail($conversationId);
        
        // Verify user is a participant
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not a participant in this conversation',
                'code' => 'AUTHORIZATION_FAILED',
            ], 403);
        }

        // Broadcast typing indicator
        broadcast(new \App\Events\TypingIndicator(
            $conversationId,
            $request->user(),
            $request->boolean('is_typing')
        ))->toOthers();

        return response()->json([
            'success' => true,
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

    /**
     * Mark all messages in conversation as read.
     *
     * @param  Request  $request
     * @param  int  $conversationId
     * @return JsonResponse
     */
    public function markAsRead(Request $request, int $conversationId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to perform this action',
                'code' => 'AUTH_REQUIRED',
            ], 401);
        }

        $conversation = Conversation::findOrFail($conversationId);
        
        // Verify user is a participant
        if (!$conversation->participants()->where('user_id', $request->user()->id)->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You are not a participant in this conversation',
                'code' => 'AUTHORIZATION_FAILED',
            ], 403);
        }

        // Mark all messages as read
        $this->messageService->markAsRead($request->user(), $conversationId);

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read',
        ]);
    }

}

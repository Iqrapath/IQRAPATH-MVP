<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ConversationController extends Controller
{
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
        $this->authorize('viewAny', Conversation::class);

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
        $conversation = Conversation::with('participants')->findOrFail($conversationId);
        $this->authorize('view', $conversation);

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
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'type' => 'sometimes|in:direct,group',
            'context_type' => 'nullable|string',
            'context_id' => 'nullable|integer',
        ]);

        $recipient = User::findOrFail($request->recipient_id);
        
        // Check authorization
        $this->authorize('create', [Conversation::class, $recipient]);

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
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('archive', $conversation);

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
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('archive', $conversation);

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
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('mute', $conversation);

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
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('mute', $conversation);

        $this->messageService->unmuteConversation($request->user(), $conversationId);

        return response()->json([
            'success' => true,
            'message' => 'Conversation unmuted successfully',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
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
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required|string|max:10000',
            'type' => 'sometimes|in:text,image,file,system',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file|max:10240', // 10MB max per file
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);
        $this->authorize('sendMessage', $conversation);

        try {
            $message = $this->messageService->sendMessage(
                $request->user(),
                $request->conversation_id,
                $request->content,
                $request->input('type', 'text'),
                $request->file('attachments', [])
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
        $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        $message = Message::findOrFail($messageId);
        $this->authorize('update', $message);

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
        $message = Message::findOrFail($messageId);
        $this->authorize('delete', $message);

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
        $message = Message::findOrFail($messageId);
        
        // Verify user is a participant in the conversation
        $conversation = $message->conversation;
        $this->authorize('view', $conversation);

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
        $this->messageService->markAllAsRead($request->user());

        return response()->json([
            'success' => true,
            'message' => 'All messages marked as read',
        ]);
    }
}

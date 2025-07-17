<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MessageController extends Controller
{
    protected MessageService $messageService;

    /**
     * Create a new controller instance.
     *
     * @param MessageService $messageService
     */
    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $messages = $this->messageService->getUserMessages($request->user(), $perPage);
        
        return MessageResource::collection($messages);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMessageRequest $request): MessageResource
    {
        $sender = $request->user();
        $recipient = User::findOrFail($request->input('recipient_id'));
        $content = $request->input('content');
        $type = $request->input('type', 'text');
        $metadata = $request->input('metadata');
        
        $message = $this->messageService->sendMessage($sender, $recipient, $content, $type, $metadata);
        
        return new MessageResource($message);
    }

    /**
     * Display the specified resource.
     */
    public function show(Message $message): MessageResource
    {
        return new MessageResource($message);
    }

    /**
     * Get messages between the authenticated user and another user.
     */
    public function withUser(Request $request, User $user): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $messages = $this->messageService->getMessagesBetweenUsers($request->user(), $user, $perPage);
        
        return MessageResource::collection($messages);
    }

    /**
     * Mark the specified message as read.
     */
    public function markAsRead(Message $message): JsonResponse
    {
        // Check if the user is the recipient of the message
        if ($message->recipient_id !== request()->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $this->messageService->markAsRead($message);
        
        return response()->json([
            'message' => 'Message marked as read',
            'data' => new MessageResource($message)
        ]);
    }

    /**
     * Mark all messages as read for the authenticated user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->messageService->markAllAsRead($request->user());
        
        return response()->json([
            'message' => 'All messages marked as read'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Message $message): JsonResponse
    {
        $this->messageService->deleteMessage($message);
        
        return response()->json([
            'message' => 'Message deleted successfully'
        ]);
    }
}

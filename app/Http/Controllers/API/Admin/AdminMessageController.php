<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminMessageController extends Controller
{
    public function __construct(
        private MessageService $messageService
    ) {
        $this->middleware('role:admin,super-admin');
    }

    /**
     * Get all conversations with filters.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Conversation::with(['participants', 'latestMessage.sender']);

        // Apply filters
        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('context_type')) {
            $query->where('context_type', $request->context_type);
        }

        $conversations = $query->orderBy('updated_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ]);
    }

    /**
     * View any conversation (admin override).
     *
     * @param  Request  $request
     * @param  int  $conversationId
     * @return JsonResponse
     */
    public function show(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::with('participants')->findOrFail($conversationId);
        
        $messages = Message::inConversation($conversationId)
            ->with(['sender', 'attachments', 'statuses'])
            ->orderBy('created_at', 'asc')
            ->paginate($request->input('per_page', 100));

        // Log admin access
        Log::info('Admin viewed conversation', [
            'admin_id' => $request->user()->id,
            'admin_name' => $request->user()->name,
            'conversation_id' => $conversationId,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $conversation,
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * Get messaging statistics.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = [
            'total_conversations' => Conversation::count(),
            'total_messages' => Message::count(),
            'total_participants' => DB::table('conversation_participants')->count(),
            'messages_today' => Message::whereDate('created_at', today())->count(),
            'messages_this_week' => Message::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'messages_this_month' => Message::whereMonth('created_at', now()->month)->count(),
            'active_conversations_today' => Conversation::whereHas('messages', function($q) {
                $q->whereDate('created_at', today());
            })->count(),
            'by_type' => Message::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get(),
            'by_conversation_type' => Conversation::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get flagged conversations.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function flagged(Request $request): JsonResponse
    {
        $flaggedConversations = Conversation::whereNotNull('metadata->flagged')
            ->with(['participants', 'latestMessage.sender'])
            ->orderBy('updated_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $flaggedConversations,
        ]);
    }

    /**
     * Flag a conversation for review.
     *
     * @param  Request  $request
     * @param  int  $conversationId
     * @return JsonResponse
     */
    public function flag(Request $request, int $conversationId): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $conversation = Conversation::findOrFail($conversationId);
        
        $metadata = $conversation->metadata ?? [];
        $metadata['flagged'] = [
            'flagged_at' => now()->toISOString(),
            'flagged_by' => $request->user()->id,
            'reason' => $request->reason,
        ];
        
        $conversation->update(['metadata' => $metadata]);

        // Log admin action
        Log::info('Admin flagged conversation', [
            'admin_id' => $request->user()->id,
            'admin_name' => $request->user()->name,
            'conversation_id' => $conversationId,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation flagged for review',
        ]);
    }

    /**
     * Unflag a conversation.
     *
     * @param  Request  $request
     * @param  int  $conversationId
     * @return JsonResponse
     */
    public function unflag(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        
        $metadata = $conversation->metadata ?? [];
        unset($metadata['flagged']);
        
        $conversation->update(['metadata' => $metadata]);

        // Log admin action
        Log::info('Admin unflagged conversation', [
            'admin_id' => $request->user()->id,
            'admin_name' => $request->user()->name,
            'conversation_id' => $conversationId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation unflagged',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MessageController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display teacher messages page.
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Get conversations where user is a participant
        $conversations = Conversation::forUser($user->id)
            ->with([
                'participants' => fn($q) => $q->where('user_id', '!=', $user->id),
                'latestMessage.sender'
            ])
            ->orderByDesc(function($query) {
                $query->select('created_at')
                    ->from('messages')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->latest()
                    ->limit(1);
            })
            ->get();
        
        return Inertia::render('teacher/messages', [
            'conversations' => $conversations,
        ]);
    }
    
    /**
     * Display a specific conversation.
     *
     * @param  Request  $request
     * @param  int  $conversationId
     * @return Response
     */
    public function show(Request $request, int $conversationId): Response
    {
        $user = $request->user();
        
        // Get all conversations
        $conversations = Conversation::forUser($user->id)
            ->with([
                'participants' => fn($q) => $q->where('user_id', '!=', $user->id),
                'latestMessage.sender'
            ])
            ->orderByDesc(function($query) {
                $query->select('created_at')
                    ->from('messages')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->latest()
                    ->limit(1);
            })
            ->get();
        
        // Get selected conversation
        $selectedConversation = Conversation::with([
            'participants',
            'messages.sender',
            'messages.attachments'
        ])->findOrFail($conversationId);
        
        // Authorize access
        $this->authorize('view', $selectedConversation);
        
        // Get messages
        $messages = $selectedConversation->messages()
            ->with(['sender', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->get();
        
        return Inertia::render('teacher/messages', [
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'messages' => $messages,
        ]);
    }
}

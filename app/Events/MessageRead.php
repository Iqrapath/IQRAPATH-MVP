<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Message $message,
        public User $user
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];

        // Also broadcast to the message sender's user channel
        if ($this->message->sender_id) {
            $channels[] = new PrivateChannel('user.' . $this->message->sender_id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.read';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        // Reload message to get updated statuses
        $this->message->refresh();
        $this->message->load(['sender', 'statuses']);

        // Get the read status for this user
        $readStatus = $this->message->statuses()
            ->where('user_id', $this->user->id)
            ->where('status', 'read')
            ->first();

        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sender_id' => $this->message->sender_id,
                'content' => $this->message->content,
                'read_at' => $readStatus?->status_at?->toISOString(),
                'created_at' => $this->message->created_at->toISOString(),
            ],
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'read_at' => $readStatus?->status_at?->toISOString(),
        ];
    }
}

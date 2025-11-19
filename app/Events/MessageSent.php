<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Message $message
    ) {
        // Load relationships for broadcasting
        $this->message->load(['sender', 'attachments', 'statuses']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sender_id' => $this->message->sender_id,
                'sender' => [
                    'id' => $this->message->sender->id,
                    'name' => $this->message->sender->name,
                    'role' => $this->message->sender->role,
                ],
                'content' => $this->message->content,
                'type' => $this->message->type,
                'attachments' => $this->message->attachments->map(fn($attachment) => [
                    'id' => $attachment->id,
                    'filename' => $attachment->filename,
                    'original_filename' => $attachment->original_filename,
                    'file_size' => $attachment->file_size,
                    'mime_type' => $attachment->mime_type,
                ]),
                'created_at' => $this->message->created_at->toISOString(),
                'updated_at' => $this->message->updated_at->toISOString(),
            ],
        ];
    }
}

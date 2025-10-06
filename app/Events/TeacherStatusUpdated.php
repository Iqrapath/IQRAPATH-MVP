<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeacherStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $teacher;
    public array $statusData;
    public string $action;

    /**
     * Create a new event instance.
     */
    public function __construct(User $teacher, array $statusData, string $action = 'updated')
    {
        $this->teacher = $teacher;
        $this->statusData = $statusData;
        $this->action = $action;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.teachers'),
            new PrivateChannel("teacher.{$this->teacher->id}"),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'teacher_id' => $this->teacher->id,
            'status' => $this->statusData['status'],
            'can_approve' => $this->statusData['can_approve'],
            'approval_block_reason' => $this->statusData['approval_block_reason'],
            'verification_request_id' => $this->statusData['verification_request_id'],
            'last_updated' => $this->statusData['last_updated'],
            'action' => $this->action,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'teacher.status.updated';
    }
}

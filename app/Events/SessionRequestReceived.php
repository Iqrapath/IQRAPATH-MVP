<?php

namespace App\Events;

use App\Models\TeachingSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionRequestReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The session instance.
     *
     * @var \App\Models\TeachingSession
     */
    public $session;
    
    /**
     * The session data to broadcast.
     *
     * @var array
     */
    public $sessionData;

    /**
     * Create a new event instance.
     */
    public function __construct(TeachingSession $session)
    {
        $this->session = $session;
        
        // Format the session data for the frontend
        $this->sessionData = [
            'id' => $session->id,
            'student' => [
                'id' => $session->student->id,
                'name' => $session->student->name,
                'avatar' => $session->student->avatar,
                'is_online' => $session->student->isOnline(),
            ],
            'subject' => $session->subject->name,
            'scheduled_at' => $session->scheduled_at,
            'created_at' => $session->created_at,
            'status' => $session->status,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('teacher.' . $this->session->teacher_id),
        ];
    }
    
    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'session' => $this->sessionData,
        ];
    }
    
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'session.requested';
    }
} 
<?php

namespace App\Events;

use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionScheduled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user instance.
     *
     * @var \App\Models\User
     */
    public $user;

    /**
     * The session instance.
     *
     * @var \App\Models\TeachingSession
     */
    protected $session;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, TeachingSession $session)
    {
        $this->user = $user;
        $this->session = $session;
    }

    /**
     * Get the session instance.
     *
     * @return \App\Models\TeachingSession
     */
    public function getSession(): TeachingSession
    {
        return $this->session;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->user->id),
        ];
    }
} 
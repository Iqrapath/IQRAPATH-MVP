<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
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
     * The session data.
     *
     * @var array|\stdClass
     */
    protected $session;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, $session)
    {
        $this->user = $user;
        $this->session = $session;
    }

    /**
     * Get the session data.
     *
     * @return \stdClass
     */
    public function getSession()
    {
        return is_array($this->session) ? (object) $this->session : $this->session;
    }
} 
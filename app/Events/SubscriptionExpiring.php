<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiring
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user instance.
     *
     * @var \App\Models\User
     */
    public $user;

    /**
     * The subscription data.
     *
     * @var array|\stdClass
     */
    protected $subscription;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, $subscription)
    {
        $this->user = $user;
        $this->subscription = $subscription;
    }

    /**
     * Get the subscription data.
     *
     * @return \stdClass
     */
    public function getSubscription()
    {
        return is_array($this->subscription) ? (object) $this->subscription : $this->subscription;
    }
} 
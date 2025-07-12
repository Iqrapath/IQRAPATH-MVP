<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user instance.
     *
     * @var \App\Models\User
     */
    public $user;

    /**
     * The payment data.
     *
     * @var array
     */
    protected $payment;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, array $payment)
    {
        $this->user = $user;
        $this->payment = $payment;
    }

    /**
     * Get the payment data.
     *
     * @return \stdClass
     */
    public function getPayment()
    {
        return (object) $this->payment;
    }
} 
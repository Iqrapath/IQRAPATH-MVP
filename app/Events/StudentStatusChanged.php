<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $student;
    public string $oldStatus;
    public string $newStatus;
    public ?User $changedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(User $student, string $oldStatus, string $newStatus, ?User $changedBy = null)
    {
        $this->student = $student;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy ?? auth()->user();
    }
}


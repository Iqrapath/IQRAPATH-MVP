<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NotificationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Notification $notification): bool
    {
        return $notification->notifiable_type === User::class && 
               $notification->notifiable_id === $user->id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Notification $notification): bool
    {
        return $notification->notifiable_type === User::class && 
               $notification->notifiable_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Notification $notification): bool
    {
        return $notification->notifiable_type === User::class && 
               $notification->notifiable_id === $user->id;
    }

    /**
     * Determine whether the user can mark the notification as read.
     */
    public function markAsRead(User $user, Notification $notification): bool
    {
        return $notification->notifiable_type === User::class && 
               $notification->notifiable_id === $user->id;
    }
}

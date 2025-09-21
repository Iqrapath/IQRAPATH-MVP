<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Booking $booking): bool
    {
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Booking $booking): bool
    {
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the booking status.
     */
    public function updateStatus(User $user, Booking $booking): bool
    {
        return $user->role === 'super-admin' || $user->role === 'admin';
    }

    /**
     * Determine whether the user can perform bulk status updates.
     */
    public function bulkUpdateStatus(User $user): bool
    {
        return $user->role === 'super-admin' || $user->role === 'admin';
    }
}

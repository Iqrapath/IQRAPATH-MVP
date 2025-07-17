<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Get all notifications for a user
     *
     * @param User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserNotifications(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get unread notifications for a user
     *
     * @param User $user
     * @return Collection
     */
    public function getUnreadNotifications(User $user): Collection
    {
        return Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get unread notification count for a user
     *
     * @param User $user
     * @return int
     */
    public function getUnreadNotificationCount(User $user): int
    {
        return Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Mark a notification as read
     *
     * @param Notification $notification
     * @return bool
     */
    public function markAsRead(Notification $notification): bool
    {
        return $notification->markAsRead()->save();
    }

    /**
     * Mark all notifications as read for a user
     *
     * @param User $user
     * @return bool
     */
    public function markAllAsRead(User $user): bool
    {
        return Notification::where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Delete a notification
     *
     * @param Notification $notification
     * @return bool
     */
    public function deleteNotification(Notification $notification): bool
    {
        return $notification->delete();
    }
} 
<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Channel for user notifications
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    // Log the channel authorization attempt
    \Illuminate\Support\Facades\Log::info('Channel authorization attempt', [
        'channel' => "notifications.{$userId}",
        'user_id' => $user->id,
        'requested_user_id' => $userId,
        'authorized' => (int) $user->id === (int) $userId
    ]);
    
    // Always allow for now to debug
    return true;
});

// Channel for teacher session requests
Broadcast::channel('session-requests.{teacherId}', function ($user, $teacherId) {
    // Log the channel authorization attempt
    \Illuminate\Support\Facades\Log::info('Session request channel authorization', [
        'channel' => "session-requests.{$teacherId}",
        'user_id' => $user->id,
        'requested_teacher_id' => $teacherId,
        'is_teacher' => $user->hasRole('teacher') ?? false,
        'authorized' => (int) $user->id === (int) $teacherId && $user->hasRole('teacher')
    ]);
    
    // Always allow for now to debug
    return true;
});

// Channel for direct messages
Broadcast::channel('messages.{userId}', function ($user, $userId) {
    // Log the channel authorization attempt
    \Illuminate\Support\Facades\Log::info('Messages channel authorization', [
        'channel' => "messages.{$userId}",
        'user_id' => $user->id,
        'requested_user_id' => $userId,
        'authorized' => (int) $user->id === (int) $userId
    ]);
    
    // Always allow for now to debug
    return true;
});

<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// User private channel
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Admin notifications channel
Broadcast::channel('admin.notifications', function ($user) {
    return $user->hasRole('super-admin') || $user->hasRole('admin');
});

// Admin teachers channel for real-time teacher status updates
Broadcast::channel('admin.teachers', function ($user) {
    return in_array($user->role, ['admin', 'super-admin']);
});

// Teacher notifications channel
Broadcast::channel('teacher.notifications', function ($user) {
    return $user->hasRole('teacher');
});

// Individual teacher channel for status updates
Broadcast::channel('teacher.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id || in_array($user->role, ['admin', 'super-admin']);
});

// Student notifications channel
Broadcast::channel('student.notifications', function ($user) {
    return $user->hasRole('student');
});

// Guardian notifications channel
Broadcast::channel('guardian.notifications', function ($user) {
    return $user->hasRole('guardian');
});

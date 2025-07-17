<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\UserNotificationController;
use App\Http\Controllers\API\MessageController;

/*
|--------------------------------------------------------------------------
| Notification Routes
|--------------------------------------------------------------------------
|
| Here is where you can register notification-related routes for your application.
|
*/

// Test endpoint for notification system
Route::get('/test-notification', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Notification system is working',
        'timestamp' => now()->toDateTimeString()
    ]);
});

// Notification routes
Route::middleware('auth:sanctum')->group(function () {
    // User notifications
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])
        ->name('notifications.show');
    
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
    
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read.all');
    
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])
        ->name('notifications.destroy');
    
    // User-specific notification endpoints
    Route::get('/user/notifications', [UserNotificationController::class, 'index'])
        ->name('user.notifications.index');
    
    Route::get('/user/notifications/unread', [UserNotificationController::class, 'unread'])
        ->name('user.notifications.unread');
    
    Route::get('/user/notifications/count', [UserNotificationController::class, 'count'])
        ->name('user.notifications.count');
    
    // Message endpoints
    Route::apiResource('messages', MessageController::class);
    
    Route::get('/messages/user/{user}', [MessageController::class, 'withUser'])
        ->name('messages.with.user');
    
    Route::post('/messages/{message}/read', [MessageController::class, 'markAsRead'])
        ->name('messages.read');
    
    Route::post('/messages/read-all', [MessageController::class, 'markAllAsRead'])
        ->name('messages.read.all');
}); 
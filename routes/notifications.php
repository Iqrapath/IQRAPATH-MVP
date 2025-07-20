<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\UserNotificationController;
use App\Http\Controllers\API\MessageController;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Notification Routes
|--------------------------------------------------------------------------
|
| Here is where you can register notification-related routes for your application.
| This file contains both web routes (for UI pages) and API routes (for data).
|
*/

// WEB ROUTES - UI pages for notifications
// Main notification page that redirects to role-specific pages
Route::middleware(['auth', 'verified'])->group(function () {
    // This is the main notification route that redirects based on role
    Route::get('/notifications', function () {
        $user = Auth::user();
        $role = $user->role;
        
        if ($role === 'super-admin') {
            return redirect()->route('admin.notifications');
        } elseif ($role === 'teacher') {
            return redirect()->route('teacher.notifications');
        } elseif ($role === 'student') {
            return redirect()->route('student.notifications');
        } elseif ($role === 'guardian') {
            return redirect()->route('guardian.notifications');
        } else {
            // Redirect unassigned users to their notifications page
            return redirect()->route('unassigned.notifications');
        }
    })->name('notifications');
});

// API ROUTES - Data endpoints for notifications
Route::prefix('api')->middleware('auth')->group(function () {
    // Test endpoint for notification system - no auth required
    Route::get('/test-notification', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'Notification system is working',
            'timestamp' => now()->toDateTimeString()
        ]);
    });
    
    // User notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    
    // User-specific notification endpoints
    Route::get('/user/notifications', [UserNotificationController::class, 'index']);
    Route::get('/user/notifications/unread', [UserNotificationController::class, 'unread']);
    Route::get('/user/notifications/count', [UserNotificationController::class, 'count']);
    
    // Message endpoints
    Route::apiResource('messages', MessageController::class);
    Route::get('/messages/user/{user}', [MessageController::class, 'withUser']);
    Route::post('/messages/{message}/read', [MessageController::class, 'markAsRead']);
    Route::post('/messages/read-all', [MessageController::class, 'markAllAsRead']);
}); 
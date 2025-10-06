<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Admin\UrgentActionsController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\UserNotificationController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\Admin\UserController as AdminUserController;
use App\Http\Controllers\API\UserController;
use App\Models\User;
use App\Http\Controllers\API\UserListController;
use App\Http\Controllers\Teacher\AvailabilityController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Admin users endpoint for notifications
Route::middleware('auth')->get('/admin/users', function (Request $request) {
    // Check if user is admin or super-admin
    if (!in_array($request->user()->role, ['admin', 'super-admin'])) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    
    return User::select('id', 'name')
        ->orderBy('name')
        ->get();
});

// Test endpoint for notification system
Route::get('/test-notification', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Notification system is working',
        'timestamp' => now()->toDateTimeString()
    ]);
});

// User list endpoint for notifications
Route::middleware('auth')->get('/users/list', [UserListController::class, 'index']);

Route::middleware('auth')->get('/user', function (Request $request) {
    return $request->user();
});

// Simple endpoint to get the current user's ID for WebSocket connections
Route::middleware('auth')->get('/user-id', function (Request $request) {
    return response()->json([
        'id' => $request->user()->id,
        'success' => true
    ]);
});

// Notification routes
Route::middleware('auth')->group(function () {
    // User notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
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
    
    // User list for notifications
    Route::get('/users/list', [UserListController::class, 'index']);
});

// Admin API routes
Route::middleware(['auth'])
    ->prefix('admin')
    ->group(function () {
        // Urgent Actions endpoints
        Route::get('/urgent-actions', [App\Http\Controllers\Api\UrgentActionController::class, 'index'])
            ->name('api.admin.urgent-actions');
        Route::post('/urgent-actions/refresh', [App\Http\Controllers\Api\UrgentActionController::class, 'refresh'])
            ->name('api.admin.urgent-actions.refresh');
        Route::get('/urgent-actions/stats', [App\Http\Controllers\Api\UrgentActionController::class, 'stats'])
            ->name('api.admin.urgent-actions.stats');
        
        // Scheduled Notifications endpoints
        Route::get('/scheduled-notifications', [App\Http\Controllers\Api\ScheduledNotificationController::class, 'index'])
            ->name('api.admin.scheduled-notifications.index');
        Route::get('/scheduled-notifications/search', [App\Http\Controllers\Api\ScheduledNotificationController::class, 'search'])
            ->name('api.admin.scheduled-notifications.search');
        Route::post('/scheduled-notifications', [App\Http\Controllers\Api\ScheduledNotificationController::class, 'store'])
            ->name('api.admin.scheduled-notifications.store');
        Route::get('/scheduled-notifications/{id}', [App\Http\Controllers\Api\ScheduledNotificationController::class, 'show'])
            ->name('api.admin.scheduled-notifications.show');
        Route::put('/scheduled-notifications/{id}', [App\Http\Controllers\Api\ScheduledNotificationController::class, 'update'])
            ->name('api.admin.scheduled-notifications.update');
        Route::put('/scheduled-notifications/{id}/cancel', [App\Http\Controllers\Api\ScheduledNotificationController::class, 'cancel'])
            ->name('api.admin.scheduled-notifications.cancel');
        Route::delete('/scheduled-notifications/{id}', [App\Http\Controllers\Api\ScheduledNotificationController::class, 'destroy'])
            ->name('api.admin.scheduled-notifications.destroy');
        
        // Add users endpoint for notifications
        Route::get('/users', [UserController::class, 'index'])
            ->name('api.admin.users');
        
        // Admin notifications endpoint
        Route::get('/notifications', function (Request $request) {
            $user = $request->user();
            $notifications = \App\Models\Notification::where('notifiable_id', $user->id)
                ->where('notifiable_type', \App\Models\User::class)
                ->orderBy('created_at', 'desc')
                ->get();
            
            $unreadCount = $notifications->whereNull('read_at')->count();
            
            return response()->json([
                'notifications' => $notifications,
                'unreadCount' => $unreadCount
            ]);
        });
        
        // Teacher status API routes
        Route::get('/teachers/{teacher}/status', [App\Http\Controllers\Api\TeacherStatusController::class, 'show']);
        Route::post('/teachers/{teacher}/status/refresh', [App\Http\Controllers\Api\TeacherStatusController::class, 'refresh']);
        Route::post('/teachers/status/bulk', [App\Http\Controllers\Api\TeacherStatusController::class, 'bulk']);
        
        
    });

    // Teacher availability routes
    Route::middleware(['web', 'auth', 'role:teacher'])->group(function () {
        Route::get('/teacher/availability/{teacherId}', [AvailabilityController::class, 'getAvailability']);
        Route::post('/teacher/availability/{teacherId}', [AvailabilityController::class, 'updateAvailability']);
    });

// Exchange rate API endpoint
Route::get('/exchange-rate/{from}/{to}', function (string $from, string $to) {
    $currencyService = app(\App\Services\CurrencyService::class);
    $rate = $currencyService->getExchangeRate($from, $to);
    
    return response()->json([
        'from' => $from,
        'to' => $to,
        'rate' => $rate,
        'timestamp' => now()->toISOString()
    ]);
}); 
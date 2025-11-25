<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Admin\UrgentActionsController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\UserNotificationController;

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
Route::middleware('auth:sanctum')->get('/admin/users', function (Request $request) {
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
Route::middleware('auth:sanctum')->get('/users/list', [UserListController::class, 'index']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Simple endpoint to get the current user's ID for WebSocket connections
Route::middleware('auth:sanctum')->get('/user-id', function (Request $request) {
    return response()->json([
        'id' => $request->user()->id,
        'success' => true
    ]);
});

// Notification routes
Route::middleware('auth:sanctum')->group(function () {
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
    

    // User list for notifications
    Route::get('/users/list', [UserListController::class, 'index']);
    
    // Messaging API routes
    Route::prefix('conversations')->group(function () {
        Route::get('/', [App\Http\Controllers\API\ConversationController::class, 'index']);
        Route::post('/', [App\Http\Controllers\API\ConversationController::class, 'store']);
        Route::get('/{conversationId}', [App\Http\Controllers\API\ConversationController::class, 'show']);
        Route::post('/{conversationId}/archive', [App\Http\Controllers\API\ConversationController::class, 'archive']);
        Route::post('/{conversationId}/unarchive', [App\Http\Controllers\API\ConversationController::class, 'unarchive']);
        Route::post('/{conversationId}/mute', [App\Http\Controllers\API\ConversationController::class, 'mute']);
        Route::post('/{conversationId}/unmute', [App\Http\Controllers\API\ConversationController::class, 'unmute']);
        Route::post('/{conversationId}/typing', [App\Http\Controllers\API\ConversationController::class, 'typing']);
        Route::post('/{conversationId}/mark-read', [App\Http\Controllers\API\ConversationController::class, 'markAsRead']);
    });
    
    Route::prefix('messages')->group(function () {
        Route::post('/', [App\Http\Controllers\API\MessageController::class, 'store']);
        Route::put('/{messageId}', [App\Http\Controllers\API\MessageController::class, 'update']);
        Route::delete('/{messageId}', [App\Http\Controllers\API\MessageController::class, 'destroy']);
        Route::post('/{messageId}/read', [App\Http\Controllers\API\MessageController::class, 'markAsRead']);
        Route::post('/read-all', [App\Http\Controllers\API\MessageController::class, 'markAllAsRead']);
        
        // Attachment routes with rate limiting and quota check
        // Limit: 20 uploads per minute per user
        Route::post('/{messageId}/attachments', [App\Http\Controllers\API\MessageAttachmentController::class, 'upload'])
            ->middleware(['throttle:20,1', 'attachment.quota']);
    });
    
    // Attachment routes
    Route::prefix('attachments')->group(function () {
        Route::get('/{attachmentId}/download', [App\Http\Controllers\API\MessageAttachmentController::class, 'download']);
        Route::get('/{attachmentId}/url', [App\Http\Controllers\API\MessageAttachmentController::class, 'getSignedUrl']);
        Route::delete('/{attachmentId}', [App\Http\Controllers\API\MessageAttachmentController::class, 'destroy']);
    });
    
    Route::prefix('search')->group(function () {
        Route::get('/messages', [App\Http\Controllers\API\SearchController::class, 'search']);
        Route::get('/participants', [App\Http\Controllers\API\SearchController::class, 'searchByParticipant']);
        Route::get('/date-range', [App\Http\Controllers\API\SearchController::class, 'searchByDateRange']);
    });
});

// Admin API routes
Route::middleware('auth:sanctum')
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
    Route::middleware(['auth:sanctum', 'role:teacher'])->group(function () {
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

// PayStack API routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/banks', function () {
        $withdrawalService = app(\App\Services\WithdrawalService::class);
        return $withdrawalService->getSupportedBanks();
    });
    
    Route::post('/verify-bank-account', function (Request $request) {
        $request->validate([
            'account_number' => 'required|string',
            'bank_code' => 'required|string'
        ]);
        
        $withdrawalService = app(\App\Services\WithdrawalService::class);
        return $withdrawalService->verifyBankAccount($request->account_number, $request->bank_code);
    });
}); 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\UrgentActionsController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\UserNotificationController;
use App\Http\Controllers\API\MessageController;

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

// Test endpoint for notification system
Route::get('/test-notification', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Notification system is working',
        'timestamp' => now()->toDateTimeString()
    ]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
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
    
    // Message endpoints
    Route::apiResource('messages', MessageController::class);
    Route::get('/messages/user/{user}', [MessageController::class, 'withUser']);
    Route::post('/messages/{message}/read', [MessageController::class, 'markAsRead']);
    Route::post('/messages/read-all', [MessageController::class, 'markAllAsRead']);
});

// Admin API routes
Route::middleware(['auth:sanctum', 'role:admin,super-admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/urgent-actions', [UrgentActionsController::class, 'index'])
            ->name('api.admin.urgent-actions');
    }); 
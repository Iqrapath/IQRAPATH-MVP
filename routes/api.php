use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\UrgentActionsController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TeacherSidebarController;
use App\Http\Controllers\Teacher\SidebarController;

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

// Add routes that work with regular auth (not sanctum)
Route::middleware('auth')->group(function() {
    Route::get('/user-notifications/count', [NotificationController::class, 'getUserNotificationCount']);
    Route::get('/user-notifications', [NotificationController::class, 'getUserNotifications'])
        ->name('api.user-notifications');
    Route::post('/user-notifications/{id}/read', [NotificationController::class, 'markRecipientAsRead'])
        ->name('api.user-notifications.read');
});

// Notification routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('api.notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('api.notifications.mark-read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('api.notifications.mark-all-read');
        
    // Test routes for real-time notifications
    Route::post('/notifications/test', [NotificationController::class, 'testNotification'])
        ->name('api.notifications.test');
    Route::post('/session-requests/test', [NotificationController::class, 'testSessionRequest'])
        ->name('api.session-requests.test');
    Route::post('/messages/test', [NotificationController::class, 'testMessage'])
        ->name('api.messages.test');
    
    // User notifications for dropdown - MOVED to auth middleware group above
    // Route::get('/user-notifications', [NotificationController::class, 'getUserNotifications'])
    //    ->name('api.user-notifications');
    // Route::get('/user-notifications/count', [NotificationController::class, 'getUserNotificationCount']);
    // Route::post('/user-notifications/{id}/read', [NotificationController::class, 'markRecipientAsRead'])
    //    ->name('api.user-notifications.mark-read');
});

// Teacher sidebar routes
Route::middleware(['auth:sanctum', 'role:teacher'])->group(function () {
    Route::get('/teacher/sidebar-data', [TeacherSidebarController::class, 'getSidebarData'])
        ->name('api.teacher.sidebar-data');
    Route::post('/teacher/session-requests/{id}/accept', [TeacherSidebarController::class, 'acceptSessionRequest'])
        ->name('api.teacher.accept-session-request');
    Route::post('/teacher/session-requests/{id}/decline', [TeacherSidebarController::class, 'declineSessionRequest'])
        ->name('api.teacher.decline-session-request');
});

// Mock API endpoint for testing
Route::middleware('auth:sanctum')->get('/teacher/sidebar-data-mock', function (Request $request) {
    return response()->json([
        'session_requests' => [],
        'messages' => [],
        'online_students' => [],
        'pending_request_count' => 0,
        'unread_message_count' => 0,
    ]);
})->name('api.teacher.sidebar-data-mock');

// Teacher sidebar data endpoints
Route::middleware(['auth'])->group(function () {
    // Real endpoint for sidebar data
    Route::get('/api/teacher/sidebar-data', [SidebarController::class, 'getData']);
    
    // Mock endpoint for testing
    Route::get('/api/teacher/sidebar-data-mock', [SidebarController::class, 'getMockData']);
    
    // Session request actions
    Route::post('/api/teacher/session-requests/{id}/accept', [SidebarController::class, 'acceptSessionRequest']);
    Route::post('/api/teacher/session-requests/{id}/decline', [SidebarController::class, 'declineSessionRequest']);
});

// Admin API routes
Route::middleware(['auth:sanctum', 'role:admin,super-admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/urgent-actions', [UrgentActionsController::class, 'index'])
            ->name('api.admin.urgent-actions');
        Route::get('/notifications', [NotificationController::class, 'getAdminNotifications'])
            ->name('api.admin.notifications');
    });

// Add a route for admin notifications that doesn't require sanctum
Route::middleware(['auth', 'role:admin,super-admin'])
    ->get('/admin/notifications', [NotificationController::class, 'getAdminNotifications'])
    ->name('api.admin.notifications.web'); 
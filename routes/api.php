use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\UrgentActionsController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TeacherSidebarController;

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

// Notification routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('api.notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('api.notifications.mark-read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('api.notifications.mark-all-read');
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

// Admin API routes
Route::middleware(['auth:sanctum', 'role:admin,super-admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/urgent-actions', [UrgentActionsController::class, 'index'])
            ->name('api.admin.urgent-actions');
    }); 
<?php

use App\Http\Controllers\Admin\ContentPagesController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\NotificationsTestController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Teacher\SidebarController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Public content pages
Route::get('/page/{slug}', [ContentPagesController::class, 'show'])->name('pages.show');

// Public FAQs
Route::get('/faqs', function () {
    return Inertia::render('Faqs');
})->name('faqs');

// Teacher document routes
Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teacher')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Teacher\DashboardController::class, 'index'])->name('teacher.dashboard');
    
    // Teacher notifications
    Route::get('/notifications', [App\Http\Controllers\Teacher\NotificationController::class, 'index'])->name('teacher.notifications');
    Route::get('/notification/{id}', [App\Http\Controllers\Teacher\NotificationController::class, 'show'])->name('teacher.notification.show');
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Teacher\NotificationController::class, 'markAsRead'])->name('teacher.notification.read');
    Route::post('/notifications/read-all', [App\Http\Controllers\Teacher\NotificationController::class, 'markAllAsRead'])->name('teacher.notifications.read-all');
    Route::delete('/notifications/{id}', [App\Http\Controllers\Teacher\NotificationController::class, 'destroy'])->name('teacher.notification.destroy');
    
    // Subject routes nested under teacher
    Route::resource('subjects', SubjectController::class);
    
    // Document management routes
    Route::resource('documents', DocumentController::class);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
});

// Student routes
Route::middleware(['auth', 'verified', 'role:student'])->prefix('student')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Student\DashboardController::class, 'index'])->name('student.dashboard');
    
    // Student notifications
    Route::get('/notifications', [App\Http\Controllers\Student\NotificationController::class, 'index'])->name('student.notifications');
    Route::get('/notification/{id}', [App\Http\Controllers\Student\NotificationController::class, 'show'])->name('student.notification.show');
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Student\NotificationController::class, 'markAsRead'])->name('student.notification.read');
    Route::post('/notifications/read-all', [App\Http\Controllers\Student\NotificationController::class, 'markAllAsRead'])->name('student.notifications.read-all');
    Route::delete('/notifications/{id}', [App\Http\Controllers\Student\NotificationController::class, 'destroy'])->name('student.notification.destroy');
});

// Guardian routes
Route::middleware(['auth', 'verified', 'role:guardian'])->prefix('guardian')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Guardian\DashboardController::class, 'index'])->name('guardian.dashboard');
    
    // Guardian notifications
    Route::get('/notifications', [App\Http\Controllers\Guardian\NotificationController::class, 'index'])->name('guardian.notifications');
    Route::get('/notification/{id}', [App\Http\Controllers\Guardian\NotificationController::class, 'show'])->name('guardian.notification.show');
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Guardian\NotificationController::class, 'markAsRead'])->name('guardian.notification.read');
    Route::post('/notifications/read-all', [App\Http\Controllers\Guardian\NotificationController::class, 'markAllAsRead'])->name('guardian.notifications.read-all');
    Route::delete('/notifications/{id}', [App\Http\Controllers\Guardian\NotificationController::class, 'destroy'])->name('guardian.notification.destroy');
});

// Include other route files
require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
require __DIR__.'/dashboard.php';
require __DIR__.'/admin.php';
require __DIR__.'/financial.php';
require __DIR__.'/subscriptions.php';
require __DIR__.'/notifications.php';
require __DIR__.'/sessions.php';
require __DIR__.'/payments.php';
require __DIR__.'/feedback.php';

// Debug routes - only available in local environment
if (app()->environment(['local', 'development'])) {
    Route::middleware(['auth'])->group(function () {
        Route::get('/test-notification', function () {
            $request = request();
            $response = app()->make(\App\Http\Controllers\Api\NotificationController::class)->createTestNotification($request);
            return redirect()->back()->with('success', 'Test notification created');
        })->name('test-notification');
        
        // Debug route for broadcasting - simplified
        Route::get('/debug-broadcasting', function () {
            return response()->json([
                'success' => true,
                'message' => 'Broadcasting system is configured',
                'user_id' => Auth::id(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
        })->name('debug-broadcasting');
    });
}

// Add the test route at the end of the file
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications/test', function () {
        return inertia('notifications/test');
    })->name('notifications.test');
    
    // Test route to check CSRF token
    Route::get('/test-csrf', function () {
        return response()->json([
            'csrf_token' => csrf_token(),
            'session_status' => session()->isStarted() ? 'Started' : 'Not started',
            'auth_user' => Auth::check() ? Auth::user()->id : 'Not authenticated'
        ]);
    });
    
    // Broadcasting auth routes with minimal middleware
    Route::match(['get', 'post'], '/broadcasting/auth', [\App\Http\Controllers\BroadcastController::class, 'authenticate'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        
    // API-based broadcasting auth route
    Route::match(['get', 'post'], '/api/broadcasting/auth', [\App\Http\Controllers\BroadcastController::class, 'authenticate'])
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
        ->middleware('auth');
});

// Add admin notifications route for the admin panel
Route::middleware(['auth', 'role:admin,super-admin'])->group(function () {
    Route::get('/api/admin/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'getAdminNotifications'])
        ->name('admin.notifications.api');
    
    // Admin notification routes
    Route::get('/admin/notification', [App\Http\Controllers\Admin\NotificationViewController::class, 'index'])->name('admin.notification');
    Route::get('/admin/notification/{id}', [App\Http\Controllers\Admin\NotificationViewController::class, 'show'])->name('admin.notification.show');
    Route::post('/admin/notifications/{id}/read', [App\Http\Controllers\Admin\NotificationViewController::class, 'markAsRead'])->name('admin.notification.read');
    Route::post('/admin/notifications/read-all', [App\Http\Controllers\Admin\NotificationViewController::class, 'markAllAsRead'])->name('admin.notifications.read-all');
    Route::delete('/admin/notifications/{id}', [App\Http\Controllers\Admin\NotificationViewController::class, 'destroy'])->name('admin.notification.destroy');
});

// Teacher API routes
Route::middleware(['auth'])->group(function () {
    Route::get('/api/teacher/sidebar-data', [SidebarController::class, 'getData']);
    Route::post('/api/teacher/session-requests/{id}/accept', [SidebarController::class, 'acceptSessionRequest']);
    Route::post('/api/teacher/session-requests/{id}/decline', [SidebarController::class, 'declineSessionRequest']);
});

// Add a universal notification route that will redirect to the appropriate role-specific notification page
Route::middleware(['auth'])->get('/notification/{id}', [\App\Http\Controllers\NotificationRedirectController::class, 'redirect']);

<?php

use App\Http\Controllers\Admin\ContentPagesController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


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
    // Subject routes nested under teacher
    Route::resource('subjects', SubjectController::class);
    
    // Document management routes
    Route::resource('documents', DocumentController::class);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
});

// Notifications page
Route::middleware(['auth', 'verified'])->get('/notifications', function () {
    return inertia('notifications/index');
})->name('notifications');

// API notification routes
Route::prefix('api')->group(function () {
    // Test endpoint for notification system - no auth required
    Route::get('/test-notification', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'Notification system is working',
            'timestamp' => now()->toDateTimeString()
        ]);
    });

    // Auth required routes
    Route::middleware('auth')->group(function () {
        // Get current user
        Route::get('/user', function () {
            return request()->user();
        });
        
        // Get user list for notifications
        Route::get('/users/list', [App\Http\Controllers\API\UserListController::class, 'index']);
        
        // User notifications
        Route::get('/notifications', [App\Http\Controllers\API\NotificationController::class, 'index']);
        Route::get('/notifications/{notification}', [App\Http\Controllers\API\NotificationController::class, 'show']);
        Route::post('/notifications', [App\Http\Controllers\API\NotificationController::class, 'store']);
        Route::post('/notifications/{notification}/read', [App\Http\Controllers\API\NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [App\Http\Controllers\API\NotificationController::class, 'markAllAsRead']);
        Route::delete('/notifications/{notification}', [App\Http\Controllers\API\NotificationController::class, 'destroy']);
        
        // User-specific notification endpoints
        Route::get('/user/notifications', [App\Http\Controllers\API\UserNotificationController::class, 'index']);
        Route::get('/user/notifications/unread', [App\Http\Controllers\API\UserNotificationController::class, 'unread']);
        Route::get('/user/notifications/count', [App\Http\Controllers\API\UserNotificationController::class, 'count']);
        
        // Message endpoints
        Route::apiResource('messages', App\Http\Controllers\API\MessageController::class);
        Route::get('/messages/user/{user}', [App\Http\Controllers\API\MessageController::class, 'withUser']);
        Route::post('/messages/{message}/read', [App\Http\Controllers\API\MessageController::class, 'markAsRead']);
        Route::post('/messages/read-all', [App\Http\Controllers\API\MessageController::class, 'markAllAsRead']);
    });
});

// Include other route files
require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
require __DIR__.'/dashboard.php';
require __DIR__.'/admin.php';
require __DIR__.'/financial.php';
require __DIR__.'/subscriptions.php';
require __DIR__.'/sessions.php';
require __DIR__.'/payments.php';
require __DIR__.'/feedback.php';
require __DIR__.'/notifications.php';

// Debug routes - only available in local environment
if (app()->environment(['local', 'development'])) {
    Route::middleware(['auth'])->group(function () {
        
        
    });
}

// Add the test route at the end of the file
Route::middleware(['auth'])->group(function () {
    
    // Test route to check CSRF token
    Route::get('/test-csrf', function () {
        return response()->json([
            'csrf_token' => csrf_token(),
            'session_status' => session()->isStarted() ? 'Started' : 'Not started',
            'auth_user' => Auth::check() ? Auth::user()->id : 'Not authenticated'
        ]);
    });
});
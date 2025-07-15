<?php

use App\Http\Controllers\Admin\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notification Routes
|--------------------------------------------------------------------------
|
| Here is where you can register notification related routes for your application.
| These routes handle user notifications, admin notification management, templates, and triggers.
|
*/

// Admin notification routes
Route::middleware(['auth', 'verified', 'role:super-admin'])->prefix('admin/notification')->name('admin.notification.')->group(function () {
    // Define fixed routes first
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/create', [NotificationController::class, 'create'])->name('create');
    Route::post('/', [NotificationController::class, 'store'])->name('store');
    
    // User search for notifications - must be before any wildcard routes
    Route::get('/search-users', [NotificationController::class, 'searchUsers'])->name('search-users');
    
    // Development routes - only available in local environment
    if (app()->environment('local')) {
        Route::get('/create-test-user', [NotificationController::class, 'createTestUser'])->name('create-test-user');
    }

    // Templates
    Route::get('/templates', [NotificationController::class, 'templates'])->name('templates');
    Route::get('/templates/create', [NotificationController::class, 'createTemplate'])->name('templates.create');
    Route::post('/templates', [NotificationController::class, 'storeTemplate'])->name('templates.store');
    Route::get('/templates/{template}', [NotificationController::class, 'showTemplate'])->name('templates.show');
    Route::get('/templates/{template}/edit', [NotificationController::class, 'editTemplate'])->name('templates.edit');
    Route::put('/templates/{template}', [NotificationController::class, 'updateTemplate'])->name('templates.update');
    Route::delete('/templates/{template}', [NotificationController::class, 'destroyTemplate'])->name('templates.destroy');
    
    // Triggers
    Route::get('/triggers', [NotificationController::class, 'triggers'])->name('triggers');
    Route::get('/triggers/create', [NotificationController::class, 'createTrigger'])->name('triggers.create');
    Route::post('/triggers', [NotificationController::class, 'storeTrigger'])->name('triggers.store');
    Route::get('/triggers/{trigger}', [NotificationController::class, 'showTrigger'])->name('triggers.show');
    Route::get('/triggers/{trigger}/edit', [NotificationController::class, 'editTrigger'])->name('triggers.edit');
    Route::put('/triggers/{trigger}', [NotificationController::class, 'updateTrigger'])->name('triggers.update');
    Route::delete('/triggers/{trigger}', [NotificationController::class, 'destroyTrigger'])->name('triggers.destroy');
    
    // Notification wildcard routes - these must come last
    Route::get('/{notification}/edit', [NotificationController::class, 'edit'])->name('edit');
    Route::put('/{notification}', [NotificationController::class, 'update'])->name('update');
    Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    Route::post('/{notification}/send', [NotificationController::class, 'send'])->name('send');
    Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
});

// Add a separate route for notification history
Route::middleware(['auth', 'verified', 'role:super-admin'])->get('/admin/notification-history', [NotificationController::class, 'history'])->name('admin.notification.history');

// User notification API routes
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index'])
        ->name('api.notifications');
    
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead'])
        ->name('api.notifications.read');
    
    Route::post('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead'])
        ->name('api.notifications.read-all');
        
    // Get user notifications for dropdown
    Route::get('/user-notifications', [NotificationController::class, 'getUserNotifications'])
        ->name('api.user.notifications');
        
    // Create a test notification (only in local/development environment)
    if (app()->environment(['local', 'development'])) {
        Route::post('/create-test-notification', [App\Http\Controllers\Api\NotificationController::class, 'createTestNotification'])
            ->name('api.create-test-notification');
    }
});

// User notification routes
Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [App\Http\Controllers\UserNotificationController::class, 'index'])->name('index');
    Route::get('/{id}', [App\Http\Controllers\UserNotificationController::class, 'show'])->name('show');
    Route::post('/{id}/read', [App\Http\Controllers\UserNotificationController::class, 'markAsRead'])->name('read');
    Route::post('/read-all', [App\Http\Controllers\UserNotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::delete('/{id}', [App\Http\Controllers\UserNotificationController::class, 'destroy'])->name('destroy');
}); 
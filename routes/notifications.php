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
Route::middleware(['auth', 'verified', 'role:super-admin'])->prefix('admin/notifications')->name('admin.notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/create', [NotificationController::class, 'create'])->name('create');
    Route::post('/', [NotificationController::class, 'store'])->name('store');
    Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
    Route::get('/{notification}/edit', [NotificationController::class, 'edit'])->name('edit');
    Route::put('/{notification}', [NotificationController::class, 'update'])->name('update');
    Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    Route::post('/{notification}/send', [NotificationController::class, 'send'])->name('send');
    
    // Templates
    Route::get('/templates', [NotificationController::class, 'templates'])->name('templates');
    Route::get('/templates/create', [NotificationController::class, 'createTemplate'])->name('templates.create');
    Route::post('/templates', [NotificationController::class, 'storeTemplate'])->name('templates.store');
    
    // Triggers
    Route::get('/triggers', [NotificationController::class, 'triggers'])->name('triggers');
    Route::get('/triggers/create', [NotificationController::class, 'createTrigger'])->name('triggers.create');
    Route::post('/triggers', [NotificationController::class, 'storeTrigger'])->name('triggers.store');
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
});

// User notification routes
Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [App\Http\Controllers\UserNotificationController::class, 'index'])->name('index');
    Route::get('/{id}', [App\Http\Controllers\UserNotificationController::class, 'show'])->name('show');
    Route::post('/{id}/read', [App\Http\Controllers\UserNotificationController::class, 'markAsRead'])->name('read');
    Route::post('/read-all', [App\Http\Controllers\UserNotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::delete('/{id}', [App\Http\Controllers\UserNotificationController::class, 'destroy'])->name('destroy');
}); 
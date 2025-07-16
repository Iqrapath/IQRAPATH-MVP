<?php

use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\NotificationTriggerController;
use App\Http\Controllers\Guardian\NotificationController as GuardianNotificationController;
use App\Http\Controllers\NotificationRedirectController;
use App\Http\Controllers\Student\NotificationController as StudentNotificationController;
use App\Http\Controllers\Teacher\NotificationController as TeacherNotificationController;
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
    Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
    Route::get('/create', [AdminNotificationController::class, 'create'])->name('create');
    Route::post('/', [AdminNotificationController::class, 'store'])->name('store');
    
    // User search for notifications - must be before any wildcard routes
    Route::get('/search-users', [AdminNotificationController::class, 'searchUsers'])->name('search-users');
    
    // Development routes - only available in local environment
    if (app()->environment('local')) {
        Route::get('/create-test-user', [AdminNotificationController::class, 'createTestUser'])->name('create-test-user');
    }

    // Templates
    Route::get('/templates', [NotificationTemplateController::class, 'index'])->name('templates');
    Route::get('/templates/create', [NotificationTemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [NotificationTemplateController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}', [NotificationTemplateController::class, 'show'])->name('templates.show');
    Route::get('/templates/{template}/edit', [NotificationTemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{template}', [NotificationTemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{template}', [NotificationTemplateController::class, 'destroy'])->name('templates.destroy');
    
    // Triggers
    Route::get('/triggers', [NotificationTriggerController::class, 'index'])->name('triggers');
    Route::get('/triggers/create', [NotificationTriggerController::class, 'create'])->name('triggers.create');
    Route::post('/triggers', [NotificationTriggerController::class, 'store'])->name('triggers.store');
    Route::get('/triggers/{trigger}', [NotificationTriggerController::class, 'show'])->name('triggers.show');
    Route::get('/triggers/{trigger}/edit', [NotificationTriggerController::class, 'edit'])->name('triggers.edit');
    Route::put('/triggers/{trigger}', [NotificationTriggerController::class, 'update'])->name('triggers.update');
    Route::delete('/triggers/{trigger}', [NotificationTriggerController::class, 'destroy'])->name('triggers.destroy');
    
    // Notification wildcard routes - these must come last
    Route::get('/{notification}/edit', [AdminNotificationController::class, 'edit'])->name('edit');
    Route::put('/{notification}', [AdminNotificationController::class, 'update'])->name('update');
    Route::delete('/{notification}', [AdminNotificationController::class, 'destroy'])->name('destroy');
    Route::post('/{notification}/send', [AdminNotificationController::class, 'send'])->name('send');
    Route::get('/{notification}', [AdminNotificationController::class, 'show'])->name('show');
});

// Add a separate route for notification history
Route::middleware(['auth', 'verified', 'role:super-admin'])->get('/admin/notification-history', [AdminNotificationController::class, 'history'])->name('admin.notification.history');

// Admin user notifications
Route::middleware(['auth', 'role:admin,super-admin'])->group(function () {
    Route::get('/api/admin/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'getAdminNotifications'])
        ->name('admin.notifications.api');
    
    // Admin notification routes
    Route::get('/admin/notification', [AdminNotificationController::class, 'viewUserNotifications'])->name('admin.notification');
    Route::get('/admin/notification/{id}', [AdminNotificationController::class, 'viewUserNotification'])->name('admin.notification.show');
    Route::post('/admin/notifications/{id}/read', [AdminNotificationController::class, 'markAsRead'])->name('admin.notification.read');
    Route::post('/admin/notifications/read-all', [AdminNotificationController::class, 'markAllAsRead'])->name('admin.notifications.read-all');
    Route::delete('/admin/notifications/{id}', [AdminNotificationController::class, 'destroyUserNotification'])->name('admin.notification.destroy');
});

// Teacher notification routes
Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teacher')->group(function () {
    Route::get('/notifications', [TeacherNotificationController::class, 'index'])->name('teacher.notifications');
    Route::get('/notification/{id}', [TeacherNotificationController::class, 'show'])->name('teacher.notification.show');
    Route::post('/notifications/{id}/read', [TeacherNotificationController::class, 'markAsRead'])->name('teacher.notification.read');
    Route::post('/notifications/read-all', [TeacherNotificationController::class, 'markAllAsRead'])->name('teacher.notifications.read-all');
    Route::delete('/notifications/{id}', [TeacherNotificationController::class, 'destroy'])->name('teacher.notification.destroy');
});

// Student notification routes
Route::middleware(['auth', 'verified', 'role:student'])->prefix('student')->group(function () {
    Route::get('/notifications', [StudentNotificationController::class, 'index'])->name('student.notifications');
    Route::get('/notification/{id}', [StudentNotificationController::class, 'show'])->name('student.notification.show');
    Route::post('/notifications/{id}/read', [StudentNotificationController::class, 'markAsRead'])->name('student.notification.read');
    Route::post('/notifications/read-all', [StudentNotificationController::class, 'markAllAsRead'])->name('student.notifications.read-all');
    Route::delete('/notifications/{id}', [StudentNotificationController::class, 'destroy'])->name('student.notification.destroy');
});

// Guardian notification routes
Route::middleware(['auth', 'verified', 'role:guardian'])->prefix('guardian')->group(function () {
    Route::get('/notifications', [GuardianNotificationController::class, 'index'])->name('guardian.notifications');
    Route::get('/notification/{id}', [GuardianNotificationController::class, 'show'])->name('guardian.notification.show');
    Route::post('/notifications/{id}/read', [GuardianNotificationController::class, 'markAsRead'])->name('guardian.notification.read');
    Route::post('/notifications/read-all', [GuardianNotificationController::class, 'markAllAsRead'])->name('guardian.notifications.read-all');
    Route::delete('/notifications/{id}', [GuardianNotificationController::class, 'destroy'])->name('guardian.notification.destroy');
});

// User notification API routes
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index'])
        ->name('api.notifications');
    
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead'])
        ->name('api.notifications.read');
    
    Route::post('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead'])
        ->name('api.notifications.read-all');
        
    // Get user notifications for dropdown
    Route::get('/user-notifications', [AdminNotificationController::class, 'getUserNotifications'])
        ->name('api.user.notifications');
        
    // Create a test notification (only in local/development environment)
    if (app()->environment(['local', 'development'])) {
        Route::post('/create-test-notification', [App\Http\Controllers\Api\NotificationController::class, 'createTestNotification'])
            ->name('api.create-test-notification');
    }
});

// Add a universal notification route that will redirect to the appropriate role-specific notification page
Route::middleware(['auth'])->get('/notification/{id}', [NotificationRedirectController::class, 'redirect']);

// User notification routes
Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [App\Http\Controllers\UserNotificationController::class, 'index'])->name('index');
    Route::get('/{id}', [App\Http\Controllers\UserNotificationController::class, 'show'])->name('show');
    Route::post('/{id}/read', [App\Http\Controllers\UserNotificationController::class, 'markAsRead'])->name('read');
    Route::post('/read-all', [App\Http\Controllers\UserNotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::delete('/{id}', [App\Http\Controllers\UserNotificationController::class, 'destroy'])->name('destroy');
}); 
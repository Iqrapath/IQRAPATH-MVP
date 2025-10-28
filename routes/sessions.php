<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Session Routes
|--------------------------------------------------------------------------
|
| Here is where you can register teaching session related routes for your application.
| These routes handle session attendance, completion, cancellation, and Zoom integration.
|
*/

// Zoom Webhook (no auth required)
Route::post('/api/zoom/webhook', [App\Http\Controllers\ZoomWebhookController::class, 'handle'])
    ->name('zoom.webhook');

// Google Meet Webhooks (no auth required)
Route::post('/api/google-meet/webhook', [App\Http\Controllers\GoogleMeetWebhookController::class, 'handle'])
    ->name('google-meet.webhook');
Route::post('/api/google-meet/push-notification', [App\Http\Controllers\GoogleMeetWebhookController::class, 'handlePushNotification'])
    ->name('google-meet.push-notification');

// Session Access Control Routes (Time-based access)
Route::middleware(['auth'])->group(function () {
    // Check if user can access a session
    Route::get('/sessions/{sessionId}/check-access', [App\Http\Controllers\SessionAccessController::class, 'checkAccess'])
        ->name('sessions.check-access');
    
    // Get meeting link (with access control)
    Route::get('/sessions/{sessionId}/meeting-link', [App\Http\Controllers\SessionAccessController::class, 'getMeetingLink'])
        ->name('sessions.meeting-link');
    
    // Join session (redirect to meeting link if access granted)
    Route::get('/sessions/{sessionId}/join', [App\Http\Controllers\SessionAccessController::class, 'joinSession'])
        ->name('sessions.join');
    
    // Waiting room (when too early to join)
    Route::get('/sessions/{sessionId}/waiting-room', [App\Http\Controllers\SessionAccessController::class, 'waitingRoom'])
        ->name('sessions.waiting-room');
    
    // Admin monitoring routes
    Route::middleware(['role:admin,super-admin'])->group(function () {
        Route::get('/admin/monitoring/sessions', [App\Http\Controllers\SessionAccessController::class, 'monitoringDashboard'])
            ->name('admin.monitoring.sessions');
        
        Route::get('/admin/monitoring/sessions/active', [App\Http\Controllers\SessionAccessController::class, 'getActiveSessionsForMonitoring'])
            ->name('admin.monitoring.sessions.active');
    });
});

// Session Attendance Routes
Route::middleware(['auth'])->group(function () {
    Route::post('/sessions/{session}/teacher-join', [App\Http\Controllers\SessionAttendanceController::class, 'teacherJoin'])
        ->name('sessions.teacher-join');
    
    Route::post('/sessions/{session}/student-join', [App\Http\Controllers\SessionAttendanceController::class, 'studentJoin'])
        ->name('sessions.student-join');
    
    Route::post('/sessions/{session}/teacher-leave', [App\Http\Controllers\SessionAttendanceController::class, 'teacherLeave'])
        ->name('sessions.teacher-leave');
    
    Route::post('/sessions/{session}/student-leave', [App\Http\Controllers\SessionAttendanceController::class, 'studentLeave'])
        ->name('sessions.student-leave');
    
    Route::post('/sessions/{session}/update-zoom-attendance', [App\Http\Controllers\SessionAttendanceController::class, 'updateZoomAttendance'])
        ->name('sessions.update-zoom-attendance');
    
    Route::post('/sessions/{session}/mark-completed', [App\Http\Controllers\SessionAttendanceController::class, 'markCompleted'])
        ->name('sessions.mark-completed');
    
    Route::post('/sessions/{session}/mark-cancelled', [App\Http\Controllers\SessionAttendanceController::class, 'markCancelled'])
        ->name('sessions.mark-cancelled');
    
    Route::post('/sessions/{session}/mark-no-show', [App\Http\Controllers\SessionAttendanceController::class, 'markNoShow'])
        ->name('sessions.mark-no-show');
}); 
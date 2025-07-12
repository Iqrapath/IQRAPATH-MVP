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
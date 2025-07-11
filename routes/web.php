<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Guardian\DashboardController as GuardianDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\UnassignedController;
use App\Http\Controllers\UserStatusController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentVerificationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Default dashboard route (will be redirected based on role)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('dashboard', [
            'message' => 'Welcome to your dashboard! You will be redirected based on your role.',
        ]);
    })->name('dashboard');
});

// Unassigned user route
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/unassigned', [UnassignedController::class, 'index'])->name('unassigned');
});

// Admin routes
Route::middleware(['auth', 'verified', 'role:super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // User role management routes
    Route::get('/users', [RoleController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit-role', [RoleController::class, 'edit'])->name('users.edit-role');
    Route::patch('/users/{user}/role', [RoleController::class, 'update'])->name('users.update-role');
    
    // Admin access to subjects
    Route::resource('subjects', SubjectController::class);
    
    // Document verification routes
    Route::get('/documents', [DocumentVerificationController::class, 'index'])->name('documents.index');
    Route::get('/documents/{document}', [DocumentVerificationController::class, 'show'])->name('documents.show');
    Route::patch('/documents/{document}/verify', [DocumentVerificationController::class, 'verify'])->name('documents.verify');
    Route::patch('/documents/{document}/reject', [DocumentVerificationController::class, 'reject'])->name('documents.reject');
    Route::post('/documents/batch-verify', [DocumentVerificationController::class, 'batchVerify'])->name('documents.batch-verify');
    Route::get('/documents/{document}/download', [DocumentVerificationController::class, 'download'])->name('documents.download');
});

// Teacher routes
Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
        
    // Subject routes nested under teacher
    Route::resource('subjects', SubjectController::class);
    
    // Document management routes
    Route::resource('documents', DocumentController::class);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
});

// Student routes
Route::middleware(['auth', 'verified', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
});

// Guardian routes
Route::middleware(['auth', 'verified', 'role:guardian'])->prefix('guardian')->name('guardian.')->group(function () {
    Route::get('/dashboard', [GuardianDashboardController::class, 'index'])->name('dashboard');
});

// User status routes
Route::post('user/status', [UserStatusController::class, 'update'])->name('user.status.update');

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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Teacher\SubjectController;
use App\Http\Controllers\Teacher\AvailabilityController;
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\FinancialController;
use App\Http\Controllers\Teacher\ProfileController;
use App\Http\Controllers\Teacher\TeacherReviewController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Teacher Routes
|--------------------------------------------------------------------------
|
| Here is where you can register teacher specific routes for your application.
| These routes handle teacher dashboard, profile management, subjects,
| availabilities, documents, and financial information.
|
*/

Route::middleware(['auth', 'verified', 'role:teacher', 'teacher.verified'])->prefix('teacher')->name('teacher.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Notifications
    Route::get('/notifications', function () {
        return Inertia::render('teacher/notifications/notifications');
    })->name('notifications');
    
    // Subject routes
    Route::resource('subjects', SubjectController::class);
    
    // Document management routes - API-like endpoints
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    
    // Financial routes
    Route::get('/earnings', [FinancialController::class, 'index'])->name('earnings');
    Route::get('/earnings/history', [FinancialController::class, 'history'])->name('earnings.history');
    Route::get('/earnings/payouts', [FinancialController::class, 'payouts'])->name('earnings.payouts');
    Route::post('/earnings/request-payout', [FinancialController::class, 'requestPayout'])->name('earnings.request-payout');
    
    // Teacher profile routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile/basic-info', [ProfileController::class, 'updateBasicInfo'])->name('profile.update-basic-info');
    Route::put('/profile/teacher-info', [ProfileController::class, 'updateProfile'])->name('profile.update-teacher-info');
    Route::put('/profile/subjects', [ProfileController::class, 'updateSubjects'])->name('profile.update-subjects');
    Route::put('/profile/availability', [ProfileController::class, 'updateAvailability'])->name('profile.update-availability');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.update-avatar');
    Route::post('/profile/intro-video', [ProfileController::class, 'uploadIntroVideo'])->name('profile.upload-intro-video');
    Route::get('/profile/intro-video', function () {
        return Inertia::render('teacher/profile/intro-video');
    })->name('profile.intro-video');
    
    // Teacher subjects management
    Route::post('/profile/subjects', [SubjectController::class, 'store'])->name('profile.subjects.store');
    Route::put('/profile/subjects/{subject}', [SubjectController::class, 'update'])->name('profile.subjects.update');
    Route::delete('/profile/subjects/{subject}', [SubjectController::class, 'destroy'])->name('profile.subjects.destroy');
    
    // Teacher availability management
    Route::post('/profile/availabilities', [AvailabilityController::class, 'store'])->name('profile.availabilities.store');
    Route::put('/profile/availabilities/{availability}', [AvailabilityController::class, 'update'])->name('profile.availabilities.update');
    Route::delete('/profile/availabilities/{availability}', [AvailabilityController::class, 'destroy'])->name('profile.availabilities.destroy');
    Route::put('/profile/availability-preferences', [AvailabilityController::class, 'updatePreferences'])->name('profile.availability-preferences.update');
    
    // Teaching sessions
    Route::get('/sessions', function () {
        return Inertia::render('teacher/sessions/index');
    })->name('sessions');
    Route::get('/sessions/upcoming', function () {
        return Inertia::render('teacher/sessions/upcoming');
    })->name('sessions.upcoming');
    Route::get('/sessions/past', function () {
        return Inertia::render('teacher/sessions/past');
    })->name('sessions.past');
    
    // Student requests
    Route::get('/requests', function () {
        return Inertia::render('teacher/requests/index');
    })->name('requests');

    // Teacher reviews (students and guardians can submit)
    Route::post('/reviews', [TeacherReviewController::class, 'store'])->middleware('role:student,guardian')->name('reviews.store');
    Route::get('/{teacher}/reviews', [TeacherReviewController::class, 'index'])->name('reviews.index');
}); 
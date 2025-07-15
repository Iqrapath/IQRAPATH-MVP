<?php

use App\Http\Controllers\Admin\ContentPagesController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SubjectController;
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
    Route::get('/dashboard', [App\Http\Controllers\Teacher\DashboardController::class, 'index'])->name('teacher.dashboard');
    Route::get('/notifications', function () {
        return inertia('teacher/notifications');
    })->name('teacher.notifications');
    // Subject routes nested under teacher
    Route::resource('subjects', SubjectController::class);
    
    // Document management routes
    Route::resource('documents', DocumentController::class);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
});

// Student routes
Route::middleware(['auth', 'verified', 'role:student'])->prefix('student')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Student\DashboardController::class, 'index'])->name('student.dashboard');
    Route::get('/notifications', function () {
        return inertia('student/notifications');
    })->name('student.notifications');
});

// Guardian routes
Route::middleware(['auth', 'verified', 'role:guardian'])->prefix('guardian')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Guardian\DashboardController::class, 'index'])->name('guardian.dashboard');
    Route::get('/notifications', function () {
        return inertia('guardian/notifications');
    })->name('guardian.notifications');
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

// Development routes - only available in local environment
if (app()->environment(['local', 'development'])) {
    Route::middleware(['auth'])->group(function () {
        Route::get('/test-notification', function () {
            $request = request();
            $response = app()->make(\App\Http\Controllers\Api\NotificationController::class)->createTestNotification($request);
            return redirect()->back()->with('success', 'Test notification created');
        })->name('test-notification');
    });
}

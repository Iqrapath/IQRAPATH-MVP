<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\DocumentVerificationController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register admin specific routes for your application.
| These routes handle user role management, document verification, and other admin tasks.
|
*/

// Admin routes
Route::middleware(['auth', 'verified', 'role:super-admin'])->prefix('admin')->name('admin.')->group(function () {
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
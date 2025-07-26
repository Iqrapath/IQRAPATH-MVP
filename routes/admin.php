<?php

use App\Http\Controllers\Admin\AdminRolesController;
use App\Http\Controllers\Admin\ContentPagesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\FeatureControlsController;
use App\Http\Controllers\Admin\FinancialManagementController;
use App\Http\Controllers\Admin\FinancialSettingsController;
use App\Http\Controllers\Admin\GeneralSettingsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SecuritySettingsController;
use App\Http\Controllers\Admin\TeacherManagementController;
use App\Http\Controllers\Admin\TeacherVerificationController;
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
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
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
    
    // Teacher verification routes
    Route::get('/teacher-verifications', [TeacherVerificationController::class, 'index'])->name('teacher-verifications.index');
    Route::get('/teacher-verifications/{verificationRequest}', [TeacherVerificationController::class, 'show'])->name('teacher-verifications.show');
    Route::patch('/teacher-verifications/{verificationRequest}/approve', [TeacherVerificationController::class, 'approve'])->name('teacher-verifications.approve');
    Route::patch('/teacher-verifications/{verificationRequest}/reject', [TeacherVerificationController::class, 'reject'])->name('teacher-verifications.reject');
    Route::post('/teacher-verifications/{verificationRequest}/request-video', [TeacherVerificationController::class, 'requestVideoVerification'])->name('teacher-verifications.request-video');
    Route::patch('/teacher-verifications/{verificationRequest}/complete-video', [TeacherVerificationController::class, 'completeVideoVerification'])->name('teacher-verifications.complete-video');
    
    // Teacher management routes
    Route::resource('teachers', TeacherManagementController::class);
    Route::patch('/teachers/{teacher}/approve', [TeacherManagementController::class, 'approve'])->name('teachers.approve');
    Route::patch('/teachers/{teacher}/reject', [TeacherManagementController::class, 'reject'])->name('teachers.reject');
    Route::get('/documents/{document}/download', [TeacherManagementController::class, 'downloadDocument'])->name('teachers.document.download');
    
    // Settings routes
    Route::prefix('settings')->name('settings.')->group(function () {
        // General settings
        Route::get('/general', [GeneralSettingsController::class, 'index'])->name('general.index');
        Route::post('/general', [GeneralSettingsController::class, 'update'])->name('general.update');
        Route::patch('/general/{key}', [GeneralSettingsController::class, 'updateSingle'])->name('general.update-single');
        
        // Financial settings
        Route::get('/financial', [FinancialSettingsController::class, 'index'])->name('financial.index');
        Route::post('/financial', [FinancialSettingsController::class, 'update'])->name('financial.update');
        Route::patch('/financial/{key}', [FinancialSettingsController::class, 'updateSingle'])->name('financial.update-single');
        
        // Feature controls
        Route::get('/features', [FeatureControlsController::class, 'index'])->name('features.index');
        Route::post('/features', [FeatureControlsController::class, 'update'])->name('features.update');
        Route::patch('/features/{key}/toggle', [FeatureControlsController::class, 'toggle'])->name('features.toggle');
        
        // Security settings
        Route::get('/security', [SecuritySettingsController::class, 'index'])->name('security.index');
        Route::post('/security', [SecuritySettingsController::class, 'update'])->name('security.update');
        Route::patch('/security/{key}', [SecuritySettingsController::class, 'updateSingle'])->name('security.update-single');
        
        // Admin roles
        Route::get('/roles', [AdminRolesController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [AdminRolesController::class, 'create'])->name('roles.create');
        Route::post('/roles', [AdminRolesController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}/edit', [AdminRolesController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [AdminRolesController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [AdminRolesController::class, 'destroy'])->name('roles.destroy');
        Route::post('/roles/assign', [AdminRolesController::class, 'assignRole'])->name('roles.assign');
        
        // Content pages
        Route::get('/content-pages', [ContentPagesController::class, 'index'])->name('content-pages.index');
        Route::get('/content-pages/create', [ContentPagesController::class, 'create'])->name('content-pages.create');
        Route::post('/content-pages', [ContentPagesController::class, 'store'])->name('content-pages.store');
        Route::get('/content-pages/{page}/edit', [ContentPagesController::class, 'edit'])->name('content-pages.edit');
        Route::put('/content-pages/{page}', [ContentPagesController::class, 'update'])->name('content-pages.update');
        Route::delete('/content-pages/{page}', [ContentPagesController::class, 'destroy'])->name('content-pages.destroy');
        Route::patch('/content-pages/{page}/toggle-published', [ContentPagesController::class, 'togglePublished'])->name('content-pages.toggle-published');
        
        // FAQs
        Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');
        Route::get('/faqs/create', [FaqController::class, 'create'])->name('faqs.create');
        Route::post('/faqs', [FaqController::class, 'store'])->name('faqs.store');
        Route::get('/faqs/{faq}/edit', [FaqController::class, 'edit'])->name('faqs.edit');
        Route::put('/faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
        Route::delete('/faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');
        Route::post('/faqs/update-order', [FaqController::class, 'updateOrder'])->name('faqs.update-order');
        Route::patch('/faqs/{faq}/toggle-published', [FaqController::class, 'togglePublished'])->name('faqs.toggle-published');
    });
    
    // Notifications
    Route::get('/notifications', function () {
        return inertia('admin/notifications/notifications');
    })->name('notifications');
}); 
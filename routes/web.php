<?php

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

// Teacher document routes
Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    // Subject routes nested under teacher
    Route::resource('subjects', SubjectController::class);
    
    // Document management routes
    Route::resource('documents', DocumentController::class);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
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

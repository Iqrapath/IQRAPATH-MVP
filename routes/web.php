<?php

use App\Http\Controllers\ContentPageController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\FindTeacherController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
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

Route::get('/', [WelcomeController::class, 'index'])->name('home');

// Broadcasting authentication
Broadcast::routes(['middleware' => ['web', 'auth']]);

// Test routes for modal testing
Route::get('/test/modal', function () {
    return Inertia::render('test/ModalTest');
})->name('test.modal');

Route::get('/test/payment-info', function () {
    return Inertia::render('test/PaymentInfoTest');
})->name('test.payment-info');

Route::get('/find-teacher', [FindTeacherController::class, 'index'])->name('find-teacher');
Route::get('/api/teachers', [FindTeacherController::class, 'getTeachers'])->name('api.teachers');
Route::post('/api/match-teachers', [FindTeacherController::class, 'matchTeachers'])->name('api.match-teachers');

Route::get('/how-it-works', function () {
    return Inertia::render('how-it-works');
})->name('how-it-works');

Route::get('/blog', function () {
    return Inertia::render('blog');
})->name('blog');

Route::get('/blog-post', function () {
    return Inertia::render('blog-post');
})->name('blog-post');

// Content pages
Route::get('/terms', [App\Http\Controllers\ContentPageController::class, 'terms'])->name('content.terms');
Route::get('/privacy', [App\Http\Controllers\ContentPageController::class, 'privacy'])->name('content.privacy');

Route::get('/about', function () {
    return Inertia::render('about');
})->name('about');

// Public content pages
Route::get('/page/{slug}', [ContentPageController::class, 'show'])->name('pages.show');

// Public FAQs
// Route::get('/faqs', function () {
//     return Inertia::render('Faqs');
// })->name('faqs');

// Unassigned user routes
Route::middleware(['auth'])->group(function () {
    Route::get('/unassigned', function () {
        return Inertia::render('unassigned');
    })->name('unassigned');
    
    // Add notifications route for unassigned users
    Route::get('/unassigned/notifications', function () {
        return Inertia::render('unassigned/notifications');
    })->name('unassigned.notifications');
});

// Include other route files
require __DIR__.'/auth.php';
require __DIR__.'/onboarding.php';
require __DIR__.'/settings.php';
require __DIR__.'/dashboard.php';
require __DIR__.'/admin.php';
require __DIR__.'/teacher.php';
require __DIR__.'/financial.php';
require __DIR__.'/subscriptions.php';
require __DIR__.'/sessions.php';
require __DIR__.'/payments.php';
require __DIR__.'/feedback.php';
require __DIR__.'/notifications.php';
// Webhooks are loaded separately in bootstrap/app.php with api middleware
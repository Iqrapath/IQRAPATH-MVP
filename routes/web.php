<?php

use App\Http\Controllers\Admin\ContentPagesController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\FindTeacherController;
use Illuminate\Support\Facades\Auth;
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

Route::get('/find-teacher', [FindTeacherController::class, 'index'])->name('find-teacher');
Route::get('/api/teachers', [FindTeacherController::class, 'getTeachers'])->name('api.teachers');

Route::get('/how-it-works', function () {
    return Inertia::render('how-it-works');
})->name('how-it-works');

Route::get('/blog', function () {
    return Inertia::render('blog');
})->name('blog');

Route::get('/blog-post', function () {
    return Inertia::render('blog-post');
})->name('blog-post');

Route::get('/about', function () {
    return Inertia::render('about');
})->name('about');



// Public content pages
Route::get('/page/{slug}', [ContentPagesController::class, 'show'])->name('pages.show');

// Public FAQs
Route::get('/faqs', function () {
    return Inertia::render('Faqs');
})->name('faqs');

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
<?php

use App\Http\Controllers\UserStatusController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // User status routes
    Route::post('user/status', [UserStatusController::class, 'update'])->name('user.status.update');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

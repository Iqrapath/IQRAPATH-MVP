<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\ResendVerificationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Student/Guardian registration routes
    Route::get('register/student-guardian', [RegisteredUserController::class, 'createStudentGuardian'])
        ->name('register.student-guardian');
    
    Route::post('register/student-guardian', [RegisteredUserController::class, 'storeStudentGuardian']);

    // Teacher registration routes
    Route::get('register/teacher', [RegisteredUserController::class, 'createTeacher'])
        ->name('register.teacher');
    
    Route::post('register/teacher', [RegisteredUserController::class, 'storeTeacher']);

    // Legacy route - redirect to student-guardian by default
    Route::get('register', function () {
        return redirect()->route('register.student-guardian');
    })->name('register');

    // Resend email verification (for guest users from success page)
    Route::post('resend-verification', [ResendVerificationController::class, 'store'])
        ->name('verification.resend');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // OAuth routes
    Route::get('auth/google', [OAuthController::class, 'redirectToGoogle'])
        ->name('auth.google');
    Route::get('auth/google/callback', [OAuthController::class, 'handleGoogleCallback'])
        ->name('auth.google.callback');
    
    Route::get('auth/facebook', [OAuthController::class, 'redirectToFacebook'])
        ->name('auth.facebook');
    Route::get('auth/facebook/callback', [OAuthController::class, 'handleFacebookCallback'])
        ->name('auth.facebook.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

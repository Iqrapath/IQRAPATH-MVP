<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Guardian\DashboardController as GuardianDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\UnassignedController;
use App\Http\Controllers\UserStatusController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register dashboard related routes for your application.
| These routes handle user dashboards based on their roles.
|
*/

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
// Route::middleware(['auth', 'verified', 'role:super-admin'])->prefix('admin')->name('admin.')->group(function () {
//     Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
// });

// Teacher routes
// Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
//     Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
//     Route::get('/notifications', function () {
//         return Inertia::render('teacher/notifications/notifications');
//     })->name('notifications');
// });

// Student routes
Route::middleware(['auth', 'verified', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifications', function () {
        return Inertia::render('student/notifications/notifications');
    })->name('notifications');
    
    // Session routes
    Route::get('/sessions', [App\Http\Controllers\Student\SessionController::class, 'index'])->name('sessions.index');
    
    // Teacher browsing routes
    Route::get('/browse-teachers', [App\Http\Controllers\Student\TeacherController::class, 'index'])->name('browse-teachers');
    Route::get('/teachers/{teacher}', [App\Http\Controllers\Student\TeacherController::class, 'show'])->name('teachers.show');
    Route::get('/teachers/{teacher}/profile-data', [App\Http\Controllers\Student\TeacherController::class, 'profileData'])->name('teachers.profile-data');
    
    // Booking routes
    Route::get('/my-bookings', [App\Http\Controllers\Student\BookingController::class, 'index'])->name('my-bookings');
    Route::get('/my-bookings/{booking}', [App\Http\Controllers\Student\BookingController::class, 'show'])->name('my-bookings.show');
    Route::get('/book-class', [App\Http\Controllers\Student\BookingController::class, 'create'])->name('book-class');
    Route::get('/booking/session-details', [App\Http\Controllers\Student\BookingController::class, 'sessionDetailsGet'])->name('booking.session-details.get');
    Route::post('/booking/session-details', [App\Http\Controllers\Student\BookingController::class, 'sessionDetails'])->name('booking.session-details');
    Route::get('/booking/pricing-payment', [App\Http\Controllers\Student\BookingController::class, 'pricingPaymentGet'])->name('booking.pricing-payment.get');
    Route::post('/booking/pricing-payment', [App\Http\Controllers\Student\BookingController::class, 'pricingPayment'])->name('booking.pricing-payment');
    Route::post('/booking/payment', [App\Http\Controllers\Student\BookingController::class, 'processPayment'])->name('booking.payment');
    
    // Wallet API routes (for modals only)
    Route::post('/wallet/fund', [App\Http\Controllers\Student\WalletController::class, 'processFunding'])->name('wallet.fund.process');
    Route::get('/wallet/balance', [App\Http\Controllers\Student\WalletController::class, 'getBalance'])->name('wallet.balance');
    Route::get('/wallet/funding-config', [App\Http\Controllers\Student\WalletController::class, 'getFundingConfig'])->name('wallet.funding-config');
    
    // Payment Methods routes
    Route::get('/payment-methods', [App\Http\Controllers\Student\WalletController::class, 'getPaymentMethods'])->name('payment-methods.index');
    Route::post('/payment-methods', [App\Http\Controllers\Student\WalletController::class, 'storePaymentMethod'])->name('payment-methods.store');
    Route::patch('/payment-methods/{paymentMethod}', [App\Http\Controllers\Student\WalletController::class, 'updatePaymentMethod'])->name('payment-methods.update');
    Route::delete('/payment-methods/{paymentMethod}', [App\Http\Controllers\Student\WalletController::class, 'deletePaymentMethod'])->name('payment-methods.delete');
    
    // Payment routes
    Route::post('/payment/fund-wallet', [App\Http\Controllers\Student\PaymentController::class, 'fundWallet'])->name('payment.fund-wallet');
    Route::get('/payment/publishable-key', [App\Http\Controllers\Student\PaymentController::class, 'getPublishableKey'])->name('payment.publishable-key');
    Route::get('/payment/paystack-public-key', [App\Http\Controllers\Student\PaymentController::class, 'getPaystackPublicKey'])->name('payment.paystack-public-key');
    Route::post('/payment/verify-paystack', [App\Http\Controllers\Student\PaymentController::class, 'verifyPaystackPayment'])->name('payment.verify-paystack');
});

// Guardian routes
Route::middleware(['auth', 'verified', 'role:guardian'])->prefix('guardian')->name('guardian.')->group(function () {
    Route::get('/dashboard', [GuardianDashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifications', function () {
        return Inertia::render('guardian/notifications/notifications');
    })->name('notifications');
    Route::get('/children', [GuardianDashboardController::class, 'childrenIndex'])->name('children.index');
    Route::get('/children/create', [GuardianDashboardController::class, 'createChild'])->name('children.create');
    Route::post('/children', [GuardianDashboardController::class, 'storeChild'])->name('children.store');
    Route::get('/children/{child}/progress', [GuardianDashboardController::class, 'childProgress'])->name('children.progress');
});

// User status routes
Route::post('user/status', [UserStatusController::class, 'update'])->name('user.status.update'); 
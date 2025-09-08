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
    Route::post('/my-bookings/{booking}/cancel', [App\Http\Controllers\Student\BookingController::class, 'cancel'])->name('my-bookings.cancel');
    Route::post('/bookings/{booking}/review', [App\Http\Controllers\Student\BookingController::class, 'saveReview'])->name('bookings.review');
    Route::post('/bookings/{booking}/personal-notes', [App\Http\Controllers\Student\BookingController::class, 'savePersonalNotes'])->name('bookings.personal-notes');
    Route::get('/bookings/{booking}/summary-pdf', [App\Http\Controllers\Student\BookingController::class, 'downloadSummaryPdf'])->name('bookings.summary-pdf');
    
    // Booking Modification routes
    Route::get('/modifications', [App\Http\Controllers\Student\BookingModificationController::class, 'index'])->name('modifications.index');
    Route::get('/modifications/{modification}', [App\Http\Controllers\Student\BookingModificationController::class, 'show'])->name('modifications.show');
    Route::get('/bookings/{booking}/reschedule', [App\Http\Controllers\Student\BookingModificationController::class, 'reschedule'])->name('bookings.reschedule');
    Route::post('/bookings/{booking}/reschedule', [App\Http\Controllers\Student\BookingModificationController::class, 'storeReschedule'])->name('bookings.reschedule.store');
    Route::get('/bookings/{booking}/rebook', [App\Http\Controllers\Student\BookingModificationController::class, 'rebook'])->name('bookings.rebook');
    Route::post('/bookings/{booking}/rebook', [App\Http\Controllers\Student\BookingModificationController::class, 'storeRebook'])->name('bookings.rebook.store');
    Route::delete('/modifications/{modification}/cancel', [App\Http\Controllers\Student\BookingModificationController::class, 'cancel'])->name('modifications.cancel');
    Route::get('/modifications/teacher-availability', [App\Http\Controllers\Student\BookingModificationController::class, 'getTeacherAvailability'])->name('modifications.teacher-availability');
    
    // New Reschedule Flow Routes (UI-based)
    Route::match(['get', 'post'], '/reschedule/class', [App\Http\Controllers\Student\BookingModificationController::class, 'rescheduleClass'])->name('reschedule.class');
    Route::get('/reschedule/session-details', [App\Http\Controllers\Student\BookingModificationController::class, 'rescheduleSessionDetailsGet'])->name('reschedule.session-details.get');
    Route::post('/reschedule/session-details', [App\Http\Controllers\Student\BookingModificationController::class, 'rescheduleSessionDetails'])->name('reschedule.session-details');
    Route::get('/reschedule/pricing-payment', [App\Http\Controllers\Student\BookingModificationController::class, 'reschedulePricingPaymentGet'])->name('reschedule.pricing-payment.get');
    Route::post('/reschedule/pricing-payment', [App\Http\Controllers\Student\BookingModificationController::class, 'reschedulePricingPayment'])->name('reschedule.pricing-payment');
    Route::post('/reschedule/submit', [App\Http\Controllers\Student\BookingModificationController::class, 'submitReschedule'])->name('reschedule.submit');
    Route::post('/reschedule/check-existing', [App\Http\Controllers\Student\BookingModificationController::class, 'checkExistingModification'])->name('reschedule.check-existing');
    
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

// Teacher routes
Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifications', function () {
        return Inertia::render('teacher/notifications/notifications');
    })->name('notifications');
    
    // Booking Modification routes for teachers
    Route::get('/modifications', [App\Http\Controllers\Teacher\BookingModificationController::class, 'index'])->name('modifications.index');
    Route::get('/modifications/{modification}', [App\Http\Controllers\Teacher\BookingModificationController::class, 'show'])->name('modifications.show');
    Route::post('/modifications/{modification}/approve', [App\Http\Controllers\Teacher\BookingModificationController::class, 'approve'])->name('modifications.approve');
    Route::post('/modifications/{modification}/reject', [App\Http\Controllers\Teacher\BookingModificationController::class, 'reject'])->name('modifications.reject');
    Route::get('/modifications/statistics', [App\Http\Controllers\Teacher\BookingModificationController::class, 'statistics'])->name('modifications.statistics');
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
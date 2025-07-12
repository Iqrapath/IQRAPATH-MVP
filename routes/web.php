<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Guardian\DashboardController as GuardianDashboardController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\UnassignedController;
use App\Http\Controllers\UserStatusController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentVerificationController;
use App\Http\Controllers\SubscriptionController;
use App\Models\NotificationRecipient;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

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
Route::middleware(['auth', 'verified', 'role:super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
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
    
    // Subscription plan management
    Route::resource('subscriptions', SubscriptionPlanController::class);
    Route::post('/subscriptions/{subscriptionPlan}/toggle-active', [SubscriptionPlanController::class, 'toggleActive'])
        ->name('subscriptions.toggle-active');
    Route::post('/subscriptions/{subscriptionPlan}/duplicate', [SubscriptionPlanController::class, 'duplicate'])
        ->name('subscriptions.duplicate');
    Route::get('/subscriptions/{subscriptionPlan}/enrolled-users', [SubscriptionPlanController::class, 'enrolledUsers'])
        ->name('subscriptions.enrolled-users');
    
    // Notification routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/create', [NotificationController::class, 'create'])->name('create');
        Route::post('/', [NotificationController::class, 'store'])->name('store');
        Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
        Route::get('/{notification}/edit', [NotificationController::class, 'edit'])->name('edit');
        Route::put('/{notification}', [NotificationController::class, 'update'])->name('update');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::post('/{notification}/send', [NotificationController::class, 'send'])->name('send');
        
        // Templates
        Route::get('/templates', [NotificationController::class, 'templates'])->name('templates');
        Route::get('/templates/create', [NotificationController::class, 'createTemplate'])->name('templates.create');
        Route::post('/templates', [NotificationController::class, 'storeTemplate'])->name('templates.store');
        
        // Triggers
        Route::get('/triggers', [NotificationController::class, 'triggers'])->name('triggers');
        Route::get('/triggers/create', [NotificationController::class, 'createTrigger'])->name('triggers.create');
        Route::post('/triggers', [NotificationController::class, 'storeTrigger'])->name('triggers.store');
    });
});

// Teacher routes
Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
        
    // Subject routes nested under teacher
    Route::resource('subjects', SubjectController::class);
    
    // Document management routes
    Route::resource('documents', DocumentController::class);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
});

// Student routes
Route::middleware(['auth', 'verified', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
});

// Guardian routes
Route::middleware(['auth', 'verified', 'role:guardian'])->prefix('guardian')->name('guardian.')->group(function () {
    Route::get('/dashboard', [GuardianDashboardController::class, 'index'])->name('dashboard');
});

// User status routes
Route::post('user/status', [UserStatusController::class, 'update'])->name('user.status.update');

// Zoom Webhook (no auth required)
Route::post('/api/zoom/webhook', [App\Http\Controllers\ZoomWebhookController::class, 'handle'])
    ->name('zoom.webhook');

// Session Attendance Routes
Route::middleware(['auth'])->group(function () {
    Route::post('/sessions/{session}/teacher-join', [App\Http\Controllers\SessionAttendanceController::class, 'teacherJoin'])
        ->name('sessions.teacher-join');
    
    Route::post('/sessions/{session}/student-join', [App\Http\Controllers\SessionAttendanceController::class, 'studentJoin'])
        ->name('sessions.student-join');
    
    Route::post('/sessions/{session}/teacher-leave', [App\Http\Controllers\SessionAttendanceController::class, 'teacherLeave'])
        ->name('sessions.teacher-leave');
    
    Route::post('/sessions/{session}/student-leave', [App\Http\Controllers\SessionAttendanceController::class, 'studentLeave'])
        ->name('sessions.student-leave');
    
    Route::post('/sessions/{session}/update-zoom-attendance', [App\Http\Controllers\SessionAttendanceController::class, 'updateZoomAttendance'])
        ->name('sessions.update-zoom-attendance');
    
    Route::post('/sessions/{session}/mark-completed', [App\Http\Controllers\SessionAttendanceController::class, 'markCompleted'])
        ->name('sessions.mark-completed');
    
    Route::post('/sessions/{session}/mark-cancelled', [App\Http\Controllers\SessionAttendanceController::class, 'markCancelled'])
        ->name('sessions.mark-cancelled');
    
    Route::post('/sessions/{session}/mark-no-show', [App\Http\Controllers\SessionAttendanceController::class, 'markNoShow'])
        ->name('sessions.mark-no-show');
});

// Financial Routes for Teachers
Route::middleware(['auth'])->prefix('teacher/financial')->name('teacher.financial.')->group(function () {
    Route::get('/', [App\Http\Controllers\Teacher\FinancialController::class, 'index'])->name('dashboard');
    Route::get('/transactions', [App\Http\Controllers\Teacher\FinancialController::class, 'transactions'])->name('transactions');
    Route::get('/transactions/{transaction}', [App\Http\Controllers\Teacher\FinancialController::class, 'showTransaction'])->name('transactions.show');
    Route::get('/payout-requests', [App\Http\Controllers\Teacher\FinancialController::class, 'payoutRequests'])->name('payout-requests');
    Route::get('/payout-requests/create', [App\Http\Controllers\Teacher\FinancialController::class, 'createPayoutRequest'])->name('payout-requests.create');
    Route::post('/payout-requests', [App\Http\Controllers\Teacher\FinancialController::class, 'storePayoutRequest'])->name('payout-requests.store');
    Route::get('/payout-requests/{payoutRequest}', [App\Http\Controllers\Teacher\FinancialController::class, 'showPayoutRequest'])->name('payout-requests.show');
    Route::post('/payout-requests/{payoutRequest}/cancel', [App\Http\Controllers\Teacher\FinancialController::class, 'cancelPayoutRequest'])->name('payout-requests.cancel');
});

// Financial Routes for Admins
Route::middleware(['auth', 'role:super-admin'])->prefix('admin/financial')->name('admin.financial.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\FinancialManagementController::class, 'index'])->name('dashboard');
    Route::get('/transactions', [App\Http\Controllers\Admin\FinancialManagementController::class, 'transactions'])->name('transactions');
    Route::get('/transactions/{transaction}', [App\Http\Controllers\Admin\FinancialManagementController::class, 'showTransaction'])->name('transactions.show');
    Route::get('/payout-requests', [App\Http\Controllers\Admin\FinancialManagementController::class, 'payoutRequests'])->name('payout-requests');
    Route::get('/payout-requests/{payoutRequest}', [App\Http\Controllers\Admin\FinancialManagementController::class, 'showPayoutRequest'])->name('payout-requests.show');
    Route::post('/payout-requests/{payoutRequest}/approve', [App\Http\Controllers\Admin\FinancialManagementController::class, 'approvePayoutRequest'])->name('payout-requests.approve');
    Route::post('/payout-requests/{payoutRequest}/decline', [App\Http\Controllers\Admin\FinancialManagementController::class, 'declinePayoutRequest'])->name('payout-requests.decline');
    Route::post('/payout-requests/{payoutRequest}/mark-paid', [App\Http\Controllers\Admin\FinancialManagementController::class, 'markPayoutRequestAsPaid'])->name('payout-requests.mark-paid');
    Route::get('/system-adjustments/create', [App\Http\Controllers\Admin\FinancialManagementController::class, 'createSystemAdjustment'])->name('system-adjustments.create');
    Route::post('/system-adjustments', [App\Http\Controllers\Admin\FinancialManagementController::class, 'storeSystemAdjustment'])->name('system-adjustments.store');
    Route::get('/refunds/{transaction}/create', [App\Http\Controllers\Admin\FinancialManagementController::class, 'createRefund'])->name('refunds.create');
    Route::post('/refunds/{transaction}', [App\Http\Controllers\Admin\FinancialManagementController::class, 'storeRefund'])->name('refunds.store');
    Route::get('/teacher-earnings', [App\Http\Controllers\Admin\FinancialManagementController::class, 'teacherEarnings'])->name('teacher-earnings');
});

// User subscription routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Subscription routes
    Route::get('/subscriptions/plans', [SubscriptionController::class, 'plans'])->name('subscriptions.plans');
    Route::get('/subscriptions/checkout/{plan}', [SubscriptionController::class, 'checkout'])->name('subscriptions.checkout');
    Route::post('/subscriptions/purchase/{plan}', [SubscriptionController::class, 'purchase'])->name('subscriptions.purchase');
    Route::get('/subscriptions/my', [SubscriptionController::class, 'mySubscriptions'])->name('subscriptions.my');
    Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('/subscriptions/{subscription}/toggle-auto-renew', [SubscriptionController::class, 'toggleAutoRenew'])
        ->name('subscriptions.toggle-auto-renew');
    Route::post('/subscriptions/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('subscriptions.renew');
});

// Payment routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Payment methods
    Route::get('/payment/methods/{plan}', [App\Http\Controllers\PaymentController::class, 'showPaymentMethods'])
        ->name('payment.methods');
    
    // Process payments
    Route::post('/payment/paystack/{plan}', [App\Http\Controllers\PaymentController::class, 'processPaystack'])
        ->name('payment.paystack');
    Route::post('/payment/wallet/{plan}', [App\Http\Controllers\PaymentController::class, 'processWallet'])
        ->name('payment.wallet');
    
    // Payment verification
    Route::get('/payment/verify/{gateway}/{reference}', [App\Http\Controllers\PaymentController::class, 'verifyPayment'])
        ->name('payment.verify');
    
    // Wallet management
    Route::get('/wallet', [App\Http\Controllers\PaymentController::class, 'managePaymentMethods'])
        ->name('wallet.manage');
    Route::post('/wallet/add-funds', [App\Http\Controllers\PaymentController::class, 'addFunds'])
        ->name('wallet.add-funds');
    Route::post('/wallet/payment-method/remove', [App\Http\Controllers\PaymentController::class, 'removePaymentMethod'])
        ->name('wallet.payment-method.remove');
    Route::post('/wallet/payment-method/default', [App\Http\Controllers\PaymentController::class, 'setDefaultPaymentMethod'])
        ->name('wallet.payment-method.default');
    
    // Paystack wallet funding
    Route::get('/wallet/paystack/initialize', [App\Http\Controllers\PaymentController::class, 'initializePaystackWalletFunding'])
        ->name('wallet.paystack.initialize');
});

// Payment webhooks (no auth required)
Route::post('/api/payment/webhook/{gateway}', [App\Http\Controllers\PaymentController::class, 'webhook'])
    ->name('payment.webhook');

// User notification API routes
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index'])
        ->name('api.notifications');
    
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead'])
        ->name('api.notifications.read');
    
    Route::post('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead'])
        ->name('api.notifications.read-all');
});

// User notification routes
Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [App\Http\Controllers\UserNotificationController::class, 'index'])->name('index');
    Route::get('/{id}', [App\Http\Controllers\UserNotificationController::class, 'show'])->name('show');
    Route::post('/{id}/read', [App\Http\Controllers\UserNotificationController::class, 'markAsRead'])->name('read');
    Route::post('/read-all', [App\Http\Controllers\UserNotificationController::class, 'markAllAsRead'])->name('read-all');
    Route::delete('/{id}', [App\Http\Controllers\UserNotificationController::class, 'destroy'])->name('destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

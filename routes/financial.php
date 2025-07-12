<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Financial Routes
|--------------------------------------------------------------------------
|
| Here is where you can register financial related routes for your application.
| These routes handle teacher earnings, payouts, transactions, and admin financial management.
|
*/

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
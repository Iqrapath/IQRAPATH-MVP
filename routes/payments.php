<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payment Routes
|--------------------------------------------------------------------------
|
| Here is where you can register payment related routes for your application.
| These routes handle payment methods, processing, verification, and wallet management.
|
*/

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
    
    // Withdrawal routes
    Route::post('/withdrawal/process', [App\Http\Controllers\WithdrawalController::class, 'processWithdrawal'])
        ->name('withdrawal.process');
    Route::get('/withdrawal/callback/{method}', [App\Http\Controllers\WithdrawalController::class, 'handleCallback'])
        ->name('withdrawal.callback');
    Route::get('/withdrawal/fee-preview', [App\Http\Controllers\WithdrawalController::class, 'getFeePreview'])
        ->name('withdrawal.fee-preview');
    Route::get('/withdrawal/status/{id}', [App\Http\Controllers\WithdrawalController::class, 'getWithdrawalStatus'])
        ->name('withdrawal.status');
});

// Payment webhooks (no auth required)
Route::post('/api/payment/webhook/{gateway}', [App\Http\Controllers\PaymentController::class, 'webhook'])
    ->name('payment.webhook'); 
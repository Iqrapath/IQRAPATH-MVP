<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhooks\PaystackWebhookController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use App\Http\Controllers\Webhooks\PayPalWebhookController;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle webhooks from payment gateways for payout status updates.
| They are excluded from CSRF protection in VerifyCsrfToken middleware.
|
*/

// PayStack Webhooks
Route::post('/webhooks/paystack/transfer', [PaystackWebhookController::class, 'handleTransferWebhook'])
    ->name('webhooks.paystack.transfer');

// Stripe Webhooks
Route::post('/webhooks/stripe/payout', [StripeWebhookController::class, 'handlePayoutWebhook'])
    ->name('webhooks.stripe.payout');

// PayPal Webhooks
Route::post('/webhooks/paypal/payout', [PayPalWebhookController::class, 'handlePayoutWebhook'])
    ->name('webhooks.paypal.payout');

// Paystack Virtual Account Webhook
Route::post('/webhooks/paystack/virtual-account', [PaystackWebhookController::class, 'handleVirtualAccountWebhook'])
    ->name('webhooks.paystack.virtual-account');

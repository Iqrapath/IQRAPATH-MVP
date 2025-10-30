<?php

use App\Http\Controllers\Webhooks\StripeWebhookController;
use App\Http\Controllers\Webhooks\PaystackWebhookController;
use App\Http\Controllers\Webhooks\PayPalWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle incoming webhooks from payment gateways.
| They are protected by signature verification middleware.
| CSRF protection is disabled for these routes.
|
*/

// Stripe Webhooks (with signature verification)
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->middleware('verify.stripe')
    ->name('webhooks.stripe');

// Paystack Webhooks (with signature verification)
Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])
    ->middleware('verify.paystack')
    ->name('webhooks.paystack');

// PayPal Webhooks (with signature verification)
Route::post('/webhooks/paypal', [PayPalWebhookController::class, 'handle'])
    ->middleware('verify.paypal')
    ->name('webhooks.paypal');

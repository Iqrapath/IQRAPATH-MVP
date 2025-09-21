<?php

use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Subscription Routes
|--------------------------------------------------------------------------
|
| Here is where you can register subscription related routes for your application.
| These routes handle subscription plans, purchases, renewals, and admin management.
|
*/

// Admin subscription plan management
Route::middleware(['auth', 'verified', 'role:super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('subscription-plans', SubscriptionPlanController::class)->parameters([
    'subscription-plans' => 'subscriptionPlan'
]);
    Route::patch('/subscription-plans/{subscriptionPlan}/toggle-active', [SubscriptionPlanController::class, 'toggleActive'])
        ->name('subscription-plans.toggle-active');
    Route::post('/subscription-plans/{subscriptionPlan}/duplicate', [SubscriptionPlanController::class, 'duplicate'])
        ->name('subscription-plans.duplicate');
    Route::get('/subscription-plans/{subscriptionPlan}/enrolled-users', [SubscriptionPlanController::class, 'enrolledUsers'])
        ->name('subscription-plans.enrolled-users');
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
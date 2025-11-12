<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionTransaction;
use App\Services\PaymentGatewayService;
use App\Services\SubscriptionService;
use App\Services\UnifiedWalletService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionPaymentController extends Controller
{
    public function __construct(
        private PaymentGatewayService $paymentGatewayService,
        private SubscriptionService $subscriptionService,
        private UnifiedWalletService $walletService,
        private NotificationService $notificationService
    ) {}

    /**
     * Process wallet payment for subscription
     */
    public function processWallet(Request $request): JsonResponse
    {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:NGN,USD'
        ]);

        $user = Auth::user();
        $subscription = Subscription::where('id', $request->subscription_id)
            ->where('user_id', $user->id)
            ->with('plan')
            ->firstOrFail();

        try {
            DB::beginTransaction();

            // Check wallet balance
            $walletBalance = $this->walletService->getUserWalletBalance($user);
            if ($walletBalance < $request->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance',
                    'code' => 'INSUFFICIENT_FUNDS',
                    'data' => [
                        'balance' => $walletBalance,
                        'required' => $request->amount,
                        'shortfall' => $request->amount - $walletBalance
                    ]
                ], 400);
            }

            // Get student wallet and deduct amount
            $wallet = $this->walletService->getStudentWallet($user);
            
            // Deduct from wallet balance
            $wallet->decrement('balance_ngn', $request->currency === 'NGN' ? $request->amount : 0);
            $wallet->decrement('balance_usd', $request->currency === 'USD' ? $request->amount : 0);

            // Create subscription transaction
            $subscriptionTransaction = SubscriptionTransaction::create([
                'transaction_uuid' => \Illuminate\Support\Str::uuid(),
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'type' => 'new_subscription',
                'payment_method' => 'wallet',
                'payment_reference' => 'WALLET_' . time() . '_' . $user->id,
                'status' => 'completed',
                'payment_details' => [
                    'wallet_id' => $wallet->id,
                    'payment_type' => 'wallet_debit',
                    'previous_balance' => $walletBalance,
                    'new_balance' => $this->walletService->getUserWalletBalance($user)
                ]
            ]);

            // Activate subscription
            $this->subscriptionService->activateSubscription($subscription);

            // Send notification
            $this->notificationService->createNotification(
                $user,
                'subscription_activated',
                [
                    'title' => 'Subscription Activated',
                    'body' => "Your {$subscription->plan->name} subscription has been activated successfully.",
                    'subscription_id' => $subscription->id,
                    'plan_name' => $subscription->plan->name,
                ],
                'success'
            );

            DB::commit();

            Log::info('Subscription wallet payment successful', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'transaction_id' => $subscriptionTransaction->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment successful. Subscription activated.',
                'data' => [
                    'transaction_id' => $subscriptionTransaction->id,
                    'subscription' => $subscription->fresh()->load('plan'),
                    'wallet_balance' => $this->walletService->getUserWalletBalance($user)
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Subscription wallet payment failed', [
                'user_id' => $user->id,
                'subscription_id' => $request->subscription_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment failed. Please try again.',
                'code' => 'PAYMENT_FAILED'
            ], 500);
        }
    }

    /**
     * Process card payment for subscription
     */
    public function processCard(Request $request): JsonResponse
    {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:NGN,USD',
            'payment_method_id' => 'required|string',
            'save_card' => 'boolean'
        ]);

        $user = Auth::user();
        $subscription = Subscription::where('id', $request->subscription_id)
            ->where('user_id', $user->id)
            ->with('plan')
            ->firstOrFail();

        try {
            DB::beginTransaction();

            // Only Stripe is supported for card payments (both NGN and USD)
            // Stripe supports multiple currencies including NGN
            $gateway = 'stripe';

            // Process payment through Stripe
            $paymentResult = $this->paymentGatewayService->processPayment([
                'gateway' => $gateway,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'payment_method_id' => $request->payment_method_id,
                'customer_email' => $user->email,
                'description' => "Subscription payment: {$subscription->plan->name}",
                'return_url' => route('student.plans.payment-success', ['subscriptionUuid' => $subscription->subscription_uuid]),
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'plan_id' => $subscription->plan_id
                ]
            ]);

            if ($paymentResult['success']) {
                // Create subscription transaction
                $subscriptionTransaction = SubscriptionTransaction::create([
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'amount' => $request->amount,
                    'currency' => $request->currency,
                    'payment_method' => 'card',
                    'payment_reference' => $paymentResult['reference'],
                    'status' => 'completed',
                    'payment_details' => [
                        'gateway' => $gateway,
                        'gateway_transaction_id' => $paymentResult['transaction_id'],
                        'payment_method_id' => $request->payment_method_id
                    ]
                ]);

                // Activate subscription and save payment method reference for auto-renewal
                $this->subscriptionService->activateSubscription($subscription, [
                    'payment_reference' => $request->payment_method_id, // Save Stripe payment method ID for future renewals
                    'payment_details' => [
                        'gateway' => $gateway,
                        'gateway_transaction_id' => $paymentResult['transaction_id'],
                        'payment_method_id' => $request->payment_method_id
                    ]
                ]);

                // Send notification
                $this->notificationService->createNotification(
                    $user,
                    'subscription_activated',
                    [
                        'title' => 'Subscription Activated',
                        'body' => "Your {$subscription->plan->name} subscription has been activated successfully.",
                        'subscription_id' => $subscription->id,
                        'plan_name' => $subscription->plan->name,
                    ],
                    'success'
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful. Subscription activated.',
                    'data' => [
                        'transaction_id' => $subscriptionTransaction->id,
                        'subscription' => $subscription->fresh()->load('plan')
                    ]
                ]);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $paymentResult['message'] ?? 'Payment failed',
                    'code' => 'PAYMENT_FAILED'
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Subscription card payment failed', [
                'user_id' => $user->id,
                'subscription_id' => $request->subscription_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment failed. Please try again.',
                'code' => 'PAYMENT_FAILED'
            ], 500);
        }
    }

    /**
     * Process PayPal payment for subscription
     */
    public function processPayPal(Request $request): JsonResponse
    {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:USD' // PayPal only supports USD
        ]);

        $user = Auth::user();
        $subscription = Subscription::where('id', $request->subscription_id)
            ->where('user_id', $user->id)
            ->with('plan')
            ->firstOrFail();

        try {
            // Create PayPal payment
            $paypalResult = $this->paymentGatewayService->createPayPalPayment([
                'amount' => $request->amount,
                'currency' => 'USD',
                'description' => "Subscription payment: {$subscription->plan->name}",
                'return_url' => route('student.plans.payment.paypal.success', ['subscription_id' => $subscription->id]),
                'cancel_url' => route('student.plans.payment.paypal.cancel', ['subscription_id' => $subscription->id]),
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id
                ]
            ]);

            if ($paypalResult['success']) {
                // Store pending transaction
                SubscriptionTransaction::create([
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'amount' => $request->amount,
                    'currency' => 'USD',
                    'payment_method' => 'paypal',
                    'payment_reference' => $paypalResult['payment_id'],
                    'status' => 'pending',
                    'payment_details' => $paypalResult
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'approval_url' => $paypalResult['approval_url'],
                        'payment_id' => $paypalResult['payment_id']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $paypalResult['message'] ?? 'Failed to initialize PayPal payment'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('PayPal payment initialization failed', [
                'user_id' => $user->id,
                'subscription_id' => $request->subscription_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle PayPal payment success callback
     */
    public function paypalSuccess(Request $request): JsonResponse
    {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'token' => 'required|string',
        ]);

        $user = Auth::user();
        $subscription = Subscription::where('id', $request->subscription_id)
            ->where('user_id', $user->id)
            ->with('plan')
            ->firstOrFail();

        try {
            DB::beginTransaction();

            // Capture PayPal payment
            $captureResult = $this->paymentGatewayService->capturePayPalPayment($request->token);

            if ($captureResult['success']) {
                // Update transaction
                $transaction = SubscriptionTransaction::where('subscription_id', $subscription->id)
                    ->where('payment_reference', $request->token)
                    ->firstOrFail();

                $transaction->update([
                    'status' => 'completed',
                    'payment_details' => $captureResult
                ]);

                // Activate subscription
                $this->subscriptionService->activateSubscription($subscription);

                // Send notification
                $this->notificationService->createNotification(
                    $user,
                    'subscription_activated',
                    [
                        'title' => 'Subscription Activated',
                        'body' => "Your {$subscription->plan->name} subscription has been activated successfully.",
                        'subscription_id' => $subscription->id,
                        'plan_name' => $subscription->plan->name,
                    ],
                    'success'
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful. Subscription activated.',
                    'data' => [
                        'transaction_id' => $transaction->id,
                        'subscription' => $subscription->fresh()->load('plan')
                    ]
                ]);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Payment capture failed'
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PayPal payment capture failed', [
                'user_id' => $user->id,
                'subscription_id' => $request->subscription_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle PayPal payment cancellation
     */
    public function paypalCancel(Request $request): RedirectResponse
    {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
        ]);

        $user = Auth::user();
        $subscription = Subscription::where('id', $request->subscription_id)
            ->where('user_id', $user->id)
            ->with('plan')
            ->firstOrFail();

        // Update transaction status to failed (cancelled payments are marked as failed)
        SubscriptionTransaction::where('subscription_id', $subscription->id)
            ->where('status', 'pending')
            ->update(['status' => 'failed']);

        Log::info('PayPal payment cancelled', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id
        ]);

        // Redirect back to the plan page with cancellation message
        return redirect()
            ->route('student.plans.show', $subscription->subscription_plan_id)
            ->with('error', 'Payment was cancelled. You can try again when ready.');
    }

    /**
     * Show payment success page
     */
    public function success(Request $request, string $subscriptionUuid): Response
    {
        $user = Auth::user();
        $subscription = Subscription::where('subscription_uuid', $subscriptionUuid)
            ->where('user_id', $user->id)
            ->with('plan')
            ->firstOrFail();

        return Inertia::render('student/plans/payment-success', [
            'subscription' => $subscription,
        ]);
    }

    /**
     * Show payment failure page
     */
    public function failure(Request $request, string $subscriptionUuid): Response
    {
        $user = Auth::user();
        $subscription = Subscription::where('subscription_uuid', $subscriptionUuid)
            ->where('user_id', $user->id)
            ->with('plan')
            ->firstOrFail();

        return Inertia::render('student/plans/payment-failed', [
            'subscription' => $subscription,
        ]);
    }
}

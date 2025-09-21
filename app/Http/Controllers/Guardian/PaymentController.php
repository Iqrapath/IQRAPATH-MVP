<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\GuardianWallet;
use App\Models\UnifiedTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Get Stripe publishable key for guardian payments
     */
    public function getPublishableKey(): JsonResponse
    {
        try {
            $publishableKey = config('services.stripe.publishable_key');
            
            if (!$publishableKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe configuration not found'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'publishable_key' => $publishableKey
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get Stripe publishable key for guardian', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment configuration'
            ], 500);
        }
    }

    /**
     * Fund guardian wallet using Stripe
     */
    public function fundWallet(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000|max:1000000', // Minimum 1000 NGN (about $0.60 USD)
            'gateway' => 'required|string|in:stripe',
            'payment_method_id' => 'required|string',
            'rememberCard' => 'boolean'
        ]);

        try {
            $user = $request->user();
            $amount = (float) $request->amount;
            $paymentMethodId = $request->payment_method_id;
            $rememberCard = $request->boolean('rememberCard');

            // Get or create guardian wallet
            $guardianWallet = $user->guardianWallet;
            if (!$guardianWallet) {
                $guardianWallet = GuardianWallet::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'total_spent_on_children' => 0,
                    'total_refunded' => 0,
                    'auto_fund_children' => false,
                    'auto_fund_threshold' => 0,
                    'family_spending_limits' => [],
                    'child_allowances' => []
                ]);
            }

            // Process payment with Stripe
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret_key'));
            
            // Create payment intent
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => 'usd', // Use USD for Stripe (NGN not supported in test mode)
                'payment_method' => $paymentMethodId,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => route('guardian.dashboard'),
                'metadata' => [
                    'user_id' => $user->id,
                    'user_type' => 'guardian',
                    'wallet_type' => 'guardian_wallet',
                    'original_amount_ngn' => $amount
                ]
            ]);

            if ($paymentIntent->status === 'succeeded') {
                // Add funds to guardian wallet
                $transaction = $guardianWallet->addFunds($amount, 'Wallet funding via Stripe', [
                    'payment_intent_id' => $paymentIntent->id,
                    'payment_method_id' => $paymentMethodId,
                    'remember_card' => $rememberCard,
                    'gateway' => 'stripe'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Wallet funded successfully',
                    'data' => [
                        'transaction_id' => $transaction->transaction_uuid,
                        'amount' => $amount,
                        'new_balance' => $guardianWallet->fresh()->balance,
                        'currency' => 'NGN'
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not completed. Please try again.'
                ], 400);
            }

        } catch (\Stripe\Exception\CardException $e) {
            Log::error('Stripe card error for guardian payment', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'decline_code' => $e->getDeclineCode()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getDeclineCode() ? 
                    'Card declined: ' . $e->getDeclineCode() : 
                    'Card payment failed. Please check your card details and try again.'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Guardian wallet funding error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment failed. Please try again later.'
            ], 500);
        }
    }

    /**
     * Get guardian wallet balance
     */
    public function getBalance(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $guardianWallet = $user->guardianWallet;

            if (!$guardianWallet) {
                return response()->json([
                    'success' => true,
                    'balance' => 0,
                    'currency' => 'NGN'
                ]);
            }

            return response()->json([
                'success' => true,
                'balance' => $guardianWallet->balance,
                'currency' => 'NGN'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get guardian wallet balance', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get wallet balance'
            ], 500);
        }
    }

    /**
     * Get guardian wallet funding configuration
     */
    public function getFundingConfig(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'config' => [
                    'min_amount' => 100,
                    'max_amount' => 1000000,
                    'currency' => 'NGN',
                    'supported_gateways' => ['stripe'],
                    'default_gateway' => 'stripe'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get guardian funding config', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get funding configuration'
            ], 500);
        }
    }
}

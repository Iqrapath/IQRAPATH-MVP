<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\CardException;
use Stripe\Exception\RateLimitException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret_key'));
    }

    /**
     * Process a payment to fund user wallet
     */
    public function processWalletFunding(User $user, array $paymentData): array
    {
        return DB::transaction(function () use ($user, $paymentData) {
            try {
                // Validate payment data
                $this->validatePaymentData($paymentData);

                $gateway = $paymentData['gateway'] ?? 'stripe';
                
                // Process payment based on gateway
                if ($gateway === 'paystack') {
                    return $this->processPaystackPayment($user, $paymentData);
                } else {
                    return $this->processStripePayment($user, $paymentData);
                }

            } catch (\Exception $e) {
                Log::error('Payment Processing Error', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'gateway' => $paymentData['gateway'] ?? 'stripe'
                ]);
                
                return [
                    'success' => false,
                    'error' => 'processing_error',
                    'message' => 'An error occurred while processing your payment. Please try again.'
                ];
            }
        });
    }

    /**
     * Process Stripe payment
     */
    private function processStripePayment(User $user, array $paymentData): array
    {
        try {
            // Create Stripe payment intent
            $paymentIntent = $this->createPaymentIntent($paymentData);

            // Confirm the payment
            $confirmedPayment = $this->confirmPayment($paymentIntent, $paymentData);

            // Update user wallet
            $this->updateUserWallet($user, $paymentData['amount']);

            // Create transaction records
            $transaction = $this->createTransactionRecords($user, $paymentData, $confirmedPayment, 'stripe');
        
            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'payment_intent_id' => $confirmedPayment->id,
                'amount' => $paymentData['amount'],
                'message' => 'Payment processed successfully'
            ];

        } catch (CardException $e) {
            Log::error('Stripe Card Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'decline_code' => $e->getDeclineCode()
            ]);
            
            return [
                'success' => false,
                'error' => 'Card declined: ' . $e->getDeclineCode(),
                'message' => $this->getCardErrorMessage($e->getDeclineCode())
            ];
        }
    }

    /**
     * Process Paystack payment
     */
    private function processPaystackPayment(User $user, array $paymentData): array
    {
        try {
            // Initialize Paystack transaction
            $paystackResponse = $this->initializePaystackTransaction($user, $paymentData);

            if (!$paystackResponse['status']) {
                return [
                    'success' => false,
                    'error' => 'paystack_error',
                    'message' => $paystackResponse['message'] ?? 'Failed to initialize payment'
                ];
            }

            // Update user wallet
            $this->updateUserWallet($user, $paymentData['amount']);

            // Create transaction records
            $transaction = $this->createPaystackTransactionRecords($user, $paymentData, $paystackResponse);
        
            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'paystack_reference' => $paystackResponse['data']['reference'],
                'authorization_url' => $paystackResponse['data']['authorization_url'],
                'amount' => $paymentData['amount'],
                'message' => 'Payment initialized successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Paystack Payment Error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'paystack_error',
                'message' => 'An error occurred while processing your payment. Please try again.'
            ];
        }
    }

    /**
     * Validate payment data
     */
    private function validatePaymentData(array $paymentData): void
    {
        $requiredFields = ['amount'];
        
        foreach ($requiredFields as $field) {
            if (!isset($paymentData[$field]) || empty($paymentData[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Validate amount
        if ($paymentData['amount'] <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }

        // Validate payment method ID for Stripe
        if (($paymentData['gateway'] ?? 'stripe') === 'stripe') {
            if (!isset($paymentData['payment_method_id']) || !preg_match('/^pm_/', $paymentData['payment_method_id'])) {
                throw new \InvalidArgumentException('Invalid payment method ID for Stripe');
            }
        }
    }
    
    /**
     * Create Stripe payment intent
     */
    private function createPaymentIntent(array $paymentData): \Stripe\PaymentIntent
    {
        return $this->stripe->paymentIntents->create([
            'amount' => $paymentData['amount'] * 100, // Convert to cents
            'currency' => 'ngn', // Nigerian Naira
            'payment_method_types' => ['card'],
            'metadata' => [
                'user_id' => $paymentData['user_id'] ?? null,
                'funding_type' => 'wallet_funding'
            ]
        ]);
    }

    /**
     * Confirm payment with payment method ID
     */
    private function confirmPayment(\Stripe\PaymentIntent $paymentIntent, array $paymentData): \Stripe\PaymentIntent
    {
        // Confirm payment intent using the provided payment method ID
        return $this->stripe->paymentIntents->confirm($paymentIntent->id, [
            'payment_method' => $paymentData['payment_method_id'],
        ]);
    }

    /**
     * Update user wallet balance
     */
    private function updateUserWallet(User $user, float $amount): void
    {
        $wallet = $user->getOrCreateWallet();
        $wallet->increment('balance', $amount);
    }

    /**
     * Create transaction records for Stripe
     */
    private function createTransactionRecords(User $user, array $paymentData, \Stripe\PaymentIntent $paymentIntent, string $gateway = 'stripe'): Transaction
    {
        // Create main transaction record
        $transaction = Transaction::create([
            'transaction_uuid' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'transaction_type' => 'wallet_funding',
            'amount' => $paymentData['amount'],
            'status' => 'completed',
            'description' => 'Wallet funding via credit card',
            'transaction_date' => now()->toDateString(),
            'gateway' => $gateway,
            'gateway_reference' => $paymentIntent->id,
            'metadata' => [
                'payment_method' => 'credit_card',
                'payment_method_id' => $paymentData['payment_method_id'],
                'remember_card' => $paymentData['rememberCard'] ?? false
            ]
        ]);

        // Create wallet transaction record
        $wallet = $user->getOrCreateWallet();
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'transaction_id' => $transaction->id,
            'transaction_type' => 'credit',
            'amount' => $paymentData['amount'],
            'balance_before' => $wallet->balance - $paymentData['amount'],
            'balance_after' => $wallet->balance,
            'description' => 'Wallet funding via credit card',
            'transaction_date' => now(),
            'status' => 'completed'
        ]);

        return $transaction;
    }

    /**
     * Initialize Paystack transaction
     */
    private function initializePaystackTransaction(User $user, array $paymentData): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.paystack.secret_key'),
            'Content-Type' => 'application/json',
        ])->post(config('services.paystack.base_url') . '/transaction/initialize', [
            'email' => $user->email,
            'amount' => $paymentData['amount'] * 100, // Convert to kobo
            'currency' => 'NGN',
            'reference' => 'WALLET_' . time() . '_' . $user->id,
            'callback_url' => url('/payment/paystack/callback'),
            'metadata' => [
                'user_id' => $user->id,
                'funding_type' => 'wallet_funding',
                'amount_original' => $paymentData['amount']
            ]
        ]);

        return $response->json();
    }

    /**
     * Create transaction records for Paystack
     */
    private function createPaystackTransactionRecords(User $user, array $paymentData, array $paystackResponse): Transaction
    {
        // Create main transaction record
        $transaction = Transaction::create([
            'transaction_uuid' => \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'transaction_type' => 'wallet_funding',
            'amount' => $paymentData['amount'],
            'status' => 'pending',
            'description' => 'Wallet funding via Paystack',
            'transaction_date' => now()->toDateString(),
            'gateway' => 'paystack',
            'gateway_reference' => $paystackResponse['data']['reference'],
            'metadata' => [
                'payment_method' => 'paystack',
                'authorization_url' => $paystackResponse['data']['authorization_url'],
                'access_code' => $paystackResponse['data']['access_code']
            ]
        ]);

        // Create wallet transaction record
        $wallet = $user->getOrCreateWallet();
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'transaction_id' => $transaction->id,
            'transaction_type' => 'credit',
            'amount' => $paymentData['amount'],
            'balance_before' => $wallet->balance - $paymentData['amount'],
            'balance_after' => $wallet->balance,
            'description' => 'Wallet funding via Paystack',
            'transaction_date' => now(),
            'status' => 'pending'
        ]);

        return $transaction;
    }

    /**
     * Get user-friendly card error messages
     */
    private function getCardErrorMessage(?string $declineCode): string
    {
        return match ($declineCode) {
            'card_declined' => 'Your card was declined. Please try a different card.',
            'expired_card' => 'Your card has expired. Please use a different card.',
            'incorrect_cvc' => 'The security code you entered is incorrect.',
            'processing_error' => 'An error occurred while processing your card. Please try again.',
            'insufficient_funds' => 'Your card has insufficient funds.',
            'withdrawal_count_limit_exceeded' => 'You have exceeded the maximum number of attempts. Please try again later.',
            default => 'Your card was declined. Please try a different card or contact your bank.'
        };
    }

    /**
     * Get Stripe publishable key for frontend
     */
    public function getPublishableKey(): string
    {
        return config('services.stripe.publishable_key');
    }

    /**
     * Get Paystack public key for frontend
     */
    public function getPaystackPublicKey(): string
    {
        return config('services.paystack.public_key');
    }

    /**
     * Verify Paystack payment
     */
    public function verifyPaystackPayment(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.secret_key'),
                'Content-Type' => 'application/json',
            ])->get(config('services.paystack.base_url') . '/transaction/verify/' . $reference);

            $result = $response->json();

            if ($result['status'] && $result['data']['status'] === 'success') {
                // Update transaction status
                $transaction = Transaction::where('gateway_reference', $reference)->first();
                if ($transaction) {
                    $transaction->update(['status' => 'completed']);
                    
                    // Update wallet transaction status
                    $walletTransaction = WalletTransaction::where('transaction_id', $transaction->id)->first();
                    if ($walletTransaction) {
                        $walletTransaction->update(['status' => 'completed']);
                    }
                }

                return [
                    'success' => true,
                    'data' => $result['data'],
                    'message' => 'Payment verified successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Payment verification failed'
            ];

        } catch (\Exception $e) {
            Log::error('Paystack Verification Error', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error verifying payment'
            ];
        }
    }
} 
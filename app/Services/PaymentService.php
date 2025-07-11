<?php

namespace App\Services;

use App\Models\PaymentGatewayLog;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\StudentWallet;

class PaymentService
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Initialize a payment with Paystack.
     *
     * @param User $user
     * @param SubscriptionPlan $plan
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function initializePaystackPayment(User $user, SubscriptionPlan $plan, array $data): array
    {
        $currency = $data['currency'] ?? 'naira';
        $amount = $plan->getPriceForCurrency($currency);
        
        // Convert to kobo/cents (Paystack expects amount in smallest currency unit)
        $amountInKobo = $amount * 100;
        
        // Generate a unique reference
        $reference = 'IQRA_' . time() . '_' . Str::random(5);
        
        // Create a pending subscription
        $subscription = $this->subscriptionService->createSubscription($user, $plan, array_merge($data, [
            'payment_reference' => $reference,
        ]));
        
        // Get the latest transaction for this subscription
        $transaction = $subscription->transactions()->latest()->first();
        
        // Prepare request data
        $requestData = [
            'email' => $user->email,
            'amount' => $amountInKobo,
            'currency' => $currency === 'dollar' ? 'USD' : 'NGN',
            'reference' => $reference,
            'callback_url' => route('payments.verify', ['gateway' => 'paystack', 'reference' => $reference]),
            'metadata' => [
                'subscription_id' => $subscription->id,
                'transaction_id' => $transaction->id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'user_id' => $user->id,
                'user_name' => $user->name,
            ],
        ];
        
        // Log the payment attempt
        $gatewayLog = PaymentGatewayLog::create([
            'gateway' => 'paystack',
            'reference' => $reference,
            'user_id' => $user->id,
            'subscription_transaction_id' => $transaction->id,
            'status' => 'pending',
            'amount' => $amount,
            'currency' => $currency === 'dollar' ? 'USD' : 'NGN',
            'request_data' => $requestData,
        ]);
        
        try {
            // Make API request to Paystack
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.secret_key'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', $requestData);
            
            $responseData = $response->json();
            
            // Update gateway log with response
            $gatewayLog->update([
                'response_data' => $responseData,
            ]);
            
            if (!$response->successful() || !isset($responseData['status']) || $responseData['status'] !== true) {
                throw new Exception($responseData['message'] ?? 'Failed to initialize payment');
            }
            
            return [
                'success' => true,
                'authorization_url' => $responseData['data']['authorization_url'],
                'reference' => $reference,
                'subscription' => $subscription,
            ];
        } catch (Exception $e) {
            // Mark the gateway log as failed
            $gatewayLog->markAsFailed(['error' => $e->getMessage()]);
            
            Log::error('Paystack payment initialization failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);
            
            throw new Exception('Payment initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Initialize Paystack payment for wallet funding.
     *
     * @param User $user
     * @param float $amount
     * @param string $currency
     * @return array
     * @throws Exception
     */
    public function initializePaystackWalletFunding(User $user, float $amount, string $currency): array
    {
        $paystackKey = config('services.paystack.secret_key');
        if (!$paystackKey) {
            throw new Exception('Paystack API key not configured');
        }

        // Convert amount to kobo (smallest currency unit)
        $amountInKobo = $amount * 100;
        
        // Generate a unique reference
        $reference = 'IQRA_FUND_' . time() . '_' . $user->id;
        
        // Create payment log
        $paymentLog = PaymentGatewayLog::create([
            'user_id' => $user->id,
            'gateway' => 'paystack',
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'payload' => [
                'type' => 'wallet_funding',
                'amount' => $amount,
                'currency' => $currency,
            ],
        ]);
        
        // Make API request to Paystack
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $paystackKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email' => $user->email,
            'amount' => $amountInKobo,
            'reference' => $reference,
            'callback_url' => route('payment.verify', ['gateway' => 'paystack', 'reference' => $reference]),
            'metadata' => [
                'type' => 'wallet_funding',
                'user_id' => $user->id,
                'currency' => $currency,
            ],
        ]);
        
        if (!$response->successful()) {
            $paymentLog->update([
                'status' => 'failed',
                'response' => $response->json(),
            ]);
            
            throw new Exception('Failed to initialize Paystack payment: ' . ($response->json()['message'] ?? 'Unknown error'));
        }
        
        $responseData = $response->json();
        
        // Update payment log with response
        $paymentLog->update([
            'status' => 'initialized',
            'response' => $responseData,
        ]);
        
        return $responseData['data'];
    }
    
    /**
     * Verify a Paystack payment.
     *
     * @param string $reference
     * @return array
     * @throws Exception
     */
    public function verifyPaystackPayment(string $reference): array
    {
        // Find the payment log
        $gatewayLog = PaymentGatewayLog::where('gateway', 'paystack')
            ->where('reference', $reference)
            ->first();
            
        if (!$gatewayLog) {
            throw new Exception('Payment reference not found');
        }
        
        try {
            // Make API request to Paystack
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.secret_key'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");
            
            $responseData = $response->json();
            
            // Update gateway log with verification response
            $gatewayLog->update([
                'response_data' => array_merge($gatewayLog->response_data ?? [], [
                    'verification' => $responseData,
                ]),
            ]);
            
            if (!$response->successful() || !isset($responseData['status']) || $responseData['status'] !== true) {
                throw new Exception($responseData['message'] ?? 'Failed to verify payment');
            }
            
            $paymentData = $responseData['data'];
            
            // Check if payment was successful
            if ($paymentData['status'] === 'success') {
                // Mark the payment as verified
                $gatewayLog->markAsVerified();
                
                // Update the transaction and subscription
                if ($gatewayLog->subscription_transaction_id) {
                    $transaction = SubscriptionTransaction::find($gatewayLog->subscription_transaction_id);
                    
                    if ($transaction) {
                        $subscription = $transaction->subscription;
                        
                        if ($subscription) {
                            // Activate the subscription
                            $this->subscriptionService->activateSubscription($subscription, [
                                'payment_reference' => $reference,
                                'payment_details' => [
                                    'gateway' => 'paystack',
                                    'transaction_id' => $paymentData['id'],
                                    'authorization_code' => $paymentData['authorization']['authorization_code'] ?? null,
                                    'card_type' => $paymentData['authorization']['card_type'] ?? null,
                                    'last4' => $paymentData['authorization']['last4'] ?? null,
                                    'exp_month' => $paymentData['authorization']['exp_month'] ?? null,
                                    'exp_year' => $paymentData['authorization']['exp_year'] ?? null,
                                    'payment_date' => now()->toDateTimeString(),
                                ],
                            ]);
                            
                            // Save payment method to wallet if authorization is provided
                            if (isset($paymentData['authorization']) && $paymentData['authorization']['reusable']) {
                                $user = User::find($gatewayLog->user_id);
                                
                                if ($user) {
                                    $wallet = $user->getOrCreateWallet();
                                    
                                    $wallet->addPaymentMethod([
                                        'gateway' => 'paystack',
                                        'type' => $paymentData['authorization']['card_type'],
                                        'last4' => $paymentData['authorization']['last4'],
                                        'exp_month' => $paymentData['authorization']['exp_month'],
                                        'exp_year' => $paymentData['authorization']['exp_year'],
                                        'authorization_code' => $paymentData['authorization']['authorization_code'],
                                        'bin' => $paymentData['authorization']['bin'],
                                        'bank' => $paymentData['authorization']['bank'],
                                        'signature' => $paymentData['authorization']['signature'],
                                        'reusable' => $paymentData['authorization']['reusable'],
                                        'country_code' => $paymentData['authorization']['country_code'],
                                    ], true);
                                }
                            }
                        }
                    }
                }
                
                return [
                    'success' => true,
                    'message' => 'Payment verified successfully',
                    'data' => $paymentData,
                ];
            } else {
                // Mark the payment as failed
                $gatewayLog->markAsFailed(['status' => $paymentData['status']]);
                
                return [
                    'success' => false,
                    'message' => 'Payment was not successful',
                    'data' => $paymentData,
                ];
            }
        } catch (Exception $e) {
            // Mark the gateway log as failed
            $gatewayLog->markAsFailed(['error' => $e->getMessage()]);
            
            Log::error('Paystack payment verification failed', [
                'error' => $e->getMessage(),
                'reference' => $reference,
            ]);
            
            throw new Exception('Payment verification failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle Paystack webhook.
     *
     * @param array $payload
     * @return bool
     */
    public function handlePaystackWebhook(array $payload): bool
    {
        try {
            // Verify webhook signature
            if (!$this->verifyPaystackWebhookSignature($payload)) {
                Log::warning('Invalid Paystack webhook signature', ['payload' => $payload]);
                return false;
            }
            
            // Extract event data
            $event = $payload['event'];
            $data = $payload['data'];
            $reference = $data['reference'] ?? null;
            
            if (!$reference) {
                Log::warning('Paystack webhook missing reference', ['payload' => $payload]);
                return false;
            }
            
            // Find the payment log
            $gatewayLog = PaymentGatewayLog::where('gateway', 'paystack')
                ->where('reference', $reference)
                ->first();
                
            if (!$gatewayLog) {
                Log::warning('Paystack webhook for unknown reference', ['reference' => $reference]);
                return false;
            }
            
            // Update gateway log with webhook data
            $gatewayLog->updateWithWebhookData($payload);
            
            // Process based on event type
            switch ($event) {
                case 'charge.success':
                    return $this->processPaystackSuccessfulCharge($gatewayLog, $data);
                    
                case 'charge.failed':
                    return $this->processPaystackFailedCharge($gatewayLog, $data);
                    
                default:
                    // Just log the event
                    Log::info('Unhandled Paystack webhook event', ['event' => $event, 'reference' => $reference]);
                    return true;
            }
        } catch (Exception $e) {
            Log::error('Error processing Paystack webhook', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            
            return false;
        }
    }
    
    /**
     * Process a successful Paystack charge.
     *
     * @param PaymentGatewayLog $gatewayLog
     * @param array $data
     * @return bool
     */
    protected function processPaystackSuccessfulCharge(PaymentGatewayLog $gatewayLog, array $data): bool
    {
        // If already verified, no need to process again
        if ($gatewayLog->verified_at) {
            return true;
        }
        
        try {
            // Mark the payment as verified
            $gatewayLog->markAsVerified();
            
            // Update the transaction and subscription
            if ($gatewayLog->subscription_transaction_id) {
                $transaction = SubscriptionTransaction::find($gatewayLog->subscription_transaction_id);
                
                if ($transaction) {
                    $subscription = $transaction->subscription;
                    
                    if ($subscription) {
                        // Activate the subscription
                        $this->subscriptionService->activateSubscription($subscription, [
                            'payment_reference' => $gatewayLog->reference,
                            'payment_details' => [
                                'gateway' => 'paystack',
                                'transaction_id' => $data['id'],
                                'authorization_code' => $data['authorization']['authorization_code'] ?? null,
                                'card_type' => $data['authorization']['card_type'] ?? null,
                                'last4' => $data['authorization']['last4'] ?? null,
                                'exp_month' => $data['authorization']['exp_month'] ?? null,
                                'exp_year' => $data['authorization']['exp_year'] ?? null,
                                'payment_date' => now()->toDateTimeString(),
                            ],
                        ]);
                        
                        // Save payment method to wallet if authorization is provided
                        if (isset($data['authorization']) && $data['authorization']['reusable']) {
                            $user = User::find($gatewayLog->user_id);
                            
                            if ($user) {
                                $wallet = $user->getOrCreateWallet();
                                
                                $wallet->addPaymentMethod([
                                    'gateway' => 'paystack',
                                    'type' => $data['authorization']['card_type'],
                                    'last4' => $data['authorization']['last4'],
                                    'exp_month' => $data['authorization']['exp_month'],
                                    'exp_year' => $data['authorization']['exp_year'],
                                    'authorization_code' => $data['authorization']['authorization_code'],
                                    'bin' => $data['authorization']['bin'],
                                    'bank' => $data['authorization']['bank'],
                                    'signature' => $data['authorization']['signature'],
                                    'reusable' => $data['authorization']['reusable'],
                                    'country_code' => $data['authorization']['country_code'],
                                ], true);
                            }
                        }
                    }
                }
            }
            
            return true;
        } catch (Exception $e) {
            Log::error('Error processing successful Paystack charge', [
                'error' => $e->getMessage(),
                'gateway_log_id' => $gatewayLog->id,
            ]);
            
            return false;
        }
    }
    
    /**
     * Process a failed Paystack charge.
     *
     * @param PaymentGatewayLog $gatewayLog
     * @param array $data
     * @return bool
     */
    protected function processPaystackFailedCharge(PaymentGatewayLog $gatewayLog, array $data): bool
    {
        // Mark the payment as failed
        $gatewayLog->markAsFailed([
            'failure_data' => $data,
        ]);
        
        // No need to do anything with the subscription - it will remain in pending state
        
        return true;
    }
    
    /**
     * Verify Paystack webhook signature.
     *
     * @param array $payload
     * @return bool
     */
    protected function verifyPaystackWebhookSignature(array $payload): bool
    {
        // In production, implement proper signature verification
        // using the Paystack Webhook Secret
        
        // For now, just return true for development
        return true;
    }
    
    /**
     * Process payment with wallet.
     *
     * @param User $user
     * @param SubscriptionPlan $plan
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function processWalletPayment(User $user, SubscriptionPlan $plan, array $data): array
    {
        $wallet = StudentWallet::where('user_id', $user->id)->first();
        
        if (!$wallet) {
            throw new Exception('User does not have a wallet');
        }
        
        $currency = $data['currency'] ?? 'naira';
        $amount = $plan->getPriceForCurrency($currency);
        
        if ($wallet->balance < $amount) {
            throw new Exception('Insufficient wallet balance');
        }
        
        // Generate a unique reference
        $reference = 'IQRA_WALLET_' . time() . '_' . $user->id;
        
        // Create a subscription
        $subscription = $this->subscriptionService->createSubscription($user, $plan, array_merge($data, [
            'payment_method' => 'wallet',
            'payment_reference' => $reference,
        ]));
        
        // Create payment log
        $paymentLog = PaymentGatewayLog::create([
            'user_id' => $user->id,
            'gateway' => 'wallet',
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'payload' => [
                'type' => 'subscription_payment',
                'plan_id' => $plan->id,
                'subscription_id' => $subscription->id,
            ],
        ]);
        
        try {
            // Deduct from wallet
            $wallet->deductFunds($amount, "Payment for {$plan->name} subscription");
            
            // Update payment log
            $paymentLog->update([
                'status' => 'completed',
                'response' => [
                    'status' => 'success',
                    'message' => 'Payment processed successfully',
                    'transaction_id' => $reference,
                    'payment_date' => now()->toDateTimeString(),
                ],
            ]);
            
            // Activate the subscription
            $this->subscriptionService->activateSubscription($subscription, [
                'payment_reference' => $reference,
                'payment_details' => [
                    'status' => 'success',
                    'transaction_id' => $reference,
                    'payment_date' => now()->toDateTimeString(),
                    'payment_method' => 'wallet',
                ],
            ]);
            
            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'subscription_id' => $subscription->id,
                'reference' => $reference,
            ];
        } catch (Exception $e) {
            // Update payment log
            $paymentLog->update([
                'status' => 'failed',
                'response' => [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ],
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Process subscription renewal.
     *
     * @param Subscription $subscription
     * @return bool
     */
    public function processSubscriptionRenewal(Subscription $subscription): bool
    {
        $user = $subscription->user;
        $plan = $subscription->plan;
        
        if (!$user || !$plan) {
            Log::error('Cannot renew subscription - missing user or plan', [
                'subscription_id' => $subscription->id,
            ]);
            return false;
        }
        
        // Check if auto-renew is enabled
        if (!$subscription->auto_renew) {
            return false;
        }
        
        try {
            $wallet = StudentWallet::where('user_id', $user->id)->first();
            
            // If user has a wallet with sufficient balance, use that
            if ($wallet && $wallet->balance >= $subscription->amount_paid) {
                return $this->renewWithWallet($subscription);
            }
            
            // Otherwise, try to use saved payment method
            if ($wallet && $wallet->default_payment_method) {
                $paymentMethod = $wallet->getDefaultPaymentMethod();
                
                if ($paymentMethod && isset($paymentMethod['gateway'])) {
                    if ($paymentMethod['gateway'] === 'paystack') {
                        return $this->renewWithPaystack($subscription, $paymentMethod);
                    }
                    // Add other payment gateways as needed
                }
            }
            
            // No valid payment method found
            Log::info('No valid payment method for renewal', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
            ]);
            
            return false;
        } catch (Exception $e) {
            Log::error('Error processing subscription renewal', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->id,
            ]);
            
            return false;
        }
    }
    
    /**
     * Renew subscription using wallet balance.
     *
     * @param Subscription $subscription
     * @return bool
     */
    protected function renewWithWallet(Subscription $subscription): bool
    {
        $user = $subscription->user;
        $wallet = StudentWallet::where('user_id', $user->id)->first();
        
        if (!$wallet || $wallet->balance < $subscription->amount_paid) {
            return false;
        }
        
        try {
            // Generate a unique reference
            $reference = 'IQRA_RENEWAL_' . time() . '_' . Str::random(5);
            
            // Create renewal transaction
            $transaction = $this->subscriptionService->renewSubscription($subscription, [
                'payment_method' => 'wallet',
                'payment_reference' => $reference,
            ]);
            
            // Log the payment
            $gatewayLog = PaymentGatewayLog::create([
                'gateway' => 'wallet',
                'reference' => $reference,
                'user_id' => $user->id,
                'subscription_transaction_id' => $transaction->id,
                'status' => 'pending',
                'amount' => $subscription->amount_paid,
                'currency' => $subscription->currency,
                'request_data' => [
                    'subscription_id' => $subscription->id,
                    'transaction_id' => $transaction->id,
                    'renewal' => true,
                ],
            ]);
            
            // Deduct from wallet
            $wallet->deductFunds($subscription->amount_paid);
            
            // Mark the payment as verified
            $gatewayLog->markAsVerified();
            
            // Renew the subscription
            $subscription->renew();
            
            return true;
        } catch (Exception $e) {
            Log::error('Error renewing subscription with wallet', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->id,
            ]);
            
            return false;
        }
    }
    
    /**
     * Renew subscription using Paystack.
     *
     * @param Subscription $subscription
     * @param array $paymentMethod
     * @return bool
     */
    protected function renewWithPaystack(Subscription $subscription, array $paymentMethod): bool
    {
        $user = $subscription->user;
        
        try {
            // Generate a unique reference
            $reference = 'IQRA_RENEWAL_' . time() . '_' . Str::random(5);
            
            // Create renewal transaction
            $transaction = $this->subscriptionService->renewSubscription($subscription, [
                'payment_method' => 'paystack',
                'payment_reference' => $reference,
            ]);
            
            // Log the payment attempt
            $gatewayLog = PaymentGatewayLog::create([
                'gateway' => 'paystack',
                'reference' => $reference,
                'user_id' => $user->id,
                'subscription_transaction_id' => $transaction->id,
                'status' => 'pending',
                'amount' => $subscription->amount_paid,
                'currency' => $subscription->currency === 'naira' ? 'NGN' : 'USD',
                'request_data' => [
                    'subscription_id' => $subscription->id,
                    'transaction_id' => $transaction->id,
                    'renewal' => true,
                    'authorization_code' => $paymentMethod['authorization_code'],
                ],
            ]);
            
            // Make API request to Paystack for recurring charge
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.paystack.secret_key'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://api.paystack.co/transaction/charge_authorization', [
                'authorization_code' => $paymentMethod['authorization_code'],
                'email' => $user->email,
                'amount' => $subscription->amount_paid * 100, // Convert to kobo/cents
                'currency' => $subscription->currency === 'naira' ? 'NGN' : 'USD',
                'reference' => $reference,
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'transaction_id' => $transaction->id,
                    'renewal' => true,
                ],
            ]);
            
            $responseData = $response->json();
            
            // Update gateway log with response
            $gatewayLog->update([
                'response_data' => $responseData,
            ]);
            
            if (!$response->successful() || !isset($responseData['status']) || $responseData['status'] !== true) {
                throw new Exception($responseData['message'] ?? 'Failed to charge card');
            }
            
            // If payment was successful
            if ($responseData['data']['status'] === 'success') {
                // Mark the payment as verified
                $gatewayLog->markAsVerified();
                
                // Renew the subscription
                $subscription->renew();
                
                return true;
            } else {
                // Payment is pending, will be updated by webhook
                return true;
            }
        } catch (Exception $e) {
            Log::error('Error renewing subscription with Paystack', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->id,
            ]);
            
            return false;
        }
    }
} 
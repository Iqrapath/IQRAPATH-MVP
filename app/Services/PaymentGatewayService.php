<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PaymentMethod;
use App\Models\PaymentIntent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class PaymentGatewayService
{
    private StripeClient $stripe;
    private string $paystackSecretKey;
    private string $paystackBaseUrl;
    private ?string $paypalClientId;
    private ?string $paypalClientSecret;
    private string $paypalBaseUrl;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret_key'));
        $this->paystackSecretKey = config('services.paystack.secret_key');
        $this->paystackBaseUrl = config('services.paystack.base_url', 'https://api.paystack.co');
        $this->paypalClientId = config('services.paypal.client_id');
        $this->paypalClientSecret = config('services.paypal.client_secret');
        $paypalMode = config('services.paypal.mode', 'sandbox');
        $this->paypalBaseUrl = $paypalMode === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Tokenize a card using Stripe.
     *
     * @param User $user
     * @param array $cardDetails
     * @return array
     */
    public function tokenizeCardStripe(User $user, array $cardDetails): array
    {
        try {
            // Create or get Stripe customer
            $stripeCustomer = $this->getOrCreateStripeCustomer($user);

            // Create payment method
            $paymentMethod = $this->stripe->paymentMethods->create([
                'type' => 'card',
                'card' => [
                    'number' => $cardDetails['card_number'],
                    'exp_month' => $cardDetails['expiry_month'],
                    'exp_year' => $cardDetails['expiry_year'],
                    'cvc' => $cardDetails['cvc'],
                ],
                'billing_details' => [
                    'name' => $cardDetails['cardholder_name'] ?? $user->name,
                    'email' => $user->email,
                ],
            ]);

            // Attach payment method to customer
            $this->stripe->paymentMethods->attach($paymentMethod->id, [
                'customer' => $stripeCustomer->id,
            ]);

            Log::info('Card tokenized successfully via Stripe', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
                'last4' => $paymentMethod->card->last4,
            ]);

            return [
                'success' => true,
                'gateway' => 'stripe',
                'gateway_token' => $paymentMethod->id,
                'gateway_customer_id' => $stripeCustomer->id,
                'last_four' => $paymentMethod->card->last4,
                'card_brand' => $paymentMethod->card->brand,
                'expiry_month' => $paymentMethod->card->exp_month,
                'expiry_year' => $paymentMethod->card->exp_year,
                'fingerprint' => $paymentMethod->card->fingerprint,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe card tokenization failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Tokenize a card using Paystack.
     *
     * @param User $user
     * @param array $cardDetails
     * @return array
     */
    public function tokenizeCardPaystack(User $user, array $cardDetails): array
    {
        try {
            // Paystack requires a transaction to tokenize a card
            // We'll create a minimal authorization transaction
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->paystackBaseUrl}/transaction/initialize", [
                'email' => $user->email,
                'amount' => 5000, // Minimal amount (50 NGN)
                'currency' => 'NGN',
                'reference' => 'AUTH_' . time() . '_' . $user->id,
                'metadata' => [
                    'user_id' => $user->id,
                    'purpose' => 'card_tokenization',
                ],
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to initialize card tokenization');
            }

            $data = $response->json('data');

            Log::info('Card tokenization initialized via Paystack', [
                'user_id' => $user->id,
                'reference' => $data['reference'],
            ]);

            return [
                'success' => true,
                'gateway' => 'paystack',
                'authorization_url' => $data['authorization_url'],
                'access_code' => $data['access_code'],
                'reference' => $data['reference'],
                'requires_action' => true,
            ];

        } catch (\Exception $e) {
            Log::error('Paystack card tokenization failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify and save Paystack card authorization.
     *
     * @param string $reference
     * @return array
     */
    public function verifyPaystackAuthorization(string $reference): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
            ])->get("{$this->paystackBaseUrl}/transaction/verify/{$reference}");

            if (!$response->successful()) {
                throw new \Exception('Failed to verify transaction');
            }

            $data = $response->json('data');

            if ($data['status'] !== 'success') {
                throw new \Exception('Transaction was not successful');
            }

            $authorization = $data['authorization'];

            return [
                'success' => true,
                'gateway_token' => $authorization['authorization_code'],
                'last_four' => $authorization['last4'],
                'card_brand' => $authorization['card_type'],
                'expiry_month' => $authorization['exp_month'],
                'expiry_year' => $authorization['exp_year'],
                'bank' => $authorization['bank'],
                'reusable' => $authorization['reusable'],
            ];

        } catch (\Exception $e) {
            Log::error('Paystack authorization verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a payment method record.
     *
     * @param User $user
     * @param array $details
     * @return PaymentMethod
     */
    public function createPaymentMethod(User $user, array $details): PaymentMethod
    {
        return DB::transaction(function () use ($user, $details) {
            // If this is the first payment method, make it default
            $isFirst = $user->paymentMethods()->count() === 0;

            // If setting as default, unset other defaults
            if ($details['is_default'] ?? $isFirst) {
                $user->paymentMethods()->update(['is_default' => false]);
            }

            $paymentMethod = PaymentMethod::create([
                'user_id' => $user->id,
                'type' => $details['type'],
                'name' => $details['name'] ?? $this->generatePaymentMethodName($details),
                'gateway' => $details['gateway'] ?? null,
                'gateway_token' => $details['gateway_token'] ?? null,
                'gateway_customer_id' => $details['gateway_customer_id'] ?? null,
                'last_four' => $details['last_four'] ?? null,
                'card_brand' => $details['card_brand'] ?? null,
                'expiry_month' => $details['expiry_month'] ?? null,
                'expiry_year' => $details['expiry_year'] ?? null,
                'bank_name' => $details['bank_name'] ?? null,
                'bank_code' => $details['bank_code'] ?? null,
                'account_name' => $details['account_name'] ?? null,
                'account_number' => $details['account_number'] ?? null,
                'phone_number' => $details['phone_number'] ?? null,
                'provider' => $details['provider'] ?? null,
                'currency' => $details['currency'] ?? 'NGN',
                'is_default' => $details['is_default'] ?? $isFirst,
                'is_active' => true,
                'is_verified' => $details['is_verified'] ?? false,
                'verification_status' => $details['verification_status'] ?? 'pending',
                'verified_at' => $details['verified_at'] ?? null,
                'metadata' => $details['metadata'] ?? null,
            ]);

            Log::info('Payment method created', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
            ]);

            return $paymentMethod;
        });
    }

    /**
     * Update a payment method.
     *
     * @param PaymentMethod $paymentMethod
     * @param array $details
     * @return PaymentMethod
     */
    public function updatePaymentMethod(PaymentMethod $paymentMethod, array $details): PaymentMethod
    {
        return DB::transaction(function () use ($paymentMethod, $details) {
            // If setting as default, unset other defaults
            if (($details['is_default'] ?? false) && !$paymentMethod->is_default) {
                $paymentMethod->user->paymentMethods()
                    ->where('id', '!=', $paymentMethod->id)
                    ->update(['is_default' => false]);
            }

            $paymentMethod->update($details);

            Log::info('Payment method updated', [
                'user_id' => $paymentMethod->user_id,
                'payment_method_id' => $paymentMethod->id,
            ]);

            return $paymentMethod->fresh();
        });
    }

    /**
     * Delete a payment method.
     *
     * @param PaymentMethod $paymentMethod
     * @return bool
     */
    public function deletePaymentMethod(PaymentMethod $paymentMethod): bool
    {
        return DB::transaction(function () use ($paymentMethod) {
            $userId = $paymentMethod->user_id;
            $wasDefault = $paymentMethod->is_default;

            // Soft delete the payment method
            $paymentMethod->delete();

            // If this was the default, set another as default
            if ($wasDefault) {
                $newDefault = PaymentMethod::where('user_id', $userId)
                    ->where('is_active', true)
                    ->first();

                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }

            Log::info('Payment method deleted', [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethod->id,
            ]);

            return true;
        });
    }

    /**
     * Verify a payment method token is still valid.
     *
     * @param PaymentMethod $paymentMethod
     * @return bool
     */
    public function verifyToken(PaymentMethod $paymentMethod): bool
    {
        try {
            if ($paymentMethod->gateway === 'stripe') {
                $pm = $this->stripe->paymentMethods->retrieve($paymentMethod->gateway_token);
                return $pm->id === $paymentMethod->gateway_token;
            }

            if ($paymentMethod->gateway === 'paystack') {
                // Paystack authorization codes don't expire, but we can check if they're still valid
                // by attempting a minimal charge (which we won't complete)
                return true; // Assume valid unless proven otherwise
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Token verification failed', [
                'payment_method_id' => $paymentMethod->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create a payment intent for a transaction.
     *
     * @param User $user
     * @param PaymentMethod $paymentMethod
     * @param float $amount
     * @param string $currency
     * @param array $metadata
     * @return PaymentIntent
     */
    public function createPaymentIntent(
        User $user,
        PaymentMethod $paymentMethod,
        float $amount,
        string $currency = 'NGN',
        array $metadata = []
    ): PaymentIntent {
        $gateway = $paymentMethod->gateway;

        if ($gateway === 'stripe') {
            return $this->createStripePaymentIntent($user, $paymentMethod, $amount, $currency, $metadata);
        }

        if ($gateway === 'paystack') {
            return $this->createPaystackPaymentIntent($user, $paymentMethod, $amount, $currency, $metadata);
        }

        throw new \Exception('Unsupported payment gateway');
    }

    /**
     * Create Stripe payment intent.
     */
    private function createStripePaymentIntent(
        User $user,
        PaymentMethod $paymentMethod,
        float $amount,
        string $currency,
        array $metadata
    ): PaymentIntent {
        try {
            $stripeIntent = $this->stripe->paymentIntents->create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => strtolower($currency),
                'customer' => $paymentMethod->gateway_customer_id,
                'payment_method' => $paymentMethod->gateway_token,
                'confirm' => false,
                'metadata' => $metadata,
            ]);

            return PaymentIntent::create([
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
                'amount' => $amount,
                'currency' => $currency,
                'gateway' => 'stripe',
                'gateway_intent_id' => $stripeIntent->id,
                'gateway_client_secret' => $stripeIntent->client_secret,
                'status' => 'pending',
                'metadata' => $metadata,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create Stripe payment intent', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create Paystack payment intent.
     */
    private function createPaystackPaymentIntent(
        User $user,
        PaymentMethod $paymentMethod,
        float $amount,
        string $currency,
        array $metadata
    ): PaymentIntent {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->paystackBaseUrl}/transaction/initialize", [
                'email' => $user->email,
                'amount' => $amount * 100, // Convert to kobo
                'currency' => $currency,
                'authorization_code' => $paymentMethod->gateway_token,
                'reference' => 'PAY_' . time() . '_' . $user->id,
                'metadata' => $metadata,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to initialize Paystack transaction');
            }

            $data = $response->json('data');

            return PaymentIntent::create([
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
                'amount' => $amount,
                'currency' => $currency,
                'gateway' => 'paystack',
                'gateway_intent_id' => $data['reference'],
                'gateway_client_secret' => $data['access_code'],
                'status' => 'pending',
                'metadata' => array_merge($metadata, [
                    'authorization_url' => $data['authorization_url'],
                ]),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create Paystack payment intent', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get or create Stripe customer for user.
     */
    private function getOrCreateStripeCustomer(User $user): \Stripe\Customer
    {
        // Check if user already has a Stripe customer ID
        $existingMethod = $user->paymentMethods()
            ->where('gateway', 'stripe')
            ->whereNotNull('gateway_customer_id')
            ->first();

        if ($existingMethod) {
            try {
                return $this->stripe->customers->retrieve($existingMethod->gateway_customer_id);
            } catch (\Exception $e) {
                // Customer not found, create new one
            }
        }

        // Create new Stripe customer
        return $this->stripe->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);
    }

    /**
     * Generate a default name for payment method.
     */
    private function generatePaymentMethodName(array $details): string
    {
        $type = $details['type'];

        return match ($type) {
            'card' => ($details['card_brand'] ?? 'Card') . ' ending in ' . ($details['last_four'] ?? '****'),
            'bank_transfer' => ($details['bank_name'] ?? 'Bank') . ' - ' . ($details['account_name'] ?? 'Account'),
            'mobile_money' => ($details['provider'] ?? 'Mobile Money') . ' - ' . ($details['phone_number'] ?? ''),
            default => ucfirst($type) . ' Payment Method',
        };
    }

    /**
     * Create PayPal payment order
     *
     * @param array $paymentData
     * @return array
     */
    public function createPayPalPayment(array $paymentData): array
    {
        try {
            if (!$this->paypalClientId || !$this->paypalClientSecret) {
                return [
                    'success' => false,
                    'message' => 'PayPal is not configured'
                ];
            }

            // Get PayPal access token
            $accessToken = $this->getPayPalAccessToken();
            if (!$accessToken) {
                throw new \Exception('Failed to get PayPal access token');
            }

            // Ensure amount is a float and format it properly
            $amount = (float) ($paymentData['amount'] ?? 0);
            $formattedAmount = number_format($amount, 2, '.', '');
            
            // Create PayPal order
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post("{$this->paypalBaseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => $paymentData['currency'] ?? 'USD',
                        'value' => $formattedAmount,
                    ],
                    'description' => $paymentData['description'] ?? 'Payment',
                ]],
                'application_context' => [
                    'return_url' => $paymentData['return_url'],
                    'cancel_url' => $paymentData['cancel_url'],
                    'brand_name' => config('app.name'),
                    'user_action' => 'PAY_NOW',
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Find approval URL
                $approvalUrl = collect($data['links'] ?? [])
                    ->firstWhere('rel', 'approve')['href'] ?? null;

                Log::info('PayPal payment created', [
                    'order_id' => $data['id'],
                    'amount' => $paymentData['amount'],
                ]);

                return [
                    'success' => true,
                    'payment_id' => $data['id'],
                    'approval_url' => $approvalUrl,
                    'status' => $data['status'],
                ];
            }

            throw new \Exception('Failed to create PayPal order');

        } catch (\Exception $e) {
            Log::error('PayPal payment creation failed', [
                'error' => $e->getMessage(),
                'amount' => $paymentData['amount'] ?? null,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create PayPal payment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Capture PayPal payment
     *
     * @param string $orderId
     * @return array
     */
    public function capturePayPalPayment(string $orderId): array
    {
        try {
            $accessToken = $this->getPayPalAccessToken();
            if (!$accessToken) {
                throw new \Exception('Failed to get PayPal access token');
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post("{$this->paypalBaseUrl}/v2/checkout/orders/{$orderId}/capture");

            if ($response->successful()) {
                $data = $response->json();

                Log::info('PayPal payment captured', [
                    'order_id' => $orderId,
                    'status' => $data['status'],
                ]);

                return [
                    'success' => true,
                    'order_id' => $data['id'],
                    'status' => $data['status'],
                    'payer' => $data['payer'] ?? null,
                    'purchase_units' => $data['purchase_units'] ?? [],
                ];
            }

            throw new \Exception('Failed to capture PayPal payment');

        } catch (\Exception $e) {
            Log::error('PayPal payment capture failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to capture PayPal payment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get PayPal access token
     *
     * @return string|null
     */
    private function getPayPalAccessToken(): ?string
    {
        try {
            $response = Http::withBasicAuth($this->paypalClientId, $this->paypalClientSecret)
                ->asForm()
                ->post("{$this->paypalBaseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('Failed to get PayPal access token', [
                'response' => $response->json(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('PayPal access token error', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Process payment through specified gateway
     *
     * @param array $paymentData
     * @return array
     */
    public function processPayment(array $paymentData): array
    {
        $gateway = $paymentData['gateway'] ?? 'stripe';

        try {
            return match($gateway) {
                'stripe' => $this->processStripePayment($paymentData),
                'paystack' => $this->processPaystackPayment($paymentData),
                'paypal' => $this->createPayPalPayment($paymentData),
                default => [
                    'success' => false,
                    'message' => 'Unsupported payment gateway'
                ],
            };
        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process Stripe payment
     */
    private function processStripePayment(array $paymentData): array
    {
        try {
            $intentParams = [
                'amount' => $paymentData['amount'] * 100,
                'currency' => strtolower($paymentData['currency']),
                'payment_method' => $paymentData['payment_method_id'],
                'confirm' => true,
                'description' => $paymentData['description'] ?? 'Payment',
                'metadata' => $paymentData['metadata'] ?? [],
                'return_url' => $paymentData['return_url'] ?? route('student.dashboard'),
            ];

            // Add automatic payment methods if needed
            if (isset($paymentData['automatic_payment_methods'])) {
                $intentParams['automatic_payment_methods'] = $paymentData['automatic_payment_methods'];
            }

            $paymentIntent = $this->stripe->paymentIntents->create($intentParams);

            return [
                'success' => $paymentIntent->status === 'succeeded',
                'transaction_id' => $paymentIntent->id,
                'reference' => $paymentIntent->id,
                'status' => $paymentIntent->status,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process Paystack payment
     */
    private function processPaystackPayment(array $paymentData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->paystackBaseUrl}/transaction/charge_authorization", [
                'authorization_code' => $paymentData['authorization_code'],
                'email' => $paymentData['customer_email'],
                'amount' => $paymentData['amount'] * 100,
                'currency' => $paymentData['currency'],
                'metadata' => $paymentData['metadata'] ?? [],
            ]);

            if ($response->successful()) {
                $data = $response->json('data');
                
                return [
                    'success' => $data['status'] === 'success',
                    'transaction_id' => $data['id'],
                    'reference' => $data['reference'],
                    'status' => $data['status'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Paystack payment failed',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}

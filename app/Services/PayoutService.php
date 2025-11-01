<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\PayoutRequest;
use App\Services\PayStackTransferService;
use App\Services\StripePayoutService;
use App\Services\PayPalPayoutService;
use Illuminate\Support\Facades\Log;

class PayoutService
{
    public function __construct(
        private PayStackTransferService $payStackService,
        private StripePayoutService $stripeService,
        private PayPalPayoutService $payPalService
    ) {}

    /**
     * Process payout based on payment method
     */
    public function processPayout(PayoutRequest $payoutRequest): array
    {
        try {
            Log::info('Processing payout', [
                'payout_request_id' => $payoutRequest->id,
                'payment_method' => $payoutRequest->payment_method,
                'amount' => $payoutRequest->amount,
                'currency' => $payoutRequest->currency,
            ]);

            // Route to appropriate payment gateway based on payment method
            $result = match($payoutRequest->payment_method) {
                'bank_transfer' => $this->processBankTransfer($payoutRequest),
                'paypal' => $this->processPayPalPayout($payoutRequest),
                'stripe' => $this->processStripePayout($payoutRequest),
                'mobile_money' => $this->processMobileMoneyPayout($payoutRequest),
                default => [
                    'success' => false,
                    'message' => 'Unsupported payment method: ' . $payoutRequest->payment_method
                ]
            };

            if ($result['success']) {
                Log::info('Payout processed successfully', [
                    'payout_request_id' => $payoutRequest->id,
                    'payment_method' => $payoutRequest->payment_method,
                ]);
            } else {
                Log::error('Payout processing failed', [
                    'payout_request_id' => $payoutRequest->id,
                    'payment_method' => $payoutRequest->payment_method,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Payout processing exception', [
                'payout_request_id' => $payoutRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while processing the payout: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process bank transfer via PayStack (for Nigerian banks)
     */
    private function processBankTransfer(PayoutRequest $payoutRequest): array
    {
        try {
            // Check if PayStack is configured
            if (empty(config('services.paystack.secret_key'))) {
                return [
                    'success' => false,
                    'message' => 'PayStack is not configured. Please add PAYSTACK_SECRET_KEY to .env file.'
                ];
            }

            // Check if currency is NGN (PayStack only supports NGN)
            if ($payoutRequest->currency !== 'NGN') {
                return [
                    'success' => false,
                    'message' => 'Bank transfer via PayStack only supports NGN currency'
                ];
            }

            return $this->payStackService->initializeBankTransfer($payoutRequest);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Bank transfer failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process PayPal payout
     */
    private function processPayPalPayout(PayoutRequest $payoutRequest): array
    {
        try {
            return $this->payPalService->initializePayout($payoutRequest);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'PayPal payout failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process Stripe payout
     */
    private function processStripePayout(PayoutRequest $payoutRequest): array
    {
        try {
            return $this->stripeService->initializePayout($payoutRequest);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Stripe payout failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process mobile money payout
     */
    private function processMobileMoneyPayout(PayoutRequest $payoutRequest): array
    {
        try {
            // Mobile money can be processed via PayStack for supported countries
            // For now, we'll use PayStack's transfer API
            return $this->payStackService->initializeBankTransfer($payoutRequest);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Mobile money payout failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify payout status
     */
    public function verifyPayoutStatus(PayoutRequest $payoutRequest): array
    {
        try {
            if (!$payoutRequest->external_reference) {
                return [
                    'success' => false,
                    'message' => 'No external reference found for this payout'
                ];
            }

            return match($payoutRequest->payment_method) {
                'bank_transfer' => $this->payStackService->verifyTransferStatus($payoutRequest->external_transfer_code ?? $payoutRequest->external_reference),
                'paypal' => $this->payPalService->verifyPayoutStatus($payoutRequest->external_reference),
                'stripe' => $this->stripeService->verifyPayoutStatus($payoutRequest->external_reference),
                'mobile_money' => $this->payStackService->verifyTransferStatus($payoutRequest->external_transfer_code ?? $payoutRequest->external_reference),
                default => [
                    'success' => false,
                    'message' => 'Unsupported payment method for verification'
                ]
            };

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get supported payment methods with their configurations
     */
    public function getSupportedPaymentMethods(): array
    {
        return [
            'bank_transfer' => [
                'name' => 'Bank Transfer',
                'gateway' => 'PayStack',
                'supported_currencies' => ['NGN'],
                'processing_time' => '1-2 business days',
                'enabled' => config('services.paystack.enabled', true),
            ],
            'paypal' => [
                'name' => 'PayPal',
                'gateway' => 'PayPal',
                'supported_currencies' => ['USD', 'EUR', 'GBP'],
                'processing_time' => 'Instant',
                'enabled' => config('services.paypal.enabled', true),
            ],
            'stripe' => [
                'name' => 'Stripe',
                'gateway' => 'Stripe',
                'supported_currencies' => ['USD', 'EUR', 'GBP'],
                'processing_time' => '1-2 business days',
                'enabled' => config('services.stripe.enabled', true),
            ],
            'mobile_money' => [
                'name' => 'Mobile Money',
                'gateway' => 'PayStack',
                'supported_currencies' => ['NGN'],
                'processing_time' => 'Instant',
                'enabled' => config('services.paystack.enabled', true),
            ],
        ];
    }

    /**
     * Check if a payment method is available for a currency
     */
    public function isPaymentMethodAvailable(string $method, string $currency): bool
    {
        $methods = $this->getSupportedPaymentMethods();
        
        if (!isset($methods[$method])) {
            return false;
        }

        $methodConfig = $methods[$method];
        
        return $methodConfig['enabled'] && 
               in_array($currency, $methodConfig['supported_currencies']);
    }
}

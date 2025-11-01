<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\PayoutRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripePayoutService
{
    private ?string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret_key');
        $this->baseUrl = 'https://api.stripe.com/v1';
    }

    /**
     * Check if Stripe is configured
     */
    private function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    /**
     * Initialize a Stripe payout
     */
    public function initializePayout(PayoutRequest $payoutRequest): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Stripe is not configured',
                'message' => 'Stripe payout service is not configured. Please add Stripe credentials to .env file.'
            ];
        }

        try {
            $paymentDetails = is_string($payoutRequest->payment_details) 
                ? json_decode($payoutRequest->payment_details, true) 
                : $payoutRequest->payment_details;
            
            // Validate required details
            if (!$this->validatePaymentDetails($paymentDetails)) {
                throw new \Exception('Invalid payment details for Stripe payout');
            }

            // For Stripe, we need to create a payout to a connected account or bank account
            // First, check if we have a Stripe account ID or need to create a bank account
            
            if (isset($paymentDetails['stripe_account_id'])) {
                // Payout to connected Stripe account
                return $this->payoutToConnectedAccount($payoutRequest, $paymentDetails);
            } else {
                // Payout to bank account (requires creating external account first)
                return $this->payoutToBankAccount($payoutRequest, $paymentDetails);
            }

        } catch (\Exception $e) {
            Log::error('Stripe payout initialization failed', [
                'payout_request_id' => $payoutRequest->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to initialize Stripe payout'
            ];
        }
    }

    /**
     * Payout to connected Stripe account
     */
    private function payoutToConnectedAccount(PayoutRequest $payoutRequest, array $paymentDetails): array
    {
        try {
            $amount = $this->convertToSmallestUnit($payoutRequest->amount, $payoutRequest->currency);
            
            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post($this->baseUrl . '/transfers', [
                    'amount' => $amount,
                    'currency' => strtolower($payoutRequest->currency ?? 'usd'),
                    'destination' => $paymentDetails['stripe_account_id'],
                    'description' => 'Teacher withdrawal - ' . $payoutRequest->teacher->name,
                    'metadata' => [
                        'payout_request_id' => $payoutRequest->id,
                        'teacher_id' => $payoutRequest->teacher_id,
                    ]
                ]);

            $responseData = $response->json();

            if ($response->successful()) {
                // Update payout request with Stripe details
                $payoutRequest->update([
                    'external_reference' => $responseData['id'],
                    'status' => 'processing',
                    'processed_at' => now(),
                ]);

                Log::info('Stripe transfer initialized', [
                    'payout_request_id' => $payoutRequest->id,
                    'transfer_id' => $responseData['id'],
                    'amount' => $payoutRequest->amount,
                ]);

                return [
                    'success' => true,
                    'transfer_id' => $responseData['id'],
                    'status' => $responseData['status'],
                    'message' => 'Transfer initialized successfully'
                ];
            } else {
                throw new \Exception($responseData['error']['message'] ?? 'Failed to initialize transfer');
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to process Stripe transfer'
            ];
        }
    }

    /**
     * Payout to bank account
     */
    private function payoutToBankAccount(PayoutRequest $payoutRequest, array $paymentDetails): array
    {
        try {
            // For direct bank payouts, Stripe requires creating a payout
            // This assumes you have funds in your Stripe balance
            $amount = $this->convertToSmallestUnit($payoutRequest->amount, $payoutRequest->currency);
            
            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post($this->baseUrl . '/payouts', [
                    'amount' => $amount,
                    'currency' => strtolower($payoutRequest->currency ?? 'usd'),
                    'description' => 'Teacher withdrawal - ' . $payoutRequest->teacher->name,
                    'metadata' => [
                        'payout_request_id' => $payoutRequest->id,
                        'teacher_id' => $payoutRequest->teacher_id,
                        'account_number' => $paymentDetails['account_number'] ?? '',
                        'routing_number' => $paymentDetails['routing_number'] ?? '',
                    ]
                ]);

            $responseData = $response->json();

            if ($response->successful()) {
                $payoutRequest->update([
                    'external_reference' => $responseData['id'],
                    'status' => 'processing',
                    'processed_at' => now(),
                ]);

                Log::info('Stripe payout initialized', [
                    'payout_request_id' => $payoutRequest->id,
                    'payout_id' => $responseData['id'],
                    'amount' => $payoutRequest->amount,
                ]);

                return [
                    'success' => true,
                    'payout_id' => $responseData['id'],
                    'status' => $responseData['status'],
                    'message' => 'Payout initialized successfully'
                ];
            } else {
                throw new \Exception($responseData['error']['message'] ?? 'Failed to initialize payout');
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to process Stripe payout'
            ];
        }
    }

    /**
     * Verify payout status
     */
    public function verifyPayoutStatus(string $payoutId): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get($this->baseUrl . '/payouts/' . $payoutId);

            $responseData = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $responseData['status'],
                    'amount' => $responseData['amount'],
                    'currency' => $responseData['currency'],
                    'arrival_date' => $responseData['arrival_date'] ?? null,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['error']['message'] ?? 'Failed to verify payout'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Stripe payout verification failed', [
                'payout_id' => $payoutId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle Stripe webhook
     */
    public function handleWebhook(array $webhookData): array
    {
        try {
            $event = $webhookData['type'];
            $object = $webhookData['data']['object'];

            // Find the payout request by external reference
            $payoutRequest = PayoutRequest::where('external_reference', $object['id'])->first();

            if (!$payoutRequest) {
                Log::warning('Stripe webhook: Payout request not found', [
                    'reference' => $object['id'],
                    'event' => $event
                ]);
                return ['success' => false, 'message' => 'Payout request not found'];
            }

            // Update payout request status based on event
            switch ($event) {
                case 'payout.paid':
                case 'transfer.paid':
                    $payoutRequest->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                    
                    Log::info('Stripe payout completed', [
                        'payout_request_id' => $payoutRequest->id,
                        'reference' => $object['id'],
                        'amount' => $payoutRequest->amount
                    ]);
                    break;

                case 'payout.failed':
                case 'transfer.failed':
                    $payoutRequest->update([
                        'status' => 'failed',
                        'failed_at' => now(),
                        'failure_reason' => $object['failure_message'] ?? 'Payout failed',
                    ]);
                    
                    // Return funds to teacher wallet
                    $payoutRequest->teacher->teacherWallet->balance += $payoutRequest->amount;
                    $payoutRequest->teacher->teacherWallet->pending_payouts -= $payoutRequest->amount;
                    $payoutRequest->teacher->teacherWallet->save();
                    
                    Log::error('Stripe payout failed', [
                        'payout_request_id' => $payoutRequest->id,
                        'reference' => $object['id'],
                        'failure_reason' => $object['failure_message'] ?? 'Unknown'
                    ]);
                    break;

                case 'payout.canceled':
                case 'transfer.canceled':
                    $payoutRequest->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                    ]);
                    
                    // Return funds to teacher wallet
                    $payoutRequest->teacher->teacherWallet->balance += $payoutRequest->amount;
                    $payoutRequest->teacher->teacherWallet->pending_payouts -= $payoutRequest->amount;
                    $payoutRequest->teacher->teacherWallet->save();
                    
                    Log::info('Stripe payout cancelled', [
                        'payout_request_id' => $payoutRequest->id,
                        'reference' => $object['id']
                    ]);
                    break;
            }

            return [
                'success' => true,
                'payout_request_id' => $payoutRequest->id,
                'status' => $payoutRequest->status,
                'message' => 'Webhook processed successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'webhook_data' => $webhookData,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate payment details
     */
    private function validatePaymentDetails(array $paymentDetails): bool
    {
        // Check if we have either a Stripe account ID or bank details
        return isset($paymentDetails['stripe_account_id']) ||
               (isset($paymentDetails['account_number']) && isset($paymentDetails['routing_number']));
    }

    /**
     * Convert amount to smallest currency unit (cents, kobo, etc.)
     */
    private function convertToSmallestUnit(float $amount, ?string $currency): int
    {
        // Most currencies use 2 decimal places (cents)
        // Some currencies like JPY use 0 decimal places
        $zeroDecimalCurrencies = ['JPY', 'KRW', 'VND', 'CLP'];
        
        if (in_array(strtoupper($currency ?? 'USD'), $zeroDecimalCurrencies)) {
            return (int) $amount;
        }
        
        return (int) ($amount * 100);
    }
}

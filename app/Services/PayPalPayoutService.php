<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\PayoutRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalPayoutService
{
    private ?string $clientId;
    private ?string $clientSecret;
    private string $baseUrl;
    private string $mode;

    public function __construct()
    {
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
        $this->mode = config('services.paypal.mode', 'sandbox');
        $this->baseUrl = $this->mode === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Check if PayPal is configured
     */
    private function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Initialize a PayPal payout
     */
    public function initializePayout(PayoutRequest $payoutRequest): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'PayPal is not configured',
                'message' => 'PayPal payout service is not configured. Please add PayPal credentials to .env file.'
            ];
        }

        try {
            $paymentDetails = is_string($payoutRequest->payment_details) 
                ? json_decode($payoutRequest->payment_details, true) 
                : $payoutRequest->payment_details;
            
            // Validate required PayPal email
            if (!isset($paymentDetails['paypal_email']) || empty($paymentDetails['paypal_email'])) {
                throw new \Exception('PayPal email is required for payout');
            }

            // Get access token
            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                throw new \Exception('Failed to get PayPal access token');
            }

            // Create payout batch
            $payoutData = [
                'sender_batch_header' => [
                    'sender_batch_id' => 'PAYOUT_' . $payoutRequest->id . '_' . time(),
                    'email_subject' => 'You have received a payment from IQRAQUEST',
                    'email_message' => 'You have received a payment for your teaching services. Thank you for being part of IQRAQUEST!',
                ],
                'items' => [
                    [
                        'recipient_type' => 'EMAIL',
                        'amount' => [
                            'value' => number_format($payoutRequest->amount, 2, '.', ''),
                            'currency' => $payoutRequest->currency ?? 'USD',
                        ],
                        'note' => 'Teacher withdrawal payment',
                        'sender_item_id' => 'ITEM_' . $payoutRequest->id,
                        'receiver' => $paymentDetails['paypal_email'],
                        'notification_language' => 'en-US',
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/v1/payments/payouts', $payoutData);

            $responseData = $response->json();

            if ($response->successful()) {
                // Update payout request with PayPal details
                $payoutRequest->update([
                    'external_reference' => $responseData['batch_header']['payout_batch_id'],
                    'status' => 'processing',
                    'processed_at' => now(),
                ]);

                Log::info('PayPal payout initialized', [
                    'payout_request_id' => $payoutRequest->id,
                    'payout_batch_id' => $responseData['batch_header']['payout_batch_id'],
                    'amount' => $payoutRequest->amount,
                ]);

                return [
                    'success' => true,
                    'payout_batch_id' => $responseData['batch_header']['payout_batch_id'],
                    'batch_status' => $responseData['batch_header']['batch_status'],
                    'message' => 'Payout initialized successfully'
                ];
            } else {
                $errorMessage = $responseData['message'] ?? 'Failed to initialize payout';
                if (isset($responseData['details'])) {
                    $errorMessage .= ': ' . json_encode($responseData['details']);
                }
                throw new \Exception($errorMessage);
            }

        } catch (\Exception $e) {
            Log::error('PayPal payout initialization failed', [
                'payout_request_id' => $payoutRequest->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to initialize PayPal payout'
            ];
        }
    }

    /**
     * Get PayPal access token
     */
    private function getAccessToken(): ?string
    {
        try {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post($this->baseUrl . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials'
                ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['access_token'])) {
                return $responseData['access_token'];
            }

            Log::error('Failed to get PayPal access token', [
                'response' => $responseData
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('PayPal access token error', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Verify payout status
     */
    public function verifyPayoutStatus(string $payoutBatchId): array
    {
        try {
            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                throw new \Exception('Failed to get PayPal access token');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/v1/payments/payouts/' . $payoutBatchId);

            $responseData = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'batch_status' => $responseData['batch_header']['batch_status'],
                    'amount' => $responseData['batch_header']['amount']['value'] ?? null,
                    'currency' => $responseData['batch_header']['amount']['currency'] ?? null,
                    'items' => $responseData['items'] ?? [],
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Failed to verify payout'
                ];
            }

        } catch (\Exception $e) {
            Log::error('PayPal payout verification failed', [
                'payout_batch_id' => $payoutBatchId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle PayPal webhook
     */
    public function handleWebhook(array $webhookData): array
    {
        try {
            $eventType = $webhookData['event_type'];
            $resource = $webhookData['resource'];

            // Extract payout batch ID
            $payoutBatchId = $resource['payout_batch_id'] ?? $resource['batch_header']['payout_batch_id'] ?? null;

            if (!$payoutBatchId) {
                Log::warning('PayPal webhook: No payout batch ID found', [
                    'event_type' => $eventType
                ]);
                return ['success' => false, 'message' => 'No payout batch ID found'];
            }

            // Find the payout request by external reference
            $payoutRequest = PayoutRequest::where('external_reference', $payoutBatchId)->first();

            if (!$payoutRequest) {
                Log::warning('PayPal webhook: Payout request not found', [
                    'payout_batch_id' => $payoutBatchId,
                    'event_type' => $eventType
                ]);
                return ['success' => false, 'message' => 'Payout request not found'];
            }

            // Update payout request status based on event type
            switch ($eventType) {
                case 'PAYMENT.PAYOUTS-ITEM.SUCCEEDED':
                case 'PAYMENT.PAYOUTSBATCH.SUCCESS':
                    $payoutRequest->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                    
                    Log::info('PayPal payout completed', [
                        'payout_request_id' => $payoutRequest->id,
                        'payout_batch_id' => $payoutBatchId,
                        'amount' => $payoutRequest->amount
                    ]);
                    break;

                case 'PAYMENT.PAYOUTS-ITEM.FAILED':
                case 'PAYMENT.PAYOUTSBATCH.DENIED':
                    $failureReason = $resource['payout_item']['errors'][0]['message'] ?? 
                                   $resource['errors'][0]['message'] ?? 
                                   'Payout failed';
                    
                    $payoutRequest->update([
                        'status' => 'failed',
                        'failed_at' => now(),
                        'failure_reason' => $failureReason,
                    ]);
                    
                    // Return funds to teacher wallet
                    $payoutRequest->teacher->teacherWallet->balance += $payoutRequest->amount;
                    $payoutRequest->teacher->teacherWallet->pending_payouts -= $payoutRequest->amount;
                    $payoutRequest->teacher->teacherWallet->save();
                    
                    Log::error('PayPal payout failed', [
                        'payout_request_id' => $payoutRequest->id,
                        'payout_batch_id' => $payoutBatchId,
                        'failure_reason' => $failureReason
                    ]);
                    break;

                case 'PAYMENT.PAYOUTS-ITEM.CANCELED':
                    $payoutRequest->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                    ]);
                    
                    // Return funds to teacher wallet
                    $payoutRequest->teacher->teacherWallet->balance += $payoutRequest->amount;
                    $payoutRequest->teacher->teacherWallet->pending_payouts -= $payoutRequest->amount;
                    $payoutRequest->teacher->teacherWallet->save();
                    
                    Log::info('PayPal payout cancelled', [
                        'payout_request_id' => $payoutRequest->id,
                        'payout_batch_id' => $payoutBatchId
                    ]);
                    break;

                case 'PAYMENT.PAYOUTS-ITEM.UNCLAIMED':
                    $payoutRequest->update([
                        'status' => 'unclaimed',
                        'notes' => 'Payment unclaimed by recipient',
                    ]);
                    
                    Log::warning('PayPal payout unclaimed', [
                        'payout_request_id' => $payoutRequest->id,
                        'payout_batch_id' => $payoutBatchId
                    ]);
                    break;

                case 'PAYMENT.PAYOUTS-ITEM.RETURNED':
                case 'PAYMENT.PAYOUTS-ITEM.REFUNDED':
                    $payoutRequest->update([
                        'status' => 'returned',
                        'returned_at' => now(),
                    ]);
                    
                    // Return funds to teacher wallet
                    $payoutRequest->teacher->teacherWallet->balance += $payoutRequest->amount;
                    $payoutRequest->teacher->teacherWallet->pending_payouts -= $payoutRequest->amount;
                    $payoutRequest->teacher->teacherWallet->save();
                    
                    Log::info('PayPal payout returned', [
                        'payout_request_id' => $payoutRequest->id,
                        'payout_batch_id' => $payoutBatchId
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
            Log::error('PayPal webhook processing failed', [
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
     * Verify webhook signature
     */
    public function verifyWebhookSignature(array $headers, string $body): bool
    {
        try {
            $webhookId = config('services.paypal.webhook_id');
            
            if (!$webhookId) {
                Log::warning('PayPal webhook ID not configured');
                return true; // Skip verification if not configured
            }

            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                return false;
            }

            $verificationData = [
                'auth_algo' => $headers['paypal-auth-algo'] ?? '',
                'cert_url' => $headers['paypal-cert-url'] ?? '',
                'transmission_id' => $headers['paypal-transmission-id'] ?? '',
                'transmission_sig' => $headers['paypal-transmission-sig'] ?? '',
                'transmission_time' => $headers['paypal-transmission-time'] ?? '',
                'webhook_id' => $webhookId,
                'webhook_event' => json_decode($body, true),
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/v1/notifications/verify-webhook-signature', $verificationData);

            $responseData = $response->json();

            return $response->successful() && 
                   isset($responseData['verification_status']) && 
                   $responseData['verification_status'] === 'SUCCESS';

        } catch (\Exception $e) {
            Log::error('PayPal webhook verification failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

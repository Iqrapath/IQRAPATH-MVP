<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\PayoutRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PayStackTransferService
{
    private string $secretKey;
    private string $baseUrl;
    private string $merchantEmail;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
        $this->baseUrl = config('services.paystack.base_url');
        $this->merchantEmail = config('services.paystack.merchant_email');
    }

    /**
     * Initialize a PayStack transfer to a bank account
     */
    public function initializeBankTransfer(PayoutRequest $payoutRequest): array
    {
        try {
            $paymentDetails = json_decode($payoutRequest->payment_details, true);
            
            // Validate required bank details
            if (!$this->validateBankDetails($paymentDetails)) {
                throw new \Exception('Invalid bank details for transfer');
            }

            // Create transfer recipient first
            $recipient = $this->createTransferRecipient($paymentDetails);
            
            if (!$recipient['success']) {
                throw new \Exception($recipient['message']);
            }

            // Initialize transfer
            $transferData = [
                'source' => 'balance',
                'amount' => $payoutRequest->amount * 100, // Convert to kobo
                'recipient' => $recipient['data']['recipient_code'],
                'reason' => 'Teacher withdrawal - ' . $payoutRequest->teacher->name,
                'reference' => 'WITHDRAWAL_' . $payoutRequest->id . '_' . time(),
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transfer', $transferData);

            $responseData = $response->json();

            if ($response->successful() && $responseData['status']) {
                // Update payout request with PayStack details
                $payoutRequest->update([
                    'external_reference' => $responseData['data']['reference'],
                    'external_transfer_code' => $responseData['data']['transfer_code'],
                    'status' => 'processing',
                    'processed_at' => now(),
                ]);

                Log::info('PayStack transfer initialized', [
                    'payout_request_id' => $payoutRequest->id,
                    'transfer_code' => $responseData['data']['transfer_code'],
                    'amount' => $payoutRequest->amount,
                ]);

                return [
                    'success' => true,
                    'transfer_code' => $responseData['data']['transfer_code'],
                    'reference' => $responseData['data']['reference'],
                    'status' => $responseData['data']['status'],
                    'message' => 'Transfer initialized successfully'
                ];
            } else {
                throw new \Exception($responseData['message'] ?? 'Failed to initialize transfer');
            }

        } catch (\Exception $e) {
            Log::error('PayStack transfer initialization failed', [
                'payout_request_id' => $payoutRequest->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to initialize PayStack transfer'
            ];
        }
    }

    /**
     * Create a transfer recipient
     */
    private function createTransferRecipient(array $bankDetails): array
    {
        try {
            $recipientData = [
                'type' => 'nuban',
                'name' => $bankDetails['account_name'],
                'account_number' => $bankDetails['account_number'],
                'bank_code' => $this->getBankCode($bankDetails['bank_name']),
                'currency' => 'NGN',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transferrecipient', $recipientData);

            $responseData = $response->json();

            if ($response->successful() && $responseData['status']) {
                return [
                    'success' => true,
                    'data' => $responseData['data']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Failed to create transfer recipient'
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get PayStack bank code from bank name
     */
    private function getBankCode(string $bankName): string
    {
        $bankCodes = [
            'Access Bank' => '044',
            'Citibank' => '023',
            'Diamond Bank' => '063',
            'Ecobank' => '050',
            'Fidelity Bank' => '070',
            'First Bank' => '011',
            'First City Monument Bank' => '214',
            'Guaranty Trust Bank' => '058',
            'Heritage Bank' => '030',
            'Keystone Bank' => '082',
            'Kuda Bank' => '50211',
            'Opay' => '100022',
            'PalmPay' => '999991',
            'Polaris Bank' => '076',
            'Providus Bank' => '101',
            'Stanbic IBTC Bank' => '221',
            'Standard Chartered Bank' => '068',
            'Sterling Bank' => '232',
            'Suntrust Bank' => '100',
            'Union Bank' => '032',
            'United Bank For Africa' => '033',
            'Unity Bank' => '215',
            'VFD' => '566',
            'Wema Bank' => '035',
            'Zenith Bank' => '057',
        ];

        return $bankCodes[$bankName] ?? '011'; // Default to First Bank
    }

    /**
     * Validate bank details
     */
    private function validateBankDetails(array $bankDetails): bool
    {
        return isset($bankDetails['bank_name']) &&
               isset($bankDetails['account_number']) &&
               isset($bankDetails['account_name']) &&
               !empty($bankDetails['bank_name']) &&
               !empty($bankDetails['account_number']) &&
               !empty($bankDetails['account_name']);
    }

    /**
     * Verify transfer status
     */
    public function verifyTransferStatus(string $transferCode): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/transfer/' . $transferCode);

            $responseData = $response->json();

            if ($response->successful() && $responseData['status']) {
                return [
                    'success' => true,
                    'status' => $responseData['data']['status'],
                    'amount' => $responseData['data']['amount'] / 100, // Convert from kobo
                    'recipient' => $responseData['data']['recipient'],
                    'transfer_code' => $responseData['data']['transfer_code'],
                    'reference' => $responseData['data']['reference'],
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Failed to verify transfer'
                ];
            }

        } catch (\Exception $e) {
            Log::error('PayStack transfer verification failed', [
                'transfer_code' => $transferCode,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle PayStack transfer webhook
     */
    public function handleTransferWebhook(array $webhookData): array
    {
        try {
            $event = $webhookData['event'];
            $transferData = $webhookData['data'];

            // Find the payout request by transfer code
            $payoutRequest = PayoutRequest::where('external_transfer_code', $transferData['transfer_code'])->first();

            if (!$payoutRequest) {
                Log::warning('PayStack webhook: Payout request not found', [
                    'transfer_code' => $transferData['transfer_code'],
                    'event' => $event
                ]);
                return ['success' => false, 'message' => 'Payout request not found'];
            }

            // Update payout request status based on event
            switch ($event) {
                case 'transfer.success':
                    $payoutRequest->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                    
                    // Update teacher wallet
                    $payoutRequest->teacher->teacherWallet->subtractPendingPayout($payoutRequest->amount);
                    $payoutRequest->teacher->teacherWallet->increment('total_withdrawn', $payoutRequest->amount);
                    
                    Log::info('PayStack transfer completed', [
                        'payout_request_id' => $payoutRequest->id,
                        'transfer_code' => $transferData['transfer_code'],
                        'amount' => $payoutRequest->amount
                    ]);
                    break;

                case 'transfer.failed':
                    $payoutRequest->update([
                        'status' => 'failed',
                        'failed_at' => now(),
                        'failure_reason' => $transferData['failure_reason'] ?? 'Transfer failed',
                    ]);
                    
                    // Return funds to teacher wallet
                    $payoutRequest->teacher->teacherWallet->subtractPendingPayout($payoutRequest->amount);
                    
                    Log::error('PayStack transfer failed', [
                        'payout_request_id' => $payoutRequest->id,
                        'transfer_code' => $transferData['transfer_code'],
                        'failure_reason' => $transferData['failure_reason'] ?? 'Unknown'
                    ]);
                    break;

                case 'transfer.reversed':
                    $payoutRequest->update([
                        'status' => 'reversed',
                        'reversed_at' => now(),
                    ]);
                    
                    // Return funds to teacher wallet
                    $payoutRequest->teacher->teacherWallet->subtractPendingPayout($payoutRequest->amount);
                    
                    Log::info('PayStack transfer reversed', [
                        'payout_request_id' => $payoutRequest->id,
                        'transfer_code' => $transferData['transfer_code']
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
            Log::error('PayStack webhook processing failed', [
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
     * Get supported banks
     */
    public function getSupportedBanks(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/bank');

            $responseData = $response->json();

            if ($response->successful() && $responseData['status']) {
                return [
                    'success' => true,
                    'banks' => $responseData['data']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Failed to fetch banks'
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify account number
     */
    public function verifyAccountNumber(string $accountNumber, string $bankCode): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/bank/resolve', [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode
            ]);

            $responseData = $response->json();

            if ($response->successful() && $responseData['status']) {
                return [
                    'success' => true,
                    'account_name' => $responseData['data']['account_name'],
                    'account_number' => $responseData['data']['account_number'],
                    'bank_code' => $responseData['data']['bank_code']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Account verification failed'
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

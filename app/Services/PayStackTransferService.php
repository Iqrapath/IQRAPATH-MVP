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
            $paymentDetails = is_string($payoutRequest->payment_details) 
                ? json_decode($payoutRequest->payment_details, true) 
                : $payoutRequest->payment_details;
            
            // Validate required bank details
            if (!$this->validateBankDetails($paymentDetails)) {
                throw new \Exception('Invalid bank details for transfer');
            }

            Log::info('Starting PayStack transfer', [
                'payout_request_id' => $payoutRequest->id,
                'amount' => $payoutRequest->amount,
                'bank_name' => $paymentDetails['bank_name'],
                'account_number' => $paymentDetails['account_number'],
            ]);

            // Create transfer recipient first
            $recipient = $this->createTransferRecipient($paymentDetails);
            
            if (!$recipient['success']) {
                throw new \Exception($recipient['message']);
            }

            Log::info('PayStack recipient created', [
                'recipient_code' => $recipient['data']['recipient_code'],
                'account_name' => $recipient['data']['details']['account_name'] ?? 'N/A',
            ]);

            // Initialize transfer
            $transferData = [
                'source' => 'balance',
                'amount' => (int)($payoutRequest->amount * 100), // Convert to kobo (integer)
                'recipient' => $recipient['data']['recipient_code'],
                'reason' => 'Teacher withdrawal - ' . $payoutRequest->teacher->name,
                'reference' => 'WITHDRAWAL_' . $payoutRequest->id . '_' . time(),
            ];

            Log::info('Initiating PayStack transfer', [
                'transfer_data' => $transferData,
                'api_url' => $this->baseUrl . '/transfer',
            ]);

            $response = Http::timeout(30)
                ->retry(3, 100)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'Content-Type' => 'application/json',
                ])->post($this->baseUrl . '/transfer', $transferData);

            $responseData = $response->json();

            Log::info('PayStack transfer response', [
                'status_code' => $response->status(),
                'response_data' => $responseData,
            ]);

            if ($response->successful() && $responseData['status']) {
                // In TEST mode, transfers need to be finalized with OTP
                // For now, we'll mark as processing and finalize automatically
                if (config('app.env') !== 'production') {
                    // Auto-finalize test transfers
                    $finalizeResult = $this->finalizeTransfer($responseData['data']['transfer_code']);
                    
                    if ($finalizeResult['success']) {
                        Log::info('Test transfer auto-finalized', [
                            'transfer_code' => $responseData['data']['transfer_code'],
                        ]);
                    }
                }

                // Update payout request with PayStack details
                $payoutRequest->update([
                    'external_reference' => $responseData['data']['reference'],
                    'external_transfer_code' => $responseData['data']['transfer_code'],
                    'status' => 'processing',
                    'processed_at' => now(),
                ]);

                // Log to payment_gateway_logs table
                \App\Models\PaymentGatewayLog::create([
                    'user_id' => $payoutRequest->user_id,
                    'gateway' => 'paystack',
                    'reference' => $responseData['data']['reference'],
                    'transaction_reference' => $payoutRequest->request_uuid,
                    'transaction_id' => $responseData['data']['transfer_code'],
                    'amount' => $payoutRequest->amount,
                    'currency' => 'NGN',
                    'status' => 'success',
                    'request_data' => $transferData,
                    'response_data' => $responseData,
                ]);

                Log::info('PayStack transfer initialized successfully', [
                    'payout_request_id' => $payoutRequest->id,
                    'transfer_code' => $responseData['data']['transfer_code'],
                    'amount' => $payoutRequest->amount,
                    'status' => $responseData['data']['status'],
                ]);

                return [
                    'success' => true,
                    'transfer_code' => $responseData['data']['transfer_code'],
                    'reference' => $responseData['data']['reference'],
                    'status' => $responseData['data']['status'],
                    'message' => 'Transfer initialized successfully'
                ];
            } else {
                // Log failed attempt
                \App\Models\PaymentGatewayLog::create([
                    'user_id' => $payoutRequest->user_id,
                    'gateway' => 'paystack',
                    'reference' => 'FAILED-' . $payoutRequest->request_uuid . '-' . time(),
                    'transaction_reference' => $payoutRequest->request_uuid,
                    'amount' => $payoutRequest->amount,
                    'currency' => 'NGN',
                    'status' => 'failed',
                    'request_data' => $transferData,
                    'response_data' => $responseData,
                ]);
                
                throw new \Exception($responseData['message'] ?? 'Failed to initialize transfer');
            }

        } catch (\Exception $e) {
            Log::error('PayStack transfer initialization failed', [
                'payout_request_id' => $payoutRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Check if error is due to account restrictions
            $isAccountRestriction = $this->isAccountRestrictionError($e->getMessage());
            
            if ($isAccountRestriction) {
                // Mark for manual processing
                $payoutRequest->update([
                    'status' => 'requires_manual_processing',
                    'notes' => 'PayStack transfers disabled. Requires manual bank transfer. Error: ' . $e->getMessage(),
                ]);
                
                Log::warning('Payout marked for manual processing', [
                    'payout_request_id' => $payoutRequest->id,
                    'reason' => 'PayStack account restriction',
                ]);

                // Notify all admins
                $this->notifyAdminsOfRestriction($payoutRequest, $e->getMessage());
            }

            // Log failed attempt to payment_gateway_logs
            \App\Models\PaymentGatewayLog::create([
                'user_id' => $payoutRequest->user_id,
                'gateway' => 'paystack',
                'reference' => 'ERROR-' . $payoutRequest->request_uuid . '-' . time(),
                'transaction_reference' => $payoutRequest->request_uuid,
                'amount' => $payoutRequest->amount,
                'currency' => 'NGN',
                'status' => 'failed',
                'request_data' => [
                    'payout_request_id' => $payoutRequest->id,
                    'amount' => $payoutRequest->amount,
                    'payment_details' => $payoutRequest->payment_details,
                    'error' => $e->getMessage(),
                    'requires_manual_processing' => $isAccountRestriction,
                ],
                'response_data' => null,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to initialize PayStack transfer',
                'requires_manual_processing' => $isAccountRestriction,
            ];
        }
    }

    /**
     * Check if error is due to account restrictions
     */
    private function isAccountRestrictionError(string $errorMessage): bool
    {
        $restrictionKeywords = [
            'cannot initiate third party payouts',
            'transfers not enabled',
            'account not verified',
            'business verification required',
            'settlement account required',
            'insufficient permissions',
        ];

        $errorLower = strtolower($errorMessage);
        
        foreach ($restrictionKeywords as $keyword) {
            if (stripos($errorLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Notify all admins about PayStack account restriction
     */
    private function notifyAdminsOfRestriction(PayoutRequest $payoutRequest, string $errorMessage): void
    {
        try {
            // Get all admin and super-admin users
            $admins = \App\Models\User::whereIn('role', ['admin', 'super-admin'])->get();
            
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\PayStackAccountRestrictionNotification(
                    $payoutRequest,
                    $errorMessage
                ));
            }
            
            Log::info('Admin notifications sent for PayStack restriction', [
                'payout_request_id' => $payoutRequest->id,
                'admins_notified' => $admins->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send admin notifications', [
                'payout_request_id' => $payoutRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Finalize a transfer (for test mode)
     * In test mode, PayStack requires OTP finalization
     */
    private function finalizeTransfer(string $transferCode): array
    {
        try {
            // Use test OTP for finalization
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/transfer/finalize_transfer', [
                'transfer_code' => $transferCode,
                'otp' => '123456', // Test OTP for test mode
            ]);

            $responseData = $response->json();

            if ($response->successful() && $responseData['status']) {
                return [
                    'success' => true,
                    'message' => 'Transfer finalized successfully'
                ];
            } else {
                Log::warning('Transfer finalization failed', [
                    'transfer_code' => $transferCode,
                    'response' => $responseData,
                ]);
                
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Finalization failed'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Transfer finalization error', [
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
     * Create a transfer recipient
     */
    private function createTransferRecipient(array $bankDetails): array
    {
        try {
            $bankCode = $this->getBankCode($bankDetails['bank_name']);
            
            $recipientData = [
                'type' => 'nuban',
                'name' => $bankDetails['account_name'],
                'account_number' => $bankDetails['account_number'],
                'bank_code' => $bankCode,
                'currency' => 'NGN',
            ];

            Log::info('Creating PayStack transfer recipient', [
                'recipient_data' => $recipientData,
                'api_url' => $this->baseUrl . '/transferrecipient',
            ]);

            $response = Http::timeout(30)
                ->retry(3, 100)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->secretKey,
                    'Content-Type' => 'application/json',
                ])->post($this->baseUrl . '/transferrecipient', $recipientData);

            $responseData = $response->json();

            Log::info('PayStack recipient creation response', [
                'status_code' => $response->status(),
                'response_data' => $responseData,
            ]);

            if ($response->successful() && $responseData['status']) {
                return [
                    'success' => true,
                    'data' => $responseData['data']
                ];
            } else {
                Log::error('PayStack recipient creation failed', [
                    'recipient_data' => $recipientData,
                    'response' => $responseData,
                ]);
                
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Failed to create transfer recipient'
                ];
            }

        } catch (\Exception $e) {
            Log::error('PayStack recipient creation exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get PayStack bank code from bank name
     * Fetches from PayStack API and caches for 24 hours
     */
    private function getBankCode(string $bankName): string
    {
        // Try to get from cache first
        $banks = \Cache::remember('paystack_banks', 86400, function () {
            $result = $this->getSupportedBanks();
            return $result['success'] ? $result['banks'] : [];
        });

        // Search for bank by name (case-insensitive, partial match)
        foreach ($banks as $bank) {
            if (stripos($bank['name'], $bankName) !== false || stripos($bankName, $bank['name']) !== false) {
                Log::info('Bank code found', [
                    'search_name' => $bankName,
                    'found_name' => $bank['name'],
                    'code' => $bank['code'],
                ]);
                return $bank['code'];
            }
        }

        // Fallback to static list if API fails
        $staticBankCodes = [
            'Access Bank' => '044',
            'Citibank' => '023',
            'Diamond Bank' => '063',
            'Ecobank' => '050',
            'Fidelity Bank' => '070',
            'First Bank' => '011',
            'First City Monument Bank' => '214',
            'FCMB' => '214',
            'Guaranty Trust Bank' => '058',
            'GTBank' => '058',
            'GT Bank' => '058',
            'Heritage Bank' => '030',
            'Keystone Bank' => '082',
            'Kuda Bank' => '50211',
            'Kuda' => '50211',
            'Opay' => '999992',
            'OPay' => '999992',
            'PalmPay' => '999991',
            'Polaris Bank' => '076',
            'Providus Bank' => '101',
            'Stanbic IBTC Bank' => '221',
            'Stanbic IBTC' => '221',
            'Standard Chartered Bank' => '068',
            'Sterling Bank' => '232',
            'Suntrust Bank' => '100',
            'Union Bank' => '032',
            'United Bank For Africa' => '033',
            'UBA' => '033',
            'Unity Bank' => '215',
            'VFD' => '566',
            'Wema Bank' => '035',
            'Zenith Bank' => '057',
        ];

        // Try exact match
        if (isset($staticBankCodes[$bankName])) {
            Log::info('Bank code from static list (exact)', [
                'bank_name' => $bankName,
                'code' => $staticBankCodes[$bankName],
            ]);
            return $staticBankCodes[$bankName];
        }

        // Try partial match
        foreach ($staticBankCodes as $name => $code) {
            if (stripos($name, $bankName) !== false || stripos($bankName, $name) !== false) {
                Log::info('Bank code from static list (partial)', [
                    'search_name' => $bankName,
                    'found_name' => $name,
                    'code' => $code,
                ]);
                return $code;
            }
        }

        Log::warning('Bank code not found, using default', [
            'bank_name' => $bankName,
            'default_code' => '011',
        ]);

        return '011'; // Default to First Bank
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

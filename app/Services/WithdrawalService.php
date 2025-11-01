<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\FinancialSetting;
use App\Models\Transaction;
use App\Models\PayoutRequest;
use App\Services\CurrencyService;
use App\Services\PayStackTransferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalService
{
    public function __construct(
        private CurrencyService $currencyService
    ) {}

    /**
     * Calculate withdrawal fee for a specific method and amount.
     */
    public function calculateWithdrawalFee(string $method, float $amount): float
    {
        $feeConfig = FinancialSetting::getWithdrawalFee($method);
        
        if ($feeConfig['type'] === 'percentage') {
            return ($amount * $feeConfig['amount']) / 100;
        }
        
        return $feeConfig['amount'];
    }

    /**
     * Get net withdrawal amount after fees.
     */
    public function getNetWithdrawalAmount(string $method, float $amount): float
    {
        $fee = $this->calculateWithdrawalFee($method, $amount);
        return $amount - $fee;
    }

    /**
     * Check if withdrawal amount is within limits.
     */
    public function validateWithdrawalLimits(User $teacher, float $amount): array
    {
        $errors = [];
        
        // Check minimum withdrawal amount
        $minimumAmount = FinancialSetting::getMinimumWithdrawalAmount();
        if ($amount < $minimumAmount) {
            $errors[] = "Minimum withdrawal amount is ₦" . number_format($minimumAmount);
        }
        
        // Check daily limit
        $dailyLimit = FinancialSetting::getDailyWithdrawalLimit();
        $todayWithdrawals = $this->getTodayWithdrawals($teacher);
        if (($todayWithdrawals + $amount) > $dailyLimit) {
            $errors[] = "Daily withdrawal limit exceeded. Remaining: ₦" . number_format($dailyLimit - $todayWithdrawals);
        }
        
        // Check monthly limit
        $monthlyLimit = FinancialSetting::getMonthlyWithdrawalLimit();
        $monthlyWithdrawals = $this->getMonthlyWithdrawals($teacher);
        if (($monthlyWithdrawals + $amount) > $monthlyLimit) {
            $errors[] = "Monthly withdrawal limit exceeded. Remaining: ₦" . number_format($monthlyLimit - $monthlyWithdrawals);
        }
        
        return $errors;
    }

    /**
     * Get today's withdrawal total for a teacher.
     */
    public function getTodayWithdrawals(User $teacher): float
    {
        return Transaction::where('teacher_id', $teacher->id)
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');
    }

    /**
     * Get monthly withdrawal total for a teacher.
     */
    public function getMonthlyWithdrawals(User $teacher): float
    {
        return Transaction::where('teacher_id', $teacher->id)
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
    }

    /**
     * Create a withdrawal request with multi-currency support.
     */
    public function createWithdrawalRequest(User $teacher, array $data): PayoutRequest
    {
        $amount = (float) $data['amount'];
        $method = $data['withdrawal_method'];
        $currency = $data['currency'] ?? 'NGN';
        
        // Validate limits
        $limitErrors = $this->validateWithdrawalLimits($teacher, $amount);
        if (!empty($limitErrors)) {
            throw new \Exception(implode(', ', $limitErrors));
        }
        
        // Calculate fee
        $fee = $this->calculateWithdrawalFee($method, $amount);
        $netAmount = $amount - $fee;
        
        // Convert to NGN if needed for storage
        $amountNGN = $currency === 'NGN' ? $amount : 
            $this->currencyService->convertAmount($amount, $currency, 'NGN');
        $feeNGN = $currency === 'NGN' ? $fee : 
            $this->currencyService->convertAmount($fee, $currency, 'NGN');
        $netAmountNGN = $amountNGN - $feeNGN;
        
        // Get exchange rate used
        $exchangeRate = $this->currencyService->getExchangeRate($currency, 'NGN');
        
        return DB::transaction(function () use ($teacher, $data, $amountNGN, $feeNGN, $netAmountNGN, $method, $currency, $exchangeRate) {
            // Create payout request
            $payoutRequest = PayoutRequest::create([
                'teacher_id' => $teacher->id,
                'amount' => $netAmountNGN, // Store net amount in NGN
                'currency' => $currency,
                'exchange_rate_used' => $exchangeRate,
                'payment_method' => $method,
                'payment_details' => $this->formatPaymentDetails($method, $data),
                'fee_amount' => $feeNGN,
                'fee_currency' => $currency,
                'status' => 'pending',
                'request_date' => now()->format('Y-m-d'),
                'notes' => $data['notes'] ?? null,
            ]);
            
            // Add pending payout to teacher wallet
            $teacher->teacherWallet->addPendingPayout($netAmountNGN);
            
            return $payoutRequest;
        });
    }

    /**
     * Format payment details based on withdrawal method.
     */
    private function formatPaymentDetails(string $method, array $data): array
    {
        switch ($method) {
            case 'bank_transfer':
                return [
                    'bank_name' => $data['bank_name'] ?? '',
                    'account_number' => $data['account_number'] ?? '',
                    'account_name' => $data['account_name'] ?? '',
                ];
                
            case 'mobile_money':
                return [
                    'provider' => $data['mobile_provider'] ?? '',
                    'mobile_number' => $data['mobile_number'] ?? '',
                ];
                
            case 'paypal':
                return [
                    'email' => $data['paypal_email'] ?? '',
                ];
                
            default:
                return [];
        }
    }

    /**
     * Get withdrawal calculator data.
     */
    public function getWithdrawalCalculatorData(string $method, float $amount, string $currency = 'NGN'): array
    {
        $fee = $this->calculateWithdrawalFee($method, $amount);
        $netAmount = $amount - $fee;
        $processingTime = FinancialSetting::getProcessingTime($method);
        
        // Convert to NGN for display
        $amountNGN = $currency === 'NGN' ? $amount : 
            $this->currencyService->convertAmount($amount, $currency, 'NGN');
        $feeNGN = $currency === 'NGN' ? $fee : 
            $this->currencyService->convertAmount($fee, $currency, 'NGN');
        $netAmountNGN = $amountNGN - $feeNGN;
        
        return [
            'method' => $method,
            'amount' => $amount,
            'currency' => $currency,
            'fee' => $fee,
            'net_amount' => $netAmount,
            'amount_ngn' => $amountNGN,
            'fee_ngn' => $feeNGN,
            'net_amount_ngn' => $netAmountNGN,
            'processing_time' => $processingTime,
            'exchange_rate' => $this->currencyService->getExchangeRate($currency, 'NGN'),
        ];
    }

    /**
     * Get available withdrawal methods with their details.
     */
    public function getAvailableWithdrawalMethods(): array
    {
        $methods = [
            'bank_transfer' => [
                'name' => 'Bank Transfer',
                'icon' => 'bank',
                'fee_type' => FinancialSetting::get('bank_transfer_fee_type', 'flat'),
                'fee_amount' => FinancialSetting::get('bank_transfer_fee_amount', 100),
                'processing_time' => FinancialSetting::get('bank_transfer_processing_time', '1-3 business days'),
                'min_amount' => 10000,
                'max_amount' => 1000000,
            ],
            'mobile_money' => [
                'name' => 'Mobile Money',
                'icon' => 'mobile',
                'fee_type' => FinancialSetting::get('mobile_money_fee_type', 'percentage'),
                'fee_amount' => FinancialSetting::get('mobile_money_fee_amount', 2.5),
                'processing_time' => FinancialSetting::get('mobile_money_processing_time', 'Instant'),
                'min_amount' => 1000,
                'max_amount' => 100000,
            ],
            'paypal' => [
                'name' => 'PayPal',
                'icon' => 'paypal',
                'fee_type' => FinancialSetting::get('paypal_fee_type', 'percentage'),
                'fee_amount' => FinancialSetting::get('paypal_fee_amount', 3.5),
                'processing_time' => FinancialSetting::get('paypal_processing_time', 'Instant'),
                'min_amount' => 1000,
                'max_amount' => 100000,
            ],
        ];
        
        return $methods;
    }

    /**
     * Get processing time for withdrawal method
     */
    public function getProcessingTime(string $method): string
    {
        return FinancialSetting::get("{$method}_processing_time", '1-3 business days');
    }

    /**
     * Get withdrawal method info
     */
    private function getWithdrawalMethodInfo(string $method): array
    {
        $methods = $this->getAvailableWithdrawalMethods();
        return $methods[$method] ?? [];
    }

    /**
     * Initialize PayPal payout
     */
    public function initializePayPalPayout(PayoutRequest $payoutRequest): array
    {
        try {
            $accessToken = $this->getPayPalAccessToken();
            
            $payoutData = [
                'sender_batch_header' => [
                    'sender_batch_id' => 'WITHDRAWAL_' . $payoutRequest->id . '_' . time(),
                    'email_subject' => 'IQRAQUEST Withdrawal',
                    'email_message' => 'You have received a withdrawal from IQRAQUEST'
                ],
                'items' => [
                    [
                        'recipient_type' => 'EMAIL',
                        'amount' => [
                            'value' => number_format($payoutRequest->net_amount, 2),
                            'currency' => $payoutRequest->currency
                        ],
                        'receiver' => (is_string($payoutRequest->payment_details) 
                            ? json_decode($payoutRequest->payment_details, true) 
                            : $payoutRequest->payment_details)['email'] ?? '',
                        'note' => 'Withdrawal from IQRAQUEST',
                        'sender_item_id' => 'WITHDRAWAL_' . $payoutRequest->id
                    ]
                ]
            ];

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post(config('services.paypal.base_url') . '/v1/payments/payouts', $payoutData);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'payout_batch_id' => $data['batch_header']['payout_batch_id'],
                    'redirect_url' => null // PayPal doesn't provide redirect URLs for payouts
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to initialize PayPal payout'
            ];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PayPal payout error', [
                'payout_request_id' => $payoutRequest->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'PayPal payout initialization failed'
            ];
        }
    }


    /**
     * Get PayPal access token
     */
    private function getPayPalAccessToken(): string
    {
        $response = \Illuminate\Support\Facades\Http::withBasicAuth(
            config('services.paypal.client_id'),
            config('services.paypal.client_secret')
        )->asForm()->post(config('services.paypal.base_url') . '/v1/oauth2/token', [
            'grant_type' => 'client_credentials'
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['access_token'];
        }

        throw new \Exception('Failed to get PayPal access token');
    }


    /**
     * Verify PayPal webhook
     */
    public function verifyPayPalWebhook(Request $request): bool
    {
        // Implement PayPal webhook verification
        // This would verify the webhook signature
        return true; // Placeholder
    }

    /**
     * Process PayPal webhook
     */
    public function processPayPalWebhook(array $payload): void
    {
        // Process PayPal webhook events
        // Update payout request status based on webhook data
    }

    /**
     * Initialize PayStack bank transfer
     */
    public function initializePayStackTransfer(PayoutRequest $payoutRequest): array
    {
        try {
            $payStackService = app(PayStackTransferService::class);
            return $payStackService->initializeBankTransfer($payoutRequest);
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
     * Process PayStack transfer webhook
     */
    public function processPayStackWebhook(array $payload): array
    {
        try {
            $payStackService = app(PayStackTransferService::class);
            return $payStackService->handleTransferWebhook($payload);
        } catch (\Exception $e) {
            Log::error('PayStack webhook processing failed', [
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to process PayStack webhook'
            ];
        }
    }

    /**
     * Verify PayStack transfer status
     */
    public function verifyPayStackTransfer(string $transferCode): array
    {
        try {
            $payStackService = app(PayStackTransferService::class);
            return $payStackService->verifyTransferStatus($transferCode);
        } catch (\Exception $e) {
            Log::error('PayStack transfer verification failed', [
                'transfer_code' => $transferCode,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to verify PayStack transfer'
            ];
        }
    }

    /**
     * Get supported banks for PayStack transfers
     */
    public function getSupportedBanks(): array
    {
        try {
            $payStackService = app(PayStackTransferService::class);
            return $payStackService->getSupportedBanks();
        } catch (\Exception $e) {
            Log::error('Failed to fetch supported banks', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to fetch supported banks'
            ];
        }
    }

    /**
     * Verify bank account number
     */
    public function verifyBankAccount(string $accountNumber, string $bankCode): array
    {
        try {
            $payStackService = app(PayStackTransferService::class);
            return $payStackService->verifyAccountNumber($accountNumber, $bankCode);
        } catch (\Exception $e) {
            Log::error('Bank account verification failed', [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to verify bank account'
            ];
        }
    }

}

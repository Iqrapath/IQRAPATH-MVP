<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BankVerificationService
{
    private string $paystackSecretKey;
    private string $paystackBaseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->paystackSecretKey = config('services.paystack.secret_key');
    }

    /**
     * Verify a bank account using Paystack API.
     *
     * @param string $accountNumber
     * @param string $bankCode
     * @return array
     * @throws \Exception
     */
    public function verifyBankAccount(string $accountNumber, string $bankCode): array
    {
        try {
            $response = Http::timeout(10) // 10 second timeout for verification
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                    'Content-Type' => 'application/json',
                ])->get("{$this->paystackBaseUrl}/bank/resolve", [
                    'account_number' => $accountNumber,
                    'bank_code' => $bankCode,
                ]);

            if (!$response->successful()) {
                $error = $response->json('message') ?? 'Bank verification failed';
                Log::error('Bank verification failed', [
                    'account_number' => $accountNumber,
                    'bank_code' => $bankCode,
                    'error' => $error,
                    'status' => $response->status(),
                ]);

                throw new \Exception($error);
            }

            $data = $response->json('data');

            if (!$data || !isset($data['account_name'])) {
                throw new \Exception('Invalid response from bank verification service');
            }

            Log::info('Bank account verified successfully', [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
                'account_name' => $data['account_name'],
            ]);

            return [
                'success' => true,
                'data' => [
                    'account_number' => $accountNumber,
                    'account_name' => $data['account_name'],
                    'bank_code' => $bankCode,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Bank verification exception', [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get list of banks from Paystack.
     *
     * @param string $country
     * @return array
     */
    public function getBankList(string $country = 'NG'): array
    {
        $cacheKey = "banks.{$country}";

        return Cache::remember($cacheKey, 86400, function () use ($country) {
            try {
                $response = Http::timeout(5) // 5 second timeout
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                    ])->get("{$this->paystackBaseUrl}/bank", [
                        'country' => $country,
                        'perPage' => 100,
                    ]);

                if (!$response->successful()) {
                    Log::error('Failed to fetch bank list', [
                        'country' => $country,
                        'status' => $response->status(),
                    ]);
                    return [];
                }

                $banks = $response->json('data', []);

                return collect($banks)->map(function ($bank) {
                    return [
                        'id' => $bank['id'],
                        'name' => $bank['name'],
                        'code' => $bank['code'],
                        'slug' => $bank['slug'] ?? null,
                        'country' => $bank['country'] ?? 'NG',
                    ];
                })->toArray();

            } catch (\Exception $e) {
                Log::error('Exception fetching bank list', [
                    'country' => $country,
                    'error' => $e->getMessage(),
                ]);
                return [];
            }
        });
    }

    /**
     * Validate account number format.
     *
     * @param string $accountNumber
     * @param string $bankCode
     * @return bool
     */
    public function validateAccountNumber(string $accountNumber, string $bankCode): bool
    {
        // Nigerian bank accounts are typically 10 digits
        if (strlen($accountNumber) !== 10) {
            return false;
        }

        // Must be numeric
        if (!ctype_digit($accountNumber)) {
            return false;
        }

        // Bank code must not be empty
        if (empty($bankCode)) {
            return false;
        }

        return true;
    }

    /**
     * Get bank name by code.
     *
     * @param string $bankCode
     * @param string $country
     * @return string|null
     */
    public function getBankName(string $bankCode, string $country = 'NG'): ?string
    {
        $banks = $this->getBankList($country);
        
        $bank = collect($banks)->firstWhere('code', $bankCode);
        
        return $bank['name'] ?? null;
    }

    /**
     * Verify multiple bank accounts in batch.
     *
     * @param array $accounts Array of ['account_number' => '', 'bank_code' => '']
     * @return array
     */
    public function verifyBankAccountsBatch(array $accounts): array
    {
        $results = [];

        foreach ($accounts as $account) {
            $accountNumber = $account['account_number'] ?? '';
            $bankCode = $account['bank_code'] ?? '';

            if (empty($accountNumber) || empty($bankCode)) {
                $results[] = [
                    'success' => false,
                    'error' => 'Missing account number or bank code',
                    'account_number' => $accountNumber,
                ];
                continue;
            }

            $results[] = $this->verifyBankAccount($accountNumber, $bankCode);

            // Add small delay to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }

        return $results;
    }

    /**
     * Clear bank list cache.
     *
     * @param string $country
     * @return void
     */
    public function clearBankListCache(string $country = 'NG'): void
    {
        Cache::forget("banks.{$country}");
    }

    /**
     * Check if bank code exists.
     *
     * @param string $bankCode
     * @param string $country
     * @return bool
     */
    public function bankCodeExists(string $bankCode, string $country = 'NG'): bool
    {
        $banks = $this->getBankList($country);
        
        return collect($banks)->contains('code', $bankCode);
    }
}

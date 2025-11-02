<?php

namespace App\Services;

use App\Models\User;
use App\Models\VirtualAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackVirtualAccountService
{
    private string $secretKey;
    private string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
    }

    /**
     * Create or get existing dedicated virtual account for user
     */
    public function getOrCreateVirtualAccount(User $user): ?VirtualAccount
    {
        // Check if user already has an active virtual account
        $existingAccount = VirtualAccount::where('user_id', $user->id)
            ->where('provider', 'paystack')
            ->where('is_active', true)
            ->first();

        if ($existingAccount) {
            return $existingAccount;
        }

        // Create new virtual account via Paystack
        return $this->createVirtualAccount($user);
    }

    /**
     * Create dedicated virtual account via Paystack API
     */
    private function createVirtualAccount(User $user): ?VirtualAccount
    {
        try {
            Log::info('[Paystack VA] Creating virtual account for user', ['user_id' => $user->id]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/dedicated_account', [
                'email' => $user->email,
                'first_name' => explode(' ', $user->name)[0],
                'last_name' => explode(' ', $user->name)[1] ?? explode(' ', $user->name)[0],
                'phone' => $user->phone ?? '',
                'preferred_bank' => 'wema-bank', // or 'titan-paystack'
                'country' => 'NG',
            ]);

            if (!$response->successful()) {
                $responseData = $response->json();
                $errorCode = $responseData['code'] ?? null;
                
                Log::error('[Paystack VA] Failed to create virtual account', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'response' => $responseData,
                    'error_code' => $errorCode
                ]);
                
                // If feature is not available, throw specific exception
                if ($errorCode === 'feature_unavailable') {
                    throw new \Exception('Dedicated Virtual Accounts feature is not enabled for your Paystack account. Please contact Paystack support to enable this feature.');
                }
                
                return null;
            }

            $data = $response->json()['data'];

            Log::info('[Paystack VA] Virtual account created successfully', [
                'user_id' => $user->id,
                'account_number' => $data['account_number']
            ]);

            // Store virtual account in database
            return VirtualAccount::create([
                'user_id' => $user->id,
                'account_number' => $data['account_number'],
                'account_name' => $data['account_name'],
                'bank_name' => $data['bank']['name'],
                'bank_code' => $data['bank']['code'] ?? null,
                'provider' => 'paystack',
                'provider_account_id' => $data['id'] ?? null,
                'provider_response' => $data,
                'is_active' => $data['active'] ?? true,
                'activated_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('[Paystack VA] Exception creating virtual account', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Handle Paystack webhook for virtual account credit
     */
    public function handleWebhook(array $payload): bool
    {
        try {
            $event = $payload['event'];
            
            if ($event !== 'charge.success') {
                return false;
            }

            $data = $payload['data'];
            
            // Check if it's a dedicated account transaction
            if (!isset($data['authorization']['channel']) || $data['authorization']['channel'] !== 'dedicated_nuban') {
                return false;
            }

            $accountNumber = $data['authorization']['receiver_bank_account_number'] ?? null;
            
            if (!$accountNumber) {
                Log::warning('[Paystack VA Webhook] No account number in payload');
                return false;
            }

            // Find virtual account
            $virtualAccount = VirtualAccount::where('account_number', $accountNumber)
                ->where('provider', 'paystack')
                ->first();

            if (!$virtualAccount) {
                Log::warning('[Paystack VA Webhook] Virtual account not found', [
                    'account_number' => $accountNumber
                ]);
                return false;
            }

            $amount = $data['amount'] / 100; // Convert from kobo to naira
            $reference = $data['reference'];

            Log::info('[Paystack VA Webhook] Processing credit', [
                'user_id' => $virtualAccount->user_id,
                'amount' => $amount,
                'reference' => $reference
            ]);

            // Credit user wallet
            $financialService = app(FinancialService::class);
            $financialService->addFunds(
                $virtualAccount->user,
                $amount,
                'Bank Transfer via Paystack Virtual Account',
                $reference
            );

            return true;

        } catch (\Exception $e) {
            Log::error('[Paystack VA Webhook] Exception processing webhook', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            return false;
        }
    }
}

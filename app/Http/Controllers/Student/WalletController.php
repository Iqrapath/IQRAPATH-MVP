<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\StudentWallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\PaymentMethod;

class WalletController extends Controller
{
    /**
     * Process wallet funding.
     */
    public function processFunding(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10|max:1000000',
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        $user = Auth::user();
        $wallet = $user->studentWallet;
        
        if (!$wallet) {
            $wallet = $user->studentWallet()->create([
                'balance' => 0,
                'total_spent' => 0,
                'total_refunded' => 0,
            ]);
        }

        // Verify payment method belongs to user
        $paymentMethod = PaymentMethod::where('id', $validated['payment_method_id'])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment method selected',
            ], 400);
        }

        $amount = (float) $validated['amount'];

        try {
            DB::beginTransaction();

            // Add funds to wallet
            $wallet->addFunds($amount, 'Wallet funding via ' . $paymentMethod->name);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Wallet funded successfully',
                'new_balance' => (float) $wallet->fresh()->balance,
                'payment_method' => $paymentMethod->name,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fund wallet: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current wallet balance (API endpoint).
     */
    public function getBalance()
    {
        $user = Auth::user();
        $wallet = $user->studentWallet;
        
        if (!$wallet) {
            $wallet = $user->studentWallet()->create([
                'balance' => 0,
                'total_spent' => 0,
                'total_refunded' => 0,
            ]);
        }

        return response()->json([
            'balance_ngn' => (float) $wallet->balance,
            'balance_usd' => (float) $wallet->balance / 1500, // Convert to USD
        ]);
    }

    /**
     * Get funding configuration (API endpoint).
     */
    public function getFundingConfig()
    {
        return response()->json([
            'min_amount' => 10,
            'max_amount' => 1000000,
            'currency' => '₦',
            'payment_method' => 'Bank Transfer',
            'bank_details' => [
                'name' => config('app.default_bank_name', 'First City Monument Bank'),
                'account_holder' => config('app.default_account_holder', 'Alayande Nurudeen Bamidele'),
                'account_number' => config('app.default_account_number', '4773719012'),
            ]
        ]);
    }

    /**
     * Get user's payment methods.
     */
    public function getPaymentMethods()
    {
        $user = Auth::user();
        $paymentMethods = $user->activePaymentMethods()->orderBy('is_default', 'desc')->get();

        $mapped = $paymentMethods->map(function ($method) {
            $data = [
                'id' => $method->id,
                'type' => $method->type,
                'name' => $method->name,
                'display_text' => $method->display_text,
                'is_default' => $method->is_default,
                // Bank transfer fields
                'bank_code' => $method->bank_code,
                'bank_name' => $method->bank_name,
                'account_name' => $method->account_name,
                'last_four' => $method->last_four,
                // Card-specific fields
                'card_brand' => $method->card_brand,
                'card_number_prefix' => $method->card_number_prefix,
                'card_number_middle' => $method->card_number_middle,
                'exp_month' => $method->exp_month,
                'exp_year' => $method->exp_year,
                'stripe_payment_method_id' => $method->stripe_payment_method_id,
                // Legacy details field for backward compatibility
                'details' => $method->details,
                'is_active' => $method->is_active,
                'is_verified' => $method->is_verified,
                'verification_status' => $method->verification_status,
                'created_at' => $method->created_at,
                'updated_at' => $method->updated_at,
            ];
            
            \Log::info('Payment Method Data', ['id' => $method->id, 'card_brand' => $method->card_brand, 'mapped_card_brand' => $data['card_brand']]);
            
            return $data;
        });

        return response()->json([
            'payment_methods' => $mapped,
        ]);
    }

    /**
     * Store a new payment method.
     */
    public function storePaymentMethod(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:bank_transfer,mobile_money',
            'name' => 'required|string|max:255',
            'details' => 'required|array',
            'is_default' => 'boolean',
        ]);

        $user = Auth::user();

        // If this is set as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            $user->paymentMethods()->where('is_default', true)->update(['is_default' => false]);
        }

        $paymentMethod = $user->paymentMethods()->create([
            'type' => $validated['type'],
            'name' => $validated['name'],
            'details' => $validated['details'],
            'is_default' => $validated['is_default'] ?? false,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'payment_method' => [
                'id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
                'name' => $paymentMethod->name,
                'display_text' => $paymentMethod->display_text,
                'is_default' => $paymentMethod->is_default,
                'details' => $paymentMethod->details,
            ],
            'message' => 'Payment method added successfully',
        ]);
    }

    /**
     * Update a payment method.
     */
    public function updatePaymentMethod(Request $request, PaymentMethod $paymentMethod)
    {
        $user = Auth::user();

        // Ensure user owns this payment method
        if ($paymentMethod->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'type' => 'sometimes|in:bank_transfer,mobile_money,card',
            'name' => 'sometimes|string|max:255',
            'bank_code' => 'required_if:type,bank_transfer|string',
            'bank_name' => 'required_if:type,bank_transfer|string',
            'account_number' => 'required_if:type,bank_transfer|string|size:10',
            'account_name' => 'required_if:type,bank_transfer|string',
            'currency' => 'sometimes|in:NGN,USD',
            'details' => 'sometimes|array',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        // Handle bank transfer updates with verification
        if (isset($validated['type']) && $validated['type'] === 'bank_transfer' && isset($validated['account_number'])) {
            $skipVerification = config('app.env') === 'local' && config('app.debug');
            
            if ($skipVerification) {
                \Log::info('Skipping bank verification in development mode (update)', [
                    'account_number' => $validated['account_number'],
                    'bank_name' => $validated['bank_name']
                ]);
                
                $validated['is_verified'] = true;
                $validated['verification_status'] = 'verified';
                $validated['verified_at'] = now();
            } else {
                // Production mode: Verify with Paystack
                $paystackSecretKey = config('services.paystack.secret_key');
                
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Bearer ' . $paystackSecretKey,
                    'Content-Type' => 'application/json',
                ])->get('https://api.paystack.co/bank/resolve', [
                    'account_number' => $validated['account_number'],
                    'bank_code' => $validated['bank_code'],
                ]);

                if (!$response->successful()) {
                    $errorMessage = $response->json()['message'] ?? 'Unable to verify bank account.';
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'account_number' => [$errorMessage]
                    ]);
                }

                $accountData = $response->json()['data'] ?? null;
                if (!$accountData || !isset($accountData['account_name'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'account_number' => ['Could not retrieve account name.']
                    ]);
                }

                $validated['account_name'] = $accountData['account_name'];
                $validated['is_verified'] = true;
                $validated['verification_status'] = 'verified';
                $validated['verified_at'] = now();
            }
            
            // Store only last 4 digits for security
            $validated['last_four'] = substr($validated['account_number'], -4);
            unset($validated['account_number']);
        }

        // If this is set as default, unset other defaults
        if (isset($validated['is_default']) && $validated['is_default']) {
            $user->paymentMethods()->where('id', '!=', $paymentMethod->id)->update(['is_default' => false]);
        }

        $paymentMethod->update($validated);

        return response()->json([
            'success' => true,
            'payment_method' => [
                'id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
                'name' => $paymentMethod->name,
                'bank_code' => $paymentMethod->bank_code,
                'bank_name' => $paymentMethod->bank_name,
                'account_name' => $paymentMethod->account_name,
                'last_four' => $paymentMethod->last_four,
                'display_text' => $paymentMethod->display_text,
                'is_default' => $paymentMethod->is_default,
                'is_verified' => $paymentMethod->is_verified,
                'details' => $paymentMethod->details,
            ],
            'message' => 'Payment method updated successfully',
        ]);
    }

    /**
     * Delete a payment method.
     */
    public function deletePaymentMethod(PaymentMethod $paymentMethod)
    {
        $user = Auth::user();

        // Ensure user owns this payment method
        if ($paymentMethod->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $paymentMethod->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment method deleted successfully',
        ]);
    }
}

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

    /**
     * Request wallet withdrawal.
     * POST /student/wallet/withdraw
     */
    public function requestWithdrawal(Request $request)
    {
        $user = Auth::user();

        // Verify user is a student
        if ($user->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Only students can request withdrawals',
            ], 403);
        }

        // Validate request
        $validated = $request->validate([
            'amount' => 'required|numeric|min:500',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $amount = (float) $validated['amount'];

        // Get student wallet
        $wallet = $user->studentWallet;
        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Student wallet not found',
            ], 404);
        }

        // Verify payment method belongs to user, is bank transfer, and is verified
        $paymentMethod = PaymentMethod::where('id', $validated['payment_method_id'])
            ->where('user_id', $user->id)
            ->where('type', 'bank_transfer')
            ->where('is_verified', true)
            ->where('is_active', true)
            ->first();

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment method. Please select a verified bank account.',
            ], 400);
        }

        // Check for duplicate pending requests
        $hasPendingRequest = $user->payoutRequests()
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($hasPendingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending withdrawal request. Please wait for it to be processed.',
            ], 400);
        }

        // Validate amount doesn't exceed available balance
        $availableBalance = $user->available_withdrawal_balance;
        if ($amount > $availableBalance) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance. Available for withdrawal: ₦' . number_format($availableBalance, 2),
            ], 400);
        }

        // Validate amount doesn't exceed wallet balance
        if ($amount > $wallet->balance) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Create payout request
            $payoutRequest = $user->payoutRequests()->create([
                'amount' => $amount,
                'payment_method' => 'bank_transfer',
                'payment_details' => [
                    'bank_name' => $paymentMethod->bank_name,
                    'bank_code' => $paymentMethod->bank_code,
                    'account_name' => $paymentMethod->account_name,
                    'account_number' => $paymentMethod->account_number ?? ($paymentMethod->last_four ? '******' . $paymentMethod->last_four : 'N/A'),
                ],
                'status' => 'pending',
                'currency' => 'NGN',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Deduct amount from wallet balance immediately
            $wallet->balance -= $amount;
            $wallet->save();

            // Create wallet transaction record
            $wallet->transactions()->create([
                'transaction_type' => 'debit',
                'amount' => $amount,
                'description' => 'Withdrawal request #' . $payoutRequest->request_uuid,
                'status' => 'pending',
                'transaction_date' => now(),
                'metadata' => [
                    'payout_request_id' => $payoutRequest->id,
                    'payment_method_id' => $paymentMethod->id,
                ],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted successfully',
                'data' => [
                    'request_id' => $payoutRequest->request_uuid,
                    'amount' => $amount,
                    'status' => 'pending',
                    'bank_details' => [
                        'bank_name' => $paymentMethod->bank_name,
                        'account_name' => $paymentMethod->account_name,
                        'last_four' => $paymentMethod->last_four,
                    ],
                    'new_balance' => (float) $wallet->fresh()->balance,
                    'processing_time' => '1-3 business days',
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Withdrawal request failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process withdrawal request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get withdrawal history.
     * GET /student/wallet/withdrawals
     */
    public function getWithdrawals(Request $request)
    {
        $user = Auth::user();

        // Verify user is a student
        if ($user->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Only students can view withdrawal history',
            ], 403);
        }

        // Get withdrawals with pagination (50 per page)
        // Eager load user relationship for potential future use
        $withdrawals = $user->payoutRequests()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        // Format the response
        $formattedWithdrawals = $withdrawals->map(function ($withdrawal) {
            return [
                'id' => $withdrawal->id,
                'request_uuid' => $withdrawal->request_uuid,
                'amount' => (float) $withdrawal->amount,
                'currency' => $withdrawal->currency ?? 'NGN',
                'status' => $withdrawal->status,
                'payment_method' => $withdrawal->payment_method,
                'bank_details' => [
                    'bank_name' => $withdrawal->payment_details['bank_name'] ?? null,
                    'account_name' => $withdrawal->payment_details['account_name'] ?? null,
                    'last_four' => isset($withdrawal->payment_details['account_number']) 
                        ? substr($withdrawal->payment_details['account_number'], -4) 
                        : null,
                ],
                'notes' => $withdrawal->notes,
                'created_at' => $withdrawal->created_at,
                'updated_at' => $withdrawal->updated_at,
                'processed_at' => $withdrawal->processed_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedWithdrawals,
            'pagination' => [
                'current_page' => $withdrawals->currentPage(),
                'per_page' => $withdrawals->perPage(),
                'total' => $withdrawals->total(),
                'last_page' => $withdrawals->lastPage(),
                'from' => $withdrawals->firstItem(),
                'to' => $withdrawals->lastItem(),
            ],
        ]);
    }

    /**
     * Initialize PayPal payment for wallet funding
     * POST /student/wallet/fund/paypal
     */
    public function fundWithPayPal(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|in:USD',
        ]);

        $user = Auth::user();
        $amount = (float) $validated['amount'];

        try {
            // Use PaymentGatewayService to create PayPal payment
            $paymentService = app(\App\Services\PaymentGatewayService::class);
            
            $result = $paymentService->createPayPalPayment([
                'amount' => $amount,
                'currency' => 'USD',
                'description' => 'Wallet Funding',
                'return_url' => route('student.wallet.paypal.success'),
                'cancel_url' => route('student.wallet.paypal.cancel'),
                'metadata' => [
                    'user_id' => $user->id,
                    'type' => 'wallet_funding',
                ]
            ]);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'approval_url' => $result['approval_url'],
                        'payment_id' => $result['payment_id'],
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to create PayPal payment'
                ], 400);
            }

        } catch (\Exception $e) {
            \Log::error('PayPal wallet funding failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize PayPal payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle successful PayPal payment
     * GET /student/wallet/paypal/success
     */
    public function paypalSuccess(Request $request)
    {
        $paymentId = $request->query('paymentId');
        $payerId = $request->query('PayerID');
        $token = $request->query('token');

        if (!$paymentId || !$payerId) {
            return redirect()->route('student.wallet.index')
                ->with('error', 'Invalid PayPal payment response');
        }

        try {
            $user = Auth::user();
            $paymentService = app(\App\Services\PaymentGatewayService::class);
            
            // Capture PayPal payment (PayPal uses "capture" not "execute")
            $result = $paymentService->capturePayPalPayment($paymentId);

            if ($result['success']) {
                // Add funds to wallet
                $wallet = $user->studentWallet;
                if (!$wallet) {
                    $wallet = $user->studentWallet()->create([
                        'balance' => 0,
                        'total_spent' => 0,
                        'total_refunded' => 0,
                    ]);
                }

                DB::beginTransaction();

                $amountUSD = $result['amount'];
                $amountNGN = $amountUSD * 1500; // Convert to NGN

                $wallet->addFunds($amountNGN, 'Wallet funding via PayPal');

                DB::commit();

                return redirect()->route('student.wallet.index')
                    ->with('success', 'Wallet funded successfully via PayPal! $' . number_format($amountUSD, 2) . ' added.');
            } else {
                return redirect()->route('student.wallet.index')
                    ->with('error', 'PayPal payment failed: ' . ($result['message'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('PayPal payment execution failed', [
                'payment_id' => $paymentId,
                'payer_id' => $payerId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('student.wallet.index')
                ->with('error', 'Failed to process PayPal payment');
        }
    }

    /**
     * Handle cancelled PayPal payment
     * GET /student/wallet/paypal/cancel
     */
    public function paypalCancel()
    {
        return redirect()->route('student.wallet.index')
            ->with('info', 'PayPal payment was cancelled');
    }

    /**
     * Get withdrawal details.
     * GET /student/wallet/withdrawals/{id}
     */
    public function getWithdrawalDetails($id)
    {
        $user = Auth::user();

        // Verify user is a student
        if ($user->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Only students can view withdrawal details',
            ], 403);
        }

        // Get withdrawal with relationships
        $withdrawal = $user->payoutRequests()
            ->with(['user', 'processedBy'])
            ->where('id', $id)
            ->orWhere('request_uuid', $id)
            ->first();

        if (!$withdrawal) {
            return response()->json([
                'success' => false,
                'message' => 'Withdrawal request not found',
            ], 404);
        }

        // Format the response with full details
        $details = [
            'id' => $withdrawal->id,
            'request_uuid' => $withdrawal->request_uuid,
            'amount' => (float) $withdrawal->amount,
            'currency' => $withdrawal->currency ?? 'NGN',
            'status' => $withdrawal->status,
            'payment_method' => $withdrawal->payment_method,
            'bank_details' => [
                'bank_name' => $withdrawal->payment_details['bank_name'] ?? null,
                'bank_code' => $withdrawal->payment_details['bank_code'] ?? null,
                'account_name' => $withdrawal->payment_details['account_name'] ?? null,
                'account_number_masked' => isset($withdrawal->payment_details['account_number']) 
                    ? '****' . substr($withdrawal->payment_details['account_number'], -4) 
                    : null,
            ],
            'notes' => $withdrawal->notes,
            'rejection_reason' => $withdrawal->failure_reason ?? null,
            'created_at' => $withdrawal->created_at,
            'updated_at' => $withdrawal->updated_at,
            'processed_at' => $withdrawal->processed_at,
            'processed_by' => $withdrawal->processedBy ? [
                'id' => $withdrawal->processedBy->id,
                'name' => $withdrawal->processedBy->name,
                'role' => $withdrawal->processedBy->role,
            ] : null,
            'transaction_id' => $withdrawal->transaction_id,
            'processing_time_estimate' => '1-3 business days',
        ];

        return response()->json([
            'success' => true,
            'data' => $details,
        ]);
    }
}

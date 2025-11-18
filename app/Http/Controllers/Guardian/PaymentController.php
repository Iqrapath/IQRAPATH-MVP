<?php
declare(strict_types=1);

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Services\UnifiedWalletService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private UnifiedWalletService $unifiedWalletService
    ) {}

    /**
     * Display the guardian wallet page
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $wallet = $user->guardianWallet;

        // Create wallet if doesn't exist
        if (!$wallet) {
            $wallet = $this->unifiedWalletService->getGuardianWallet($user);
        }

        // Get wallet settings
        $walletSettings = [
            'preferredCurrency' => 'NGN',
        ];

        // Get family spending summary (guardian-specific)
        $familySummary = $wallet->getFamilySpendingSummary();

        // Get upcoming payments for children (bookings that need payment)
        $upcomingPayments = collect();
        if ($user->guardianProfile) {
            $children = $user->guardianProfile->students;
            foreach ($children as $child) {
                $childBookings = $child->bookings()
                    ->whereIn('status', ['pending', 'approved', 'upcoming'])
                    ->where('booking_date', '>=', now())
                    ->with(['teacher.teacherProfile', 'subject'])
                    ->latest()
                    ->take(3)
                    ->get();
                
                foreach ($childBookings as $booking) {
                    $hourlyRate = $booking->hourly_rate_ngn 
                        ?? $booking->teacher?->teacherProfile?->hourly_rate_ngn 
                        ?? 0;
                    
                    $durationHours = ($booking->duration_minutes ?? 60) / 60;
                    $totalAmount = $hourlyRate * $durationHours;
                    
                    $upcomingPayments->push([
                        'id' => $booking->id,
                        'childName' => $child->user?->name ?? 'Unknown',
                        'childId' => $child->user_id,
                        'amount' => $totalAmount,
                        'amountSecondary' => $totalAmount / 1500,
                        'currency' => 'NGN',
                        'secondaryCurrency' => 'USD',
                        'teacherName' => $booking->teacher?->name ?? 'Unknown',
                        'subjectName' => $booking->subject?->name ?? 'Unknown Subject',
                        'dueDate' => $booking->booking_date ?? now(),
                        'startTime' => $booking->start_time ?? null,
                        'status' => $booking->status,
                    ]);
                }
            }
        }

        // Get payment history from unified transactions
        $paymentHistory = $wallet->unifiedTransactions()
            ->latest('transaction_date')
            ->take(10)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->transaction_date,
                    'description' => $transaction->description ?? 'Wallet Transaction',
                    'type' => $transaction->transaction_type,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency ?? 'NGN',
                    'status' => $transaction->status,
                ];
            });

        // Get payment methods
        $paymentMethods = $user->paymentMethods()
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->get()
            ->map(function ($method) {
                return [
                    'id' => $method->id,
                    'type' => $method->type,
                    'name' => $method->name,
                    'details' => $method->details,
                    'is_default' => $method->is_default,
                    'is_active' => $method->is_active,
                    'is_verified' => $method->is_verified,
                    'verification_status' => $method->verification_status ?? 'pending',
                    'created_at' => $method->created_at->toDateString(),
                ];
            });

        $availableCurrencies = [
            ['value' => 'NGN', 'label' => 'Nigerian Naira (NGN)', 'symbol' => '₦', 'is_default' => true],
            ['value' => 'USD', 'label' => 'US Dollar (USD)', 'symbol' => '$', 'is_default' => false],
        ];

        return Inertia::render('guardian/wallet/index', [
            'walletBalance' => (float) $wallet->balance,
            'totalSpentOnChildren' => (float) $wallet->total_spent_on_children,
            'totalRefunded' => (float) $wallet->total_refunded,
            'walletSettings' => $walletSettings,
            'familySummary' => $familySummary,
            'upcomingPayments' => $upcomingPayments->take(5),
            'paymentHistory' => $paymentHistory,
            'paymentMethods' => $paymentMethods,
            'availableCurrencies' => $availableCurrencies,
        ]);
    }

    /**
     * Save wallet settings
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferred_currency' => 'required|in:NGN,USD',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Settings saved successfully',
        ]);
    }

    /**
     * Email activity report
     */
    public function emailReport(Request $request): JsonResponse
    {
        try {
            $guardian = $request->user();
            $wallet = $guardian->guardianWallet;
            
            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }
            
            // Get recent transactions (last 30 days)
            $recentTransactions = $wallet->unifiedTransactions()
                ->where('transaction_date', '>=', now()->subDays(30))
                ->orderBy('transaction_date', 'desc')
                ->get()
                ->map(function ($transaction) {
                    return [
                        'date' => $transaction->transaction_date->format('M d, Y'),
                        'description' => $transaction->description ?? ucfirst($transaction->transaction_type) . ' transaction',
                        'amount' => $transaction->amount,
                        'type' => $transaction->transaction_type,
                        'status' => $transaction->status ?? 'completed',
                    ];
                });

            // Get wallet summary
            $summary = [
                'current_balance' => $wallet->balance ?? 0,
                'total_spent_on_children' => $wallet->total_spent_on_children ?? 0,
                'total_refunded' => $wallet->total_refunded ?? 0,
            ];

            // Send email (create mail class if needed)
            \Illuminate\Support\Facades\Mail::to($guardian->email)->send(
                new \App\Mail\Guardian\WalletActivityReport($guardian, $summary, $recentTransactions->toArray())
            );

            \Illuminate\Support\Facades\Log::info('Guardian wallet activity report emailed', [
                'guardian_id' => $guardian->id,
                'email' => $guardian->email,
                'transactions_count' => $recentTransactions->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Activity report sent to your email',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send guardian wallet activity report', [
                'error' => $e->getMessage(),
                'guardian_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send activity report. Please try again.',
            ], 500);
        }
    }

    /**
     * Transaction history page or API
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        $wallet = $user->guardianWallet;

        if (!$wallet) {
            $wallet = $this->unifiedWalletService->getGuardianWallet($user);
        }

        $transactions = $wallet->unifiedTransactions()
            ->latest('transaction_date')
            ->paginate(20);

        $formattedTransactions = $transactions->through(function ($transaction) {
            return [
                'id' => $transaction->id,
                'date' => $transaction->transaction_date,
                'type' => $transaction->transaction_type,
                'description' => $transaction->description ?? ucfirst($transaction->transaction_type) . ' transaction',
                'amount' => (float) $transaction->amount,
                'currency' => $transaction->currency ?? 'NGN',
                'status' => $transaction->status ?? 'completed',
            ];
        });

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $formattedTransactions->items(),
                'pagination' => [
                    'current_page' => $formattedTransactions->currentPage(),
                    'per_page' => $formattedTransactions->perPage(),
                    'total' => $formattedTransactions->total(),
                    'last_page' => $formattedTransactions->lastPage(),
                    'from' => $formattedTransactions->firstItem(),
                    'to' => $formattedTransactions->lastItem(),
                ],
            ]);
        }

        // Return Inertia page for direct navigation
        return Inertia::render('guardian/wallet/history', [
            'transactions' => $formattedTransactions,
            'walletBalance' => (float) $wallet->balance,
        ]);
    }

    /**
     * Process wallet funding payment
     */
    public function fundWallet(Request $request): JsonResponse
    {
        // Increase execution time for payment processing
        set_time_limit(120);
        
        try {
            \Log::info('Guardian payment request received', $request->all());
            
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:100|max:1000000',
                'gateway' => 'required|in:stripe,paystack',
                'payment_method_id' => 'required_if:gateway,stripe|string',
                'rememberCard' => 'boolean'
            ]);

            if ($validator->fails()) {
                \Log::error('Guardian payment validation failed', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            
            // Ensure user has a wallet
            $wallet = $this->unifiedWalletService->getGuardianWallet($user);

            $paymentData = [
                'amount' => $request->amount,
                'gateway' => $request->gateway,
                'payment_method_id' => $request->payment_method_id,
                'rememberCard' => $request->boolean('rememberCard'),
                'user_id' => $user->id,
                'wallet_type' => 'guardian'
            ];

            \Log::info('Processing guardian payment', ['user_id' => $user->id, 'amount' => $request->amount, 'gateway' => $request->gateway]);
            
            // Use PaymentService but adapt for guardian wallet
            $result = $this->paymentService->processWalletFunding($user, $paymentData);

            if ($result['success']) {
                // Update guardian wallet balance
                $wallet->addFunds($result['amount'], 'Wallet funding via ' . $request->gateway, [
                    'gateway' => $request->gateway,
                    'transaction_id' => $result['transaction_id'] ?? null,
                ]);

                \Log::info('Guardian payment successful', ['transaction_id' => $result['transaction_id']]);
                
                $responseData = [
                    'transaction_id' => $result['transaction_id'],
                    'amount' => $result['amount'],
                    'new_balance' => $wallet->fresh()->balance
                ];

                // Add gateway-specific data
                if ($request->gateway === 'paystack') {
                    $responseData['authorization_url'] = $result['authorization_url'];
                    $responseData['paystack_reference'] = $result['paystack_reference'];
                }

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $responseData
                ], 200);
            } else {
                \Log::error('Guardian payment failed', ['error' => $result['message']]);
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? 'unknown_error'
                ], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Guardian payment controller error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.',
                'error' => 'server_error'
            ], 500);
        }
    }

    /**
     * Get Stripe publishable key
     */
    public function getPublishableKey(): JsonResponse
    {
        return response()->json([
            'publishable_key' => $this->paymentService->getPublishableKey()
        ]);
    }

    /**
     * Get Paystack public key
     */
    public function getPaystackPublicKey(): JsonResponse
    {
        return response()->json([
            'public_key' => $this->paymentService->getPaystackPublicKey()
        ]);
    }

    /**
     * Verify Paystack payment
     */
    public function verifyPaystackPayment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'reference' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reference is required'
                ], 422);
            }

            $result = $this->paymentService->verifyPaystackPayment($request->reference);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            \Log::error('Guardian Paystack verification error', [
                'message' => $e->getMessage(),
                'reference' => $request->reference ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error verifying payment'
            ], 500);
        }
    }

    /**
     * Get or create virtual account for bank transfer
     */
    public function getVirtualAccount(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $virtualAccountService = app(\App\Services\PaystackVirtualAccountService::class);
            $virtualAccount = $virtualAccountService->getOrCreateVirtualAccount($user);

            if (!$virtualAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to create virtual account. Please try again or use card payment.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'account_number' => $virtualAccount->account_number,
                    'account_name' => $virtualAccount->account_name,
                    'bank_name' => $virtualAccount->bank_name,
                    'bank_code' => $virtualAccount->bank_code,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('[Guardian Virtual Account] Error getting virtual account', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if (str_contains($e->getMessage(), 'feature is not enabled')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank Transfer via Virtual Account is currently unavailable. Please use Credit/Debit Card payment or contact support.',
                    'feature_unavailable' => true
                ], 503);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error creating virtual account. Please try again or use card payment.'
            ], 500);
        }
    }

    /**
     * Get list of Nigerian banks from Paystack
     */
    public function getBanks(): JsonResponse
    {
        try {
            $paystackSecretKey = config('services.paystack.secret_key');
            
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $paystackSecretKey,
                'Content-Type' => 'application/json',
            ])->get('https://api.paystack.co/bank', [
                'country' => 'nigeria',
                'perPage' => 100
            ]);

            if ($response->successful()) {
                $banks = $response->json()['data'] ?? [];
                
                // Filter to only active banks and remove duplicates
                $uniqueBanks = [];
                $seenCodes = [];
                
                $majorBanks = [
                    'Access Bank', 'GTBank', 'Guaranty Trust Bank', 'First Bank', 'Zenith Bank',
                    'UBA', 'United Bank For Africa', 'Fidelity Bank', 'Union Bank',
                    'Sterling Bank', 'Stanbic IBTC Bank', 'Polaris Bank', 'Wema Bank',
                    'Ecobank Nigeria', 'FCMB', 'Keystone Bank', 'Unity Bank', 'Heritage Bank',
                    'Providus Bank', 'Jaiz Bank', 'SunTrust Bank', 'Titan Trust Bank',
                    'Globus Bank', 'Parallex Bank', 'Premium Trust Bank'
                ];
                
                foreach ($banks as $bank) {
                    $code = $bank['code'] ?? null;
                    $name = $bank['name'] ?? '';
                    $isActive = $bank['active'] ?? true;
                    
                    if (!$code || in_array($code, $seenCodes) || !$isActive) {
                        continue;
                    }
                    
                    if (stripos($name, 'test') !== false || 
                        stripos($name, 'microfinance') !== false ||
                        stripos($name, 'mortgage') !== false) {
                        continue;
                    }
                    
                    $uniqueBanks[] = $bank;
                    $seenCodes[] = $code;
                }
                
                usort($uniqueBanks, function($a, $b) use ($majorBanks) {
                    $aIsMajor = in_array($a['name'], $majorBanks);
                    $bIsMajor = in_array($b['name'], $majorBanks);
                    
                    if ($aIsMajor && !$bIsMajor) return -1;
                    if (!$aIsMajor && $bIsMajor) return 1;
                    
                    return strcmp($a['name'], $b['name']);
                });
                
                return response()->json($uniqueBanks);
            }

            return response()->json([
                'error' => 'Failed to fetch banks'
            ], 500);

        } catch (\Exception $e) {
            \Log::error('Error fetching banks for guardian', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to fetch banks'
            ], 500);
        }
    }

    /**
     * Get payment methods for guardian
     */
    public function getPaymentMethods(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $paymentMethods = $user->paymentMethods()
                ->where('is_active', true)
                ->orderBy('is_default', 'desc')
                ->get()
                ->map(function ($method) {
                    return [
                        'id' => $method->id,
                        'type' => $method->type,
                        'name' => $method->name,
                        'bank_code' => $method->bank_code,
                        'bank_name' => $method->bank_name,
                        'account_name' => $method->account_name,
                        'last_four' => $method->last_four,
                        'card_brand' => $method->card_brand,
                        'card_number_prefix' => $method->card_number_prefix,
                        'card_number_middle' => $method->card_number_middle,
                        'exp_month' => $method->exp_month,
                        'exp_year' => $method->exp_year,
                        'stripe_payment_method_id' => $method->stripe_payment_method_id,
                        'details' => $method->details,
                        'is_default' => $method->is_default,
                        'is_active' => $method->is_active,
                        'is_verified' => $method->is_verified,
                        'verification_status' => $method->verification_status ?? 'pending',
                        'created_at' => $method->created_at->toDateString(),
                        'updated_at' => $method->updated_at->toDateString(),
                    ];
                });

            return response()->json([
                'payment_methods' => $paymentMethods
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching guardian payment methods', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'error' => 'Failed to fetch payment methods'
            ], 500);
        }
    }

    /**
     * Store a new payment method
     */
    public function storePaymentMethod(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:bank_transfer,mobile_money,card',
                'name' => 'required|string|max:255',
                'bank_code' => 'required_if:type,bank_transfer|string',
                'bank_name' => 'nullable|string',
                'account_number' => 'required_if:type,bank_transfer|string|size:10',
                'account_name' => 'nullable|string',
                'card_brand' => 'required_if:type,card|string',
                'card_number_prefix' => 'nullable|string|max:4',
                'card_number_middle' => 'nullable|string|max:4',
                'last_four' => 'nullable|string|max:4',
                'exp_month' => 'required_if:type,card|integer|min:1|max:12',
                'exp_year' => 'required_if:type,card|integer|min:' . date('Y'),
                'stripe_payment_method_id' => 'required_if:type,card|string',
                'remember_card' => 'nullable|boolean',
                'currency' => 'required|in:NGN,USD',
            ]);

            $user = Auth::user();

            // Verify account with Paystack if it's a bank transfer
            if ($validated['type'] === 'bank_transfer') {
                $skipVerification = config('app.env') === 'local' && config('app.debug');
                
                if ($skipVerification) {
                    \Log::info('Skipping bank verification in development mode (guardian)', [
                        'account_number' => $validated['account_number'],
                        'bank_name' => $validated['bank_name']
                    ]);
                    
                    if (empty($validated['account_name'])) {
                        $validated['account_name'] = 'Test Account Holder';
                    }
                    
                    $validated['is_verified'] = true;
                    $validated['verification_status'] = 'verified';
                    $validated['verified_at'] = now();
                } else {
                    $paystackSecretKey = config('services.paystack.secret_key');
                    
                    \Log::info('Verifying bank account (guardian)', [
                        'account_number' => $validated['account_number'],
                        'bank_code' => $validated['bank_code'],
                        'bank_name' => $validated['bank_name']
                    ]);
                    
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Authorization' => 'Bearer ' . $paystackSecretKey,
                        'Content-Type' => 'application/json',
                    ])->get('https://api.paystack.co/bank/resolve', [
                        'account_number' => $validated['account_number'],
                        'bank_code' => $validated['bank_code'],
                    ]);

                    \Log::info('Paystack verification response (guardian)', [
                        'status' => $response->status(),
                        'body' => $response->json()
                    ]);

                    if (!$response->successful()) {
                        $errorMessage = $response->json()['message'] ?? 'Unable to verify bank account. Please check your details.';
                        
                        if (stripos($errorMessage, 'test') !== false || $validated['account_number'] === '0000000000') {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'account_number' => ['Test account numbers are not supported. Please use a real bank account number.']
                            ]);
                        }
                        
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'account_number' => [$errorMessage]
                        ]);
                    }

                    $accountData = $response->json()['data'] ?? null;
                    
                    if (!$accountData || !isset($accountData['account_name'])) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'account_number' => ['Could not retrieve account name. Please verify your account number.']
                        ]);
                    }

                    $validated['account_name'] = $accountData['account_name'];
                    $validated['is_verified'] = true;
                    $validated['verification_status'] = 'verified';
                    $validated['verified_at'] = now();
                    
                    \Log::info('Bank account verified successfully (guardian)', [
                        'account_name' => $validated['account_name']
                    ]);
                }
                
                $validated['last_four'] = substr($validated['account_number'], -4);
                unset($validated['account_number']);
            }

            $hasExistingMethods = $user->paymentMethods()->active()->exists();
            if (!$hasExistingMethods) {
                $validated['is_default'] = true;
            }

            if ($validated['type'] === 'card') {
                $validated['is_verified'] = true;
                $validated['verification_status'] = 'verified';
                $validated['verified_at'] = now();
            }

            $paymentMethod = $user->paymentMethods()->create([
                'type' => $validated['type'],
                'name' => $validated['name'],
                'bank_code' => $validated['bank_code'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'account_name' => $validated['account_name'] ?? null,
                'last_four' => $validated['last_four'] ?? null,
                'card_brand' => $validated['card_brand'] ?? null,
                'card_number_prefix' => $validated['card_number_prefix'] ?? null,
                'card_number_middle' => $validated['card_number_middle'] ?? null,
                'exp_month' => $validated['exp_month'] ?? null,
                'exp_year' => $validated['exp_year'] ?? null,
                'stripe_payment_method_id' => $validated['stripe_payment_method_id'] ?? null,
                'currency' => $validated['currency'] ?? 'NGN',
                'is_default' => $validated['is_default'] ?? false,
                'is_active' => true,
                'is_verified' => $validated['is_verified'] ?? false,
                'verification_status' => $validated['verification_status'] ?? 'pending',
                'verified_at' => $validated['verified_at'] ?? null,
            ]);

            \Log::info('Guardian payment method created securely', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
            ]);

            return back()->with('success', 'Payment method added successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error storing guardian payment method', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->withErrors([
                'error' => 'Failed to add payment method. Please try again.'
            ]);
        }
    }

    /**
     * Update an existing payment method
     */
    public function updatePaymentMethod(Request $request, $paymentMethodId)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:bank_transfer,mobile_money,card',
                'name' => 'required|string|max:255',
                'bank_code' => 'required_if:type,bank_transfer|string',
                'bank_name' => 'required_if:type,bank_transfer|string',
                'account_number' => 'required_if:type,bank_transfer|string|size:10',
                'account_name' => 'nullable|string',
                'card_brand' => 'required_if:type,card|string',
                'last_four' => 'nullable|string|max:4',
                'exp_month' => 'required_if:type,card|integer|min:1|max:12',
                'exp_year' => 'required_if:type,card|integer|min:' . date('Y'),
                'stripe_payment_method_id' => 'required_if:type,card|string',
                'remember_card' => 'nullable|boolean',
                'currency' => 'required|in:NGN,USD',
            ]);

            $user = Auth::user();
            $paymentMethod = $user->paymentMethods()->findOrFail($paymentMethodId);

            // Similar verification logic as storePaymentMethod
            if ($validated['type'] === 'bank_transfer') {
                $skipVerification = config('app.env') === 'local' && config('app.debug');
                
                if (!$skipVerification) {
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
                    if ($accountData && isset($accountData['account_name'])) {
                        $validated['account_name'] = $accountData['account_name'];
                    }
                } else {
                    if (empty($validated['account_name'])) {
                        $validated['account_name'] = 'Test Account Holder';
                    }
                }
                
                $validated['last_four'] = substr($validated['account_number'], -4);
                unset($validated['account_number']);
            }

            $updateData = [
                'type' => $validated['type'],
                'name' => $validated['name'],
                'currency' => $validated['currency'] ?? 'NGN',
            ];

            if ($validated['type'] === 'bank_transfer') {
                $updateData['bank_code'] = $validated['bank_code'] ?? null;
                $updateData['bank_name'] = $validated['bank_name'] ?? null;
                $updateData['account_name'] = $validated['account_name'] ?? null;
                $updateData['last_four'] = $validated['last_four'] ?? null;
            }

            if ($validated['type'] === 'card') {
                $updateData['card_brand'] = $validated['card_brand'] ?? null;
                $updateData['last_four'] = $validated['last_four'] ?? null;
                $updateData['exp_month'] = $validated['exp_month'] ?? null;
                $updateData['exp_year'] = $validated['exp_year'] ?? null;
                $updateData['stripe_payment_method_id'] = $validated['stripe_payment_method_id'] ?? null;
                $updateData['is_verified'] = true;
                $updateData['verification_status'] = 'verified';
                $updateData['verified_at'] = now();
            }

            $paymentMethod->update($updateData);

            return back()->with('success', 'Payment method updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error updating guardian payment method', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->withErrors([
                'error' => 'Failed to update payment method. Please try again.'
            ]);
        }
    }

    /**
     * Set a payment method as default
     */
    public function setDefaultPaymentMethod($paymentMethodId)
    {
        try {
            $user = Auth::user();
            $paymentMethod = $user->paymentMethods()->findOrFail($paymentMethodId);

            $user->paymentMethods()->where('id', '!=', $paymentMethodId)->update(['is_default' => false]);
            $paymentMethod->update(['is_default' => true]);

            return back()->with('success', 'Default payment method updated successfully');

        } catch (\Exception $e) {
            \Log::error('Error setting default guardian payment method', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->withErrors([
                'error' => 'Failed to set default payment method. Please try again.'
            ]);
        }
    }

    /**
     * Delete a payment method
     */
    public function deletePaymentMethod($paymentMethodId)
    {
        try {
            $user = Auth::user();
            $paymentMethod = $user->paymentMethods()->findOrFail($paymentMethodId);

            if ($paymentMethod->is_default && $user->paymentMethods()->where('id', '!=', $paymentMethodId)->count() > 0) {
                return back()->withErrors([
                    'error' => 'Cannot delete the default payment method. Please set another payment method as default first.'
                ]);
            }

            $paymentMethod->delete();

            return back()->with('success', 'Payment method deleted successfully');

        } catch (\Exception $e) {
            \Log::error('Error deleting guardian payment method', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->withErrors([
                'error' => 'Failed to delete payment method. Please try again.'
            ]);
        }
    }

    /**
     * Get guardian wallet funding configuration
     */
    public function getFundingConfig(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'config' => [
                    'min_amount' => 100,
                    'max_amount' => 1000000,
                    'currency' => 'NGN',
                    'supported_gateways' => ['stripe', 'paystack'],
                    'default_gateway' => 'stripe'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to get guardian funding config', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get funding configuration'
            ], 500);
        }
    }
}

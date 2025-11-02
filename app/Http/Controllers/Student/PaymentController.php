<?php
declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Display the student wallet page
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $wallet = $user->studentWallet;

        // Create wallet if doesn't exist
        if (!$wallet) {
            $wallet = $user->studentWallet()->create([
                'balance' => 0,
                'total_spent' => 0,
                'total_refunded' => 0,
            ]);
        }

        // Get wallet settings
        $walletSettings = [
            'preferredCurrency' => 'NGN',
        ];

        // Get upcoming payments (bookings that need payment)
        $upcomingPayments = $user->studentBookings()
            ->whereIn('status', ['pending', 'approved', 'upcoming'])
            ->where('booking_date', '>=', now())
            ->with(['teacher.teacherProfile', 'subject'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($booking) {
                // Calculate amount based on hourly rate and duration
                // Use booking's locked rate, or fall back to teacher's current rate
                $hourlyRate = $booking->hourly_rate_ngn 
                    ?? $booking->teacher->teacherProfile->hourly_rate_ngn 
                    ?? 0;
                    
                $durationHours = ($booking->duration_minutes ?? 60) / 60;
                $totalAmount = $hourlyRate * $durationHours;
                
                return [
                    'id' => $booking->id,
                    'amount' => $totalAmount,
                    'amountSecondary' => $totalAmount / 1500, // Approximate NGN to USD conversion
                    'currency' => 'NGN',
                    'secondaryCurrency' => 'USD',
                    'teacherName' => $booking->teacher->name ?? 'Unknown',
                    'subjectName' => $booking->subject->name ?? 'Unknown Subject',
                    'dueDate' => $booking->booking_date ?? now(),
                    'startTime' => $booking->start_time ?? null,
                    'status' => $booking->status,
                ];
            });

        // Get payment history from bookings (completed and pending)
        $paymentHistory = $user->studentBookings()
            ->whereIn('status', ['completed', 'confirmed', 'paid', 'pending', 'approved', 'upcoming'])
            ->with(['teacher.teacherProfile', 'subject'])
            ->latest('booking_date')
            ->take(10)
            ->get()
            ->map(function ($booking) {
                // Calculate amount based on hourly rate and duration
                $hourlyRate = $booking->hourly_rate_ngn 
                    ?? $booking->teacher->teacherProfile->hourly_rate_ngn 
                    ?? 0;
                    
                $durationHours = ($booking->duration_minutes ?? 60) / 60;
                $totalAmount = $hourlyRate * $durationHours;
                
                return [
                    'id' => $booking->id,
                    'date' => $booking->booking_date,
                    'subject' => $booking->subject->name ?? 'Unknown Subject',
                    'teacherName' => $booking->teacher->name ?? 'Unknown',
                    'amount' => $totalAmount,
                    'currency' => 'NGN',
                    'status' => $booking->status,
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

        return Inertia::render('student/wallet/index', [
            'walletBalance' => (float) $wallet->balance,
            'totalSpent' => (float) $wallet->total_spent,
            'totalRefunded' => (float) $wallet->total_refunded,
            'walletSettings' => $walletSettings,
            'upcomingPayments' => $upcomingPayments,
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
            $student = $request->user();
            
            // Get recent transactions (last 30 days)
            $recentTransactions = \App\Models\WalletTransaction::where('wallet_id', $student->studentWallet->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($transaction) {
                    return [
                        'date' => $transaction->created_at->format('M d, Y'),
                        'description' => $transaction->description ?? ucfirst($transaction->transaction_type) . ' transaction',
                        'amount' => $transaction->amount,
                        'type' => $transaction->transaction_type,
                        'status' => $transaction->status ?? 'completed',
                    ];
                });

            // Get wallet summary
            $studentWallet = $student->studentWallet;
            
            // Calculate total deposited (credits)
            $totalDeposited = \App\Models\WalletTransaction::where('wallet_id', $studentWallet->id)
                ->where('transaction_type', 'credit')
                ->where('status', 'completed')
                ->sum('amount');
            
            // Calculate total spent (debits)
            $totalSpent = \App\Models\WalletTransaction::where('wallet_id', $studentWallet->id)
                ->where('transaction_type', 'debit')
                ->where('status', 'completed')
                ->sum('amount');
            
            // Calculate pending payments
            $pendingPayments = \App\Models\WalletTransaction::where('wallet_id', $studentWallet->id)
                ->where('status', 'pending')
                ->sum('amount');
            
            $summary = [
                'current_balance' => $studentWallet->balance ?? 0,
                'total_deposited' => $totalDeposited,
                'total_spent' => $totalSpent,
                'pending_payments' => $pendingPayments,
            ];

            // Send email
            \Illuminate\Support\Facades\Mail::to($student->email)->send(
                new \App\Mail\Student\WalletActivityReport($student, $summary, $recentTransactions->toArray())
            );

            \Illuminate\Support\Facades\Log::info('Wallet activity report emailed', [
                'student_id' => $student->id,
                'email' => $student->email,
                'transactions_count' => $recentTransactions->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Activity report sent to your email',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send wallet activity report', [
                'error' => $e->getMessage(),
                'student_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send activity report. Please try again.',
            ], 500);
        }
    }

    /**
     * Transaction history page
     */
    public function history(Request $request): Response
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

        $transactions = $wallet->transactions()
            ->latest()
            ->paginate(20)
            ->through(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->created_at->toDateString(),
                    'type' => $transaction->transaction_type,
                    'description' => $transaction->description,
                    'subject' => 'Wallet Transaction',
                    'teacherName' => 'System',
                    'amount' => $transaction->amount,
                    'currency' => 'NGN',
                    'status' => $transaction->status,
                    'balance_after' => $transaction->balance_after ?? null,
                ];
            });

        return Inertia::render('student/wallet/history', [
            'transactions' => $transactions,
            'walletBalance' => (float) $wallet->balance,
        ]);
    }

    /**
     * Process wallet funding payment
     */
    public function fundWallet(Request $request): JsonResponse
    {
        // Increase execution time for payment processing (Stripe can be slow)
        set_time_limit(120); // 2 minutes
        
        try {
            \Log::info('Payment request received', $request->all());
            
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:100|max:1000000', // Min ₦100, Max ₦1,000,000
                'gateway' => 'required|in:stripe,paystack',
                'payment_method_id' => 'required_if:gateway,stripe|string',
                'rememberCard' => 'boolean'
            ]);

            if ($validator->fails()) {
                \Log::error('Validation failed', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            
            // Ensure user has a wallet (create if doesn't exist)
            $wallet = $user->getOrCreateWallet();

            $paymentData = [
                'amount' => $request->amount,
                'gateway' => $request->gateway,
                'payment_method_id' => $request->payment_method_id,
                'rememberCard' => $request->boolean('rememberCard'),
                'user_id' => $user->id
            ];

            \Log::info('Processing payment', ['user_id' => $user->id, 'amount' => $request->amount, 'gateway' => $request->gateway]);
            
            $result = $this->paymentService->processWalletFunding($user, $paymentData);

            if ($result['success']) {
                \Log::info('Payment successful', ['transaction_id' => $result['transaction_id']]);
                
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
                \Log::error('Payment failed', ['error' => $result['message']]);
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? 'unknown_error'
                ], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Payment controller error', [
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
            \Log::error('Paystack verification error', [
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
            \Log::error('[Virtual Account] Error getting virtual account', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            // Check if it's a feature unavailable error
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
                
                // List of major Nigerian banks to prioritize
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
                    
                    // Skip if no code, duplicate, or inactive
                    if (!$code || in_array($code, $seenCodes) || !$isActive) {
                        continue;
                    }
                    
                    // Skip test banks and non-commercial banks
                    if (stripos($name, 'test') !== false || 
                        stripos($name, 'microfinance') !== false ||
                        stripos($name, 'mortgage') !== false) {
                        continue;
                    }
                    
                    $uniqueBanks[] = $bank;
                    $seenCodes[] = $code;
                }
                
                // Sort banks: major banks first, then alphabetically
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
            \Log::error('Error fetching banks', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to fetch banks'
            ], 500);
        }
    }

    /**
     * Get payment methods for student
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
            \Log::error('Error fetching payment methods', [
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
                // Bank transfer fields
                'bank_code' => 'required_if:type,bank_transfer|string',
                'bank_name' => 'nullable|string',
                'account_number' => 'required_if:type,bank_transfer|string|size:10',
                'account_name' => 'nullable|string',
                // Card fields
                'card_brand' => 'required_if:type,card|string',
                'card_number_prefix' => 'nullable|string|max:4',
                'card_number_middle' => 'nullable|string|max:4',
                'last_four' => 'nullable|string|max:4',
                'exp_month' => 'required_if:type,card|integer|min:1|max:12',
                'exp_year' => 'required_if:type,card|integer|min:' . date('Y'),
                'stripe_payment_method_id' => 'required_if:type,card|string',
                'remember_card' => 'nullable|boolean',
                // Common fields
                'currency' => 'required|in:NGN,USD',
            ]);

            $user = Auth::user();

            // Verify account with Paystack if it's a bank transfer
            if ($validated['type'] === 'bank_transfer') {
                $skipVerification = config('app.env') === 'local' && config('app.debug');
                
                if ($skipVerification) {
                    // Development mode: Skip Paystack verification
                    \Log::info('Skipping bank verification in development mode', [
                        'account_number' => $validated['account_number'],
                        'bank_name' => $validated['bank_name']
                    ]);
                    
                    // Use provided account name or generate a test one
                    if (empty($validated['account_name'])) {
                        $validated['account_name'] = 'Test Account Holder';
                    }
                    
                    $validated['is_verified'] = true;
                    $validated['verification_status'] = 'verified';
                    $validated['verified_at'] = now();
                } else {
                    // Production mode: Verify with Paystack
                    $paystackSecretKey = config('services.paystack.secret_key');
                    
                    \Log::info('Verifying bank account', [
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

                    \Log::info('Paystack verification response', [
                        'status' => $response->status(),
                        'body' => $response->json()
                    ]);

                    if (!$response->successful()) {
                        $errorMessage = $response->json()['message'] ?? 'Unable to verify bank account. Please check your details.';
                        
                        // Check if it's a test mode issue
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

                    // Update account name with verified name from Paystack
                    $validated['account_name'] = $accountData['account_name'];
                    $validated['is_verified'] = true;
                    $validated['verification_status'] = 'verified';
                    $validated['verified_at'] = now();
                    
                    \Log::info('Bank account verified successfully', [
                        'account_name' => $validated['account_name']
                    ]);
                }
                
                // Store only last 4 digits for security (like teachers)
                $validated['last_four'] = substr($validated['account_number'], -4);
                // Don't store full account number
                unset($validated['account_number']);
            }

            // Set as default if this is the first payment method
            $hasExistingMethods = $user->paymentMethods()->active()->exists();
            if (!$hasExistingMethods) {
                $validated['is_default'] = true;
            }

            // Create payment method with secure storage
            $paymentMethod = $user->paymentMethods()->create([
                'type' => $validated['type'],
                'name' => $validated['name'],
                // Bank transfer fields
                'bank_code' => $validated['bank_code'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'account_name' => $validated['account_name'] ?? null,
                'last_four' => $validated['last_four'] ?? null,
                // Card fields
                'card_brand' => $validated['card_brand'] ?? null,
                'card_number_prefix' => $validated['card_number_prefix'] ?? null,
                'card_number_middle' => $validated['card_number_middle'] ?? null,
                'exp_month' => $validated['exp_month'] ?? null,
                'exp_year' => $validated['exp_year'] ?? null,
                'stripe_payment_method_id' => $validated['stripe_payment_method_id'] ?? null,
                // Common fields
                'currency' => $validated['currency'] ?? 'NGN',
                'is_default' => $validated['is_default'] ?? false,
                'is_active' => true,
                'is_verified' => $validated['is_verified'] ?? false,
                'verification_status' => $validated['verification_status'] ?? 'pending',
                'verified_at' => $validated['verified_at'] ?? null,
            ]);

            \Log::info('Payment method created securely', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
                'bank_name' => $paymentMethod->bank_name,
                'last_four' => $paymentMethod->last_four,
                'verified' => $paymentMethod->is_verified
            ]);

            return back()->with('success', 'Payment method added successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error storing payment method', [
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
                // Bank transfer fields
                'bank_code' => 'required_if:type,bank_transfer|string',
                'bank_name' => 'required_if:type,bank_transfer|string',
                'account_number' => 'required_if:type,bank_transfer|string|size:10',
                'account_name' => 'nullable|string',
                // Card fields
                'card_brand' => 'required_if:type,card|string',
                'last_four' => 'nullable|string|max:4',
                'exp_month' => 'required_if:type,card|integer|min:1|max:12',
                'exp_year' => 'required_if:type,card|integer|min:' . date('Y'),
                'stripe_payment_method_id' => 'required_if:type,card|string',
                'remember_card' => 'nullable|boolean',
                // Common fields
                'currency' => 'required|in:NGN,USD',
            ]);

            $user = Auth::user();
            
            // Find the payment method and ensure it belongs to the user
            $paymentMethod = $user->paymentMethods()->findOrFail($paymentMethodId);

            // Verify account with Paystack if it's a bank transfer
            if ($validated['type'] === 'bank_transfer') {
                $skipVerification = config('app.env') === 'local' && config('app.debug');
                
                if ($skipVerification) {
                    // Development mode: Skip Paystack verification
                    \Log::info('Skipping bank verification in development mode (update)', [
                        'account_number' => $validated['account_number'],
                        'bank_name' => $validated['bank_name']
                    ]);
                    
                    // Use provided account name or generate a test one
                    if (empty($validated['account_name'])) {
                        $validated['account_name'] = 'Test Account Holder';
                    }
                    
                    $validated['is_verified'] = true;
                    $validated['verification_status'] = 'verified';
                    $validated['verified_at'] = now();
                } else {
                    // Production mode: Verify with Paystack
                    $paystackSecretKey = config('services.paystack.secret_key');
                    
                    \Log::info('Verifying bank account for update', [
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

                    \Log::info('Paystack verification response (update)', [
                        'status' => $response->status(),
                        'body' => $response->json()
                    ]);

                    if (!$response->successful()) {
                        $errorMessage = $response->json()['message'] ?? 'Unable to verify bank account. Please check your details.';
                        
                        // Check if it's a test mode issue
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

                    // Update account name with verified name from Paystack
                    $validated['account_name'] = $accountData['account_name'];
                    $validated['is_verified'] = true;
                    $validated['verification_status'] = 'verified';
                    $validated['verified_at'] = now();
                    
                    \Log::info('Bank account verified successfully (update)', [
                        'account_name' => $validated['account_name']
                    ]);
                }
                
                // Store only last 4 digits for security
                $validated['last_four'] = substr($validated['account_number'], -4);
                // Don't store full account number
                unset($validated['account_number']);
            }

            // Update payment method with secure storage
            $updateData = [
                'type' => $validated['type'],
                'name' => $validated['name'],
                'currency' => $validated['currency'] ?? 'NGN',
            ];

            // Add bank transfer fields if applicable
            if ($validated['type'] === 'bank_transfer') {
                $updateData['bank_code'] = $validated['bank_code'] ?? null;
                $updateData['bank_name'] = $validated['bank_name'] ?? null;
                $updateData['account_name'] = $validated['account_name'] ?? null;
                $updateData['last_four'] = $validated['last_four'] ?? null;
                $updateData['is_verified'] = $validated['is_verified'] ?? false;
                $updateData['verification_status'] = $validated['verification_status'] ?? 'pending';
                $updateData['verified_at'] = $validated['verified_at'] ?? null;
            }

            // Add card fields if applicable
            if ($validated['type'] === 'card') {
                $updateData['card_brand'] = $validated['card_brand'] ?? null;
                $updateData['last_four'] = $validated['last_four'] ?? null;
                $updateData['exp_month'] = $validated['exp_month'] ?? null;
                $updateData['exp_year'] = $validated['exp_year'] ?? null;
                $updateData['stripe_payment_method_id'] = $validated['stripe_payment_method_id'] ?? null;
                $updateData['account_name'] = $validated['account_name'] ?? null;
            }

            \Log::info('Updating payment method', [
                'payment_method_id' => $paymentMethod->id,
                'type' => $validated['type'],
                'update_data' => $updateData
            ]);

            $paymentMethod->update($updateData);

            \Log::info('Payment method updated securely', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
                'card_brand' => $paymentMethod->card_brand,
                'last_four' => $paymentMethod->last_four,
                'account_name' => $paymentMethod->account_name,
                'exp_month' => $paymentMethod->exp_month,
                'exp_year' => $paymentMethod->exp_year,
            ]);

            return back()->with('success', 'Payment method updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error updating payment method', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'payment_method_id' => $paymentMethodId
            ]);

            return back()->withErrors([
                'error' => 'Failed to update payment method. Please try again.'
            ]);
        }
    }
}

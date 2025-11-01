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
}

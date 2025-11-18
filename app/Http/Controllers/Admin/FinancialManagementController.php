<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\TeacherEarning;
use App\Models\Transaction;
use App\Models\User;
use App\Models\FinancialSetting;
use App\Services\FinancialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FinancialManagementController extends Controller
{
    protected $financialService;

    protected $notificationService;

    public function __construct(
        FinancialService $financialService,
        \App\Services\NotificationService $notificationService
    ) {
        $this->financialService = $financialService;
        $this->notificationService = $notificationService;
        // $this->middleware(['auth', 'role:super-admin, admin']);
    }

    /**
     * Display the financial management dashboard.
     */
    public function index()
    {
        try {
            // Get summary statistics with null safety
            $totalTeachers = User::where('role', 'teacher')->count() ?? 0;
            $totalEarnings = TeacherEarning::sum('total_earned') ?? 0;
            $pendingPayouts = PayoutRequest::where('status', 'pending')->count() ?? 0;
            $pendingPayoutsAmount = PayoutRequest::where('status', 'pending')->sum('amount') ?? 0;
            
            // Get recent transactions with error handling
            $recentTransactions = Transaction::with(['teacher'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'teacher_name' => $transaction->teacher->name ?? 'N/A',
                        'teacher_email' => $transaction->teacher->email ?? 'N/A',
                        'amount' => $transaction->amount ?? 0,
                        'transaction_type' => $transaction->transaction_type ?? 'unknown',
                        'status' => $transaction->status ?? 'pending',
                        'transaction_date' => $transaction->transaction_date ?? $transaction->created_at,
                        'created_at' => $transaction->created_at,
                    ];
                });
                
            // Get all recent teacher payout requests (not just pending) with error handling
            $pendingPayoutRequests = PayoutRequest::teacherPayouts()
                ->with(['teacher', 'transaction'])
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get()
                ->map(function ($payout) {
                    return [
                        'id' => $payout->id,
                        'teacher_name' => $payout->teacher->name ?? 'N/A',
                        'email' => $payout->teacher->email ?? 'N/A',
                        'amount' => $payout->amount ?? 0,
                        'request_date' => $payout->request_date ?? $payout->created_at,
                        'payment_method' => $payout->payment_method ?? 'bank_transfer',
                        'status' => $payout->status ?? 'pending',
                        'payment_details' => $payout->payment_details ?? [],
                    ];
                });

            // Get student withdrawal requests
            $studentWithdrawalRequests = PayoutRequest::studentWithdrawals()
                ->with(['user'])
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get()
                ->map(function ($withdrawal) {
                    $paymentDetails = $withdrawal->payment_details ?? [];
                    $accountNumber = $paymentDetails['account_number'] ?? null;
                    
                    return [
                        'id' => $withdrawal->id,
                        'student_name' => $withdrawal->user->name ?? 'N/A',
                        'email' => $withdrawal->user->email ?? 'N/A',
                        'amount' => $withdrawal->amount ?? 0,
                        'request_date' => $withdrawal->created_at,
                        'status' => $withdrawal->status ?? 'pending',
                        'bank_name' => $paymentDetails['bank_name'] ?? 'N/A',
                        'account_number' => $accountNumber,
                        'account_name' => $paymentDetails['account_name'] ?? 'N/A',
                        'payment_method' => [
                            'bank_name' => $paymentDetails['bank_name'] ?? 'N/A',
                            'account_number' => $accountNumber,
                            'account_name' => $paymentDetails['account_name'] ?? 'N/A',
                        ],
                    ];
                });

            // Get student subscription payments
            $studentPayments = \App\Models\SubscriptionTransaction::with(['user', 'subscription.plan'])
                ->where('status', 'completed')
                ->orderBy('created_at', 'desc')
                ->take(50)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'date' => $transaction->created_at->toDateTimeString(),
                        'student_name' => $transaction->user->name ?? 'N/A',
                        'student_email' => $transaction->user->email ?? 'N/A',
                        'plan' => $transaction->subscription->plan->name ?? 'N/A',
                        'amount' => $transaction->amount ?? 0,
                        'currency' => $transaction->currency ?? 'NGN',
                        'payment_method' => ucfirst($transaction->payment_method ?? 'N/A'),
                        'status' => $transaction->status ?? 'pending',
                    ];
                });

            // Get comprehensive transaction logs (ALL financial activities)
            $transactionLogs = collect();

            // 1. Teacher payout requests
            $payoutLogs = PayoutRequest::teacherPayouts()
                ->with(['teacher'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($payout) {
                    return [
                        'id' => 'payout_' . $payout->id,
                        'date' => $payout->created_at->toDateTimeString(),
                        'user_name' => $payout->teacher->name ?? 'N/A',
                        'user_email' => $payout->teacher->email ?? 'N/A',
                        'description' => 'Teacher Payout Request',
                        'amount' => $payout->amount ?? 0,
                        'currency' => 'NGN',
                        'status' => $payout->status === 'paid' ? 'completed' : $payout->status,
                        'transaction_type' => 'payout',
                        'created_at' => $payout->created_at,
                    ];
                });

            // 2. Student withdrawal requests
            $withdrawalLogs = PayoutRequest::studentWithdrawals()
                ->with(['user'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($withdrawal) {
                    return [
                        'id' => 'withdrawal_' . $withdrawal->id,
                        'date' => $withdrawal->created_at->toDateTimeString(),
                        'user_name' => $withdrawal->user->name ?? 'N/A',
                        'user_email' => $withdrawal->user->email ?? 'N/A',
                        'description' => 'Student Withdrawal Request',
                        'amount' => $withdrawal->amount ?? 0,
                        'currency' => 'NGN',
                        'status' => $withdrawal->status === 'completed' ? 'completed' : $withdrawal->status,
                        'transaction_type' => 'withdrawal',
                        'created_at' => $withdrawal->created_at,
                    ];
                });

            // 3. Student subscription payments
            $subscriptionLogs = \App\Models\SubscriptionTransaction::with(['user'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => 'subscription_' . $transaction->id,
                        'date' => $transaction->created_at->toDateTimeString(),
                        'user_name' => $transaction->user->name ?? 'N/A',
                        'user_email' => $transaction->user->email ?? 'N/A',
                        'description' => 'Subscription Payment',
                        'amount' => $transaction->amount ?? 0,
                        'currency' => $transaction->currency ?? 'NGN',
                        'status' => $transaction->status === 'completed' ? 'platform_earned' : $transaction->status,
                        'transaction_type' => 'subscription',
                        'created_at' => $transaction->created_at,
                    ];
                });

            // 4. Wallet transactions (skip for now due to polymorphic wallet relationships)
            $walletLogs = collect([]);

            // Merge all logs and sort by date
            $allTransactions = $transactionLogs
                ->concat($payoutLogs)
                ->concat($withdrawalLogs)
                ->concat($subscriptionLogs)
                ->concat($walletLogs)
                ->sortByDesc('created_at')
                ->values();

            // Manual pagination
            $perPage = 10;
            $currentPage = (int) request()->input('page', 1);
            $offset = ($currentPage - 1) * $perPage;
            
            $paginatedItems = $allTransactions->slice($offset, $perPage)->values()->toArray();
            
            $transactionLogs = [
                'data' => $paginatedItems,
                'current_page' => $currentPage,
                'last_page' => (int) ceil($allTransactions->count() / $perPage),
                'per_page' => $perPage,
                'total' => $allTransactions->count(),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $allTransactions->count()),
            ];

            // Get payment settings
            $paymentSettings = [
                'commission_rate' => (float) FinancialSetting::get('commission_rate', 10),
                'commission_type' => FinancialSetting::get('commission_type', 'fixed_percentage'),
                'auto_payout_threshold' => (float) FinancialSetting::get('auto_payout_threshold', 50000),
                'minimum_withdrawal_amount' => (float) FinancialSetting::get('minimum_withdrawal_amount', 10000),
                'bank_verification_enabled' => FinancialSetting::get('bank_verification_enabled', 'true') === 'true',
                'withdrawal_note' => FinancialSetting::get('withdrawal_note', 'Withdrawals are processed within 1-3 business days.'),
            ];

            // Get withdrawal limits
            $withdrawalLimits = [
                'daily_withdrawal_limit' => (float) FinancialSetting::get('daily_withdrawal_limit', 500000),
                'monthly_withdrawal_limit' => (float) FinancialSetting::get('monthly_withdrawal_limit', 5000000),
                'instant_payouts_enabled' => FinancialSetting::get('instant_payouts_enabled', 'true') === 'true',
            ];

            // Get payment methods settings
            $paymentMethods = [
                'bank_transfer_fee_type' => FinancialSetting::get('bank_transfer_fee_type', 'flat'),
                'bank_transfer_fee_amount' => (float) FinancialSetting::get('bank_transfer_fee_amount', 100),
                'bank_transfer_processing_time' => FinancialSetting::get('bank_transfer_processing_time', '1-3 business days'),
                'mobile_money_fee_type' => FinancialSetting::get('mobile_money_fee_type', 'percentage'),
                'mobile_money_fee_amount' => (float) FinancialSetting::get('mobile_money_fee_amount', 2.5),
                'mobile_money_processing_time' => FinancialSetting::get('mobile_money_processing_time', 'Instant'),
                'paypal_fee_type' => FinancialSetting::get('paypal_fee_type', 'percentage'),
                'paypal_fee_amount' => (float) FinancialSetting::get('paypal_fee_amount', 3.5),
                'paypal_processing_time' => FinancialSetting::get('paypal_processing_time', 'Instant'),
                'flutterwave_fee_type' => FinancialSetting::get('flutterwave_fee_type', 'flat'),
                'flutterwave_fee_amount' => (float) FinancialSetting::get('flutterwave_fee_amount', 50),
                'flutterwave_processing_time' => FinancialSetting::get('flutterwave_processing_time', '1-2 business days'),
                'paystack_fee_type' => FinancialSetting::get('paystack_fee_type', 'flat'),
                'paystack_fee_amount' => (float) FinancialSetting::get('paystack_fee_amount', 100),
                'paystack_processing_time' => FinancialSetting::get('paystack_processing_time', '1-2 business days'),
                'stripe_fee_type' => FinancialSetting::get('stripe_fee_type', 'percentage'),
                'stripe_fee_amount' => (float) FinancialSetting::get('stripe_fee_amount', 2.9),
                'stripe_processing_time' => FinancialSetting::get('stripe_processing_time', '1-2 business days'),
            ];

            // Get currency settings
            $currencySettings = [
                'platform_currency' => FinancialSetting::get('platform_currency', 'NGN'),
                'multi_currency_mode' => FinancialSetting::get('multi_currency_mode', 'true') === 'true',
            ];

            return Inertia::render('admin/financial/index', [
                'totalTeachers' => $totalTeachers,
                'totalEarnings' => $totalEarnings,
                'pendingPayouts' => $pendingPayouts,
                'pendingPayoutsAmount' => $pendingPayoutsAmount,
                'recentTransactions' => $recentTransactions,
                'pendingPayoutRequests' => $pendingPayoutRequests,
                'studentWithdrawalRequests' => $studentWithdrawalRequests,
                'studentPayments' => $studentPayments,
                'transactions' => $transactionLogs,
                'paymentSettings' => $paymentSettings,
                'withdrawalLimits' => $withdrawalLimits,
                'paymentMethods' => $paymentMethods,
                'currencySettings' => $currencySettings,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading financial dashboard: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return with empty data to prevent complete failure
            return Inertia::render('admin/financial/index', [
                'totalTeachers' => 0,
                'totalEarnings' => 0,
                'pendingPayouts' => 0,
                'pendingPayoutsAmount' => 0,
                'recentTransactions' => [],
                'pendingPayoutRequests' => [],
                'studentWithdrawalRequests' => [],
                'studentPayments' => [],
                'error' => 'Unable to load financial data. Please try again later.'
            ]);
        }
    }

    /**
     * Display all transactions.
     */
    public function transactions(Request $request)
    {
        $query = Transaction::with(['teacher']);
        
        // Apply filters
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        
        if ($request->has('type')) {
            $query->where('transaction_type', $request->type);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }
        
        // Sort transactions
        $query->orderBy('transaction_date', 'desc')
              ->orderBy('created_at', 'desc');
        
        $transactions = $query->paginate(20);
        
        // Get teachers for filter dropdown
        $teachers = User::where('role', 'teacher')->get(['id', 'name']);
        
        return Inertia::render('admin/financial/transactions', [
            'transactions' => $transactions,
            'filters' => $request->only(['teacher_id', 'type', 'status', 'date_from', 'date_to']),
            'teachers' => $teachers,
        ]);
    }

    /**
     * Display all payout requests.
     */
    public function payoutRequests(Request $request)
    {
        $query = PayoutRequest::with(['teacher', 'transaction']);
        
        // Apply filters
        if ($request->has('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('request_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('request_date', '<=', $request->date_to);
        }
        
        // Sort payout requests
        $query->orderBy('request_date', 'desc')
              ->orderBy('created_at', 'desc');
        
        $payoutRequests = $query->paginate(20);
        
        // Get teachers for filter dropdown
        $teachers = User::where('role', 'teacher')->get(['id', 'name']);
        
        return Inertia::render('admin/financial/payout-requests', [
            'payoutRequests' => $payoutRequests,
            'filters' => $request->only(['teacher_id', 'status', 'date_from', 'date_to']),
            'teachers' => $teachers,
        ]);
    }

    /**
     * Show transaction details.
     */
    public function showTransaction(Transaction $transaction)
    {
        return Inertia::render('admin/financial/transaction-details', [
            'transaction' => $transaction->load(['teacher', 'session', 'createdBy']),
        ]);
    }

    /**
     * Show payout request details.
     */
    public function showPayoutRequest(PayoutRequest $payoutRequest)
    {
        // Get teacher/user
        $user = $payoutRequest->user ?? $payoutRequest->teacher;
        
        // Get teacher earnings data if user is a teacher
        $teacherEarnings = null;
        if ($user && $user->role === 'teacher') {
            $teacherEarning = TeacherEarning::where('teacher_id', $user->id)->first();
            if ($teacherEarning) {
                $teacherEarnings = [
                    'wallet_balance' => $teacherEarning->wallet_balance ?? 0,
                    'total_earned' => $teacherEarning->total_earned ?? 0,
                    'previous_payouts' => $teacherEarning->total_withdrawn ?? 0,
                ];
            }
        }
        
        // Get recent session logs (last 10 completed sessions)
        $sessionLogs = [];
        if ($user && $user->role === 'teacher') {
            $sessions = \App\Models\TeachingSession::where('teacher_id', $user->id)
                ->where('status', 'completed')
                ->with(['subject', 'booking'])
                ->orderBy('session_date', 'desc')
                ->orderBy('start_time', 'desc')
                ->take(10)
                ->get();
            
            $sessionLogs = $sessions->map(function ($session) {
                return [
                    'id' => $session->id,
                    'date' => $session->session_date->format('M d, Y') . ' – ' . 
                             \Carbon\Carbon::parse($session->start_time)->format('h:i A'),
                    'subject' => $session->subject->name ?? 'N/A',
                    'session_type' => $session->booking->session_type ?? '1-on-1 Session',
                    'amount_earned' => $session->teacher_earnings ?? 0,
                ];
            })->toArray();
        }
        
        // Get user's available payment methods
        $availablePaymentMethods = [];
        if ($user) {
            $paymentMethods = \App\Models\PaymentMethod::where('user_id', $user->id)
                ->where('is_active', true)
                ->get();
            
            $availablePaymentMethods = $paymentMethods->map(function ($method) {
                return [
                    'id' => $method->id,
                    'type' => $method->type,
                    'details' => [
                        'bank_name' => $method->bank_name ?? null,
                        'account_number' => $method->account_number ?? null,
                        'account_name' => $method->account_name ?? null,
                        'card_last_four' => $method->card_last_four ?? null,
                        'card_brand' => $method->card_brand ?? null,
                        'paypal_email' => $method->paypal_email ?? null,
                    ],
                    'is_default' => $method->is_default ?? false,
                ];
            })->toArray();
        }
        
        return Inertia::render('admin/financial/payout-request-details', [
            'payoutRequest' => $payoutRequest->load(['teacher', 'user', 'processedBy', 'transaction']),
            'teacherEarnings' => $teacherEarnings,
            'sessionLogs' => $sessionLogs,
            'availablePaymentMethods' => $availablePaymentMethods,
        ]);
    }

    /**
     * Approve a payout request.
     */
    public function approvePayoutRequest(PayoutRequest $payoutRequest)
    {
        try {
            $admin = Auth::user();
            
            // Verify admin is authenticated
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }
            
            // Verify that the payout request is still pending
            if ($payoutRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending payout requests can be approved.'
                ], 422);
            }
            
            // Check if teacher exists
            if (!$payoutRequest->teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found for this payout request.'
                ], 404);
            }
            
            // Approve the payout request
            $payoutRequest->approve($admin);
            
            // Refresh to get updated notes
            $payoutRequest = $payoutRequest->fresh()->load('teacher');
            
            // Check if automatic payout succeeded or failed
            $autoPayoutFailed = str_contains($payoutRequest->notes ?? '', 'Automatic transfer failed');
            
            if ($autoPayoutFailed) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payout approved but automatic transfer failed. Please process manually.',
                    'warning' => true,
                    'data' => $payoutRequest
                ], 200);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Payout request approved and payment initiated successfully!',
                'data' => $payoutRequest
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Error approving payout request: ' . $e->getMessage(), [
                'payout_request_id' => $payoutRequest->id,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Show detailed error in development, generic in production
            $errorMessage = app()->environment('local', 'development') 
                ? 'Error: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')'
                : 'An error occurred while approving the payout request. Please try again.';
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage
            ], 500);
        }
    }

    /**
     * Mark a payout as completed after manual processing.
     */
    public function markAsCompleted(Request $request, PayoutRequest $payoutRequest)
    {
        try {
            $validated = $request->validate([
                'external_reference' => 'required|string|max:255',
                'notes' => 'nullable|string|max:1000',
            ]);
            
            $admin = Auth::user();
            
            // Verify admin is authenticated
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }
            
            // Verify that the payout request requires manual processing
            if ($payoutRequest->status !== 'requires_manual_processing' && $payoutRequest->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only payouts requiring manual processing can be marked as completed.'
                ], 422);
            }
            
            // Check for duplicate transaction reference
            $duplicateReference = PayoutRequest::where('external_reference', $validated['external_reference'])
                ->where('id', '!=', $payoutRequest->id)
                ->where('status', 'completed')
                ->first();
                
            if ($duplicateReference) {
                \Log::warning('Duplicate transaction reference attempted', [
                    'payout_id' => $payoutRequest->id,
                    'duplicate_payout_id' => $duplicateReference->id,
                    'reference' => $validated['external_reference'],
                    'admin_id' => $admin->id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'This transaction reference has already been used for another payout. Please verify the reference number.'
                ], 422);
            }
            
            // Check if marked completed too quickly (potential fraud indicator)
            $minutesSinceRequest = $payoutRequest->created_at->diffInMinutes(now());
            if ($minutesSinceRequest < 5) {
                \Log::warning('Payout marked completed suspiciously fast', [
                    'payout_id' => $payoutRequest->id,
                    'admin_id' => $admin->id,
                    'minutes_since_request' => $minutesSinceRequest,
                ]);
            }
            
            // Load teacher relationship if not loaded
            if (!$payoutRequest->relationLoaded('teacher')) {
                $payoutRequest->load('teacher');
            }
            
            // Enhanced audit logging
            \Log::channel('daily')->info('Manual payout marked as completed', [
                'payout_id' => $payoutRequest->id,
                'payout_uuid' => $payoutRequest->request_uuid ?? null,
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'admin_email' => $admin->email,
                'teacher_id' => $payoutRequest->user_id,
                'teacher_name' => optional($payoutRequest->teacher)->name ?? 'N/A',
                'amount' => $payoutRequest->amount,
                'currency' => $payoutRequest->currency ?? 'NGN',
                'external_reference' => $validated['external_reference'],
                'payment_method' => $payoutRequest->payment_method,
                'bank_details' => $payoutRequest->payment_details,
                'timestamp' => now()->toIso8601String(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'notes' => $validated['notes'] ?? null,
            ]);
            
            // Update payout request
            $additionalNotes = isset($validated['notes']) && !empty($validated['notes']) 
                ? "\nNotes: " . $validated['notes'] 
                : '';
            
            $payoutRequest->update([
                'status' => 'completed',
                'processed_at' => now(),
                'processed_by_id' => $admin->id,
                'external_reference' => $validated['external_reference'],
                'notes' => ($payoutRequest->notes ?? '') . "\n\nManually processed by {$admin->name} on " . now()->format('Y-m-d H:i:s') . $additionalNotes,
            ]);
            
            // Send notification to teacher
            if ($payoutRequest->teacher) {
                try {
                    $this->notificationService->createNotification(
                        $payoutRequest->teacher,
                        'payout_completed',
                        [
                            'title' => 'Payout Completed',
                            'message' => "Your payout request of ₦{$payoutRequest->amount} has been completed successfully.",
                            'amount' => $payoutRequest->amount,
                            'external_reference' => $validated['external_reference'],
                            'payout_id' => $payoutRequest->id,
                            'processed_by' => $admin->name,
                            'processed_at' => now()->toDateTimeString(),
                        ],
                        'success'
                    );
                } catch (\Exception $notificationError) {
                    // Log notification error but don't fail the whole operation
                    \Log::error('Failed to send payout completion notification', [
                        'payout_id' => $payoutRequest->id,
                        'error' => $notificationError->getMessage(),
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Payout marked as completed successfully!',
                'data' => $payoutRequest->fresh()->load('teacher', 'processedBy')
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Error marking payout as completed: ' . $e->getMessage(), [
                'payout_request_id' => $payoutRequest->id,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Show detailed error in development
            $errorMessage = app()->environment('local', 'development') 
                ? 'Error: ' . $e->getMessage() . ' (File: ' . basename($e->getFile()) . ' Line: ' . $e->getLine() . ')'
                : 'An error occurred while marking the payout as completed. Please try again.';
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage
            ], 500);
        }
    }

    /**
     * Reject a payout request.
     */
    public function rejectPayoutRequest(Request $request, PayoutRequest $payoutRequest)
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
            ]);
            
            $admin = Auth::user();
            
            // Verify admin is authenticated
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }
            
            // Verify that the payout request is still pending
            if ($payoutRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending payout requests can be rejected.'
                ], 422);
            }
            
            // Check if teacher exists
            if (!$payoutRequest->teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found for this payout request.'
                ], 404);
            }
            
            // Reject the payout request
            $payoutRequest->reject($admin, $validated['reason']);
            
            return response()->json([
                'success' => true,
                'message' => 'Payout request rejected successfully.',
                'data' => $payoutRequest->fresh()->load('teacher')
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Error rejecting payout request: ' . $e->getMessage(), [
                'payout_request_id' => $payoutRequest->id,
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rejecting the payout request. Please try again.'
            ], 500);
        }
    }

    /**
     * Mark a payout request as paid.
     */
    public function markPayoutRequestAsPaid(PayoutRequest $payoutRequest)
    {
        try {
            $admin = Auth::user();
            
            // Verify admin is authenticated
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }
            
            // Verify that the payout request is approved
            if ($payoutRequest->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved payout requests can be marked as paid.'
                ], 422);
            }
            
            // Check if teacher exists
            if (!$payoutRequest->teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found for this payout request.'
                ], 404);
            }
            
            // Mark the payout request as paid
            $payoutRequest->markAsPaid($admin);
            
            return response()->json([
                'success' => true,
                'message' => 'Payout request marked as paid successfully.',
                'data' => $payoutRequest->fresh()->load('teacher')
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Error marking payout as paid: ' . $e->getMessage(), [
                'payout_request_id' => $payoutRequest->id,
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while marking the payout as paid. Please try again.'
            ], 500);
        }
    }

    /**
     * Show form to create a system adjustment.
     */
    public function createSystemAdjustment()
    {
        // Get teachers for dropdown
        $teachers = User::where('role', 'teacher')->get(['id', 'name']);
        
        return Inertia::render('admin/financial/create-system-adjustment', [
            'teachers' => $teachers,
        ]);
    }

    /**
     * Store a system adjustment.
     */
    public function storeSystemAdjustment(Request $request)
    {
        try {
            $validated = $request->validate([
                'teacher_id' => 'required|exists:users,id',
                'amount' => 'required|numeric|not_in:0',
                'reason' => 'required|string|max:500',
            ]);
            
            $admin = Auth::user();
            
            // Verify admin is authenticated
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }
            
            $teacher = User::findOrFail($validated['teacher_id']);
            
            // Verify the user is actually a teacher
            if ($teacher->role !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected user is not a teacher.'
                ], 422);
            }
            
            // Create the system adjustment
            $this->financialService->createSystemAdjustment(
                $teacher, 
                $validated['amount'], 
                $validated['reason'], 
                $admin
            );
            
            return response()->json([
                'success' => true,
                'message' => 'System adjustment created successfully.'
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Error creating system adjustment: ' . $e->getMessage(), [
                'teacher_id' => $request->teacher_id ?? null,
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the system adjustment. Please try again.'
            ], 500);
        }
    }

    /**
     * Show form to create a refund.
     */
    public function createRefund(Transaction $transaction)
    {
        // Verify that the transaction is completed
        if ($transaction->status !== 'completed') {
            return redirect()->back()->with('error', 'Only completed transactions can be refunded.');
        }
        
        // Verify that the transaction is not already a refund
        if ($transaction->transaction_type === 'refund') {
            return redirect()->back()->with('error', 'Cannot refund a refund transaction.');
        }
        
        return Inertia::render('admin/financial/create-refund', [
            'transaction' => $transaction->load('teacher'),
        ]);
    }

    /**
     * Store a refund.
     */
    public function storeRefund(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $transaction->amount,
            'reason' => 'required|string|max:255',
        ]);
        
        $admin = Auth::user();
        
        // Create the refund
        $this->financialService->createRefund(
            $transaction, 
            $validated['amount'], 
            $validated['reason'], 
            $admin
        );
        
        return redirect()->route('admin.financial.transactions')
            ->with('success', 'Refund created successfully.');
    }

    /**
     * Show teacher earnings.
     */
    public function teacherEarnings(Request $request)
    {
        $query = TeacherEarning::with(['teacher']);
        
        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('teacher', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Sort earnings
        $query->orderBy('total_earned', 'desc');
        
        $earnings = $query->paginate(20);
        
        return Inertia::render('admin/financial/teacher-earnings', [
            'earnings' => $earnings,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Display all student withdrawal requests.
     */
    public function getStudentWithdrawals(Request $request)
    {
        try {
            $query = PayoutRequest::studentWithdrawals()
                ->with(['user.studentProfile']);
            
            // Apply status filter
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            
            // Apply date range filters
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Apply search filter (student name or request ID)
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhere('request_uuid', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }
            
            // Sort by most recent first
            $query->orderBy('created_at', 'desc');
            
            // Paginate results (50 per page as per design spec)
            $withdrawals = $query->paginate(50)->through(function ($withdrawal) {
                // Extract bank details from payment_details JSON
                $paymentDetails = $withdrawal->payment_details ?? [];
                $accountNumber = $paymentDetails['account_number'] ?? null;
                
                return [
                    'id' => $withdrawal->id,
                    'request_uuid' => $withdrawal->request_uuid,
                    'student_id' => $withdrawal->user_id,
                    'student_name' => $withdrawal->user->name ?? 'N/A',
                    'student_email' => $withdrawal->user->email ?? 'N/A',
                    'amount' => $withdrawal->amount,
                    'currency' => $withdrawal->currency ?? 'NGN',
                    'status' => $withdrawal->status,
                    'request_date' => $withdrawal->created_at,
                    'processed_at' => $withdrawal->processed_at,
                    'processed_by_id' => $withdrawal->processed_by_id,
                    'bank_details' => [
                        'bank_name' => $paymentDetails['bank_name'] ?? 'N/A',
                        'account_name' => $paymentDetails['account_name'] ?? 'N/A',
                        'account_number_masked' => $accountNumber 
                            ? '****' . substr($accountNumber, -4) 
                            : 'N/A',
                        'account_number_full' => $accountNumber, // Full number for admin
                    ],
                    'notes' => $withdrawal->notes,
                ];
            });
            
            return Inertia::render('admin/financial/student-withdrawals', [
                'withdrawals' => $withdrawals,
                'filters' => $request->only(['status', 'date_from', 'date_to', 'search']),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error loading student withdrawals: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Inertia::render('admin/financial/student-withdrawals', [
                'withdrawals' => [],
                'filters' => $request->only(['status', 'date_from', 'date_to', 'search']),
                'error' => 'Unable to load student withdrawals. Please try again later.'
            ]);
        }
    }

    /**
     * Show student withdrawal request details.
     */
    public function showStudentWithdrawal(PayoutRequest $payoutRequest)
    {
        try {
            // Load relationships
            $payoutRequest->load(['user', 'processedBy']);
            
            // Get student wallet information with calculated values
            $studentWallet = null;
            if ($payoutRequest->user && $payoutRequest->user->studentWallet) {
                $wallet = $payoutRequest->user->studentWallet;
                
                // Calculate total funded (all credit transactions)
                $totalFunded = $wallet->transactions()
                    ->where('transaction_type', 'credit')
                    ->where('status', 'completed')
                    ->sum('amount');
                
                // Calculate total withdrawn (all completed withdrawal requests)
                $totalWithdrawn = \App\Models\PayoutRequest::where('user_id', $payoutRequest->user_id)
                    ->whereIn('status', ['completed', 'processing', 'approved'])
                    ->sum('amount');
                
                $studentWallet = [
                    'balance' => $wallet->balance ?? 0,
                    'total_funded' => $totalFunded ?? 0,
                    'total_withdrawn' => $totalWithdrawn ?? 0,
                ];
            }
            
            return Inertia::render('admin/financial/student-withdrawal-details', [
                'withdrawalRequest' => $payoutRequest,
                'studentWallet' => $studentWallet,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error showing student withdrawal details: ' . $e->getMessage(), [
                'withdrawal_id' => $payoutRequest->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.financial.dashboard')
                ->with('error', 'Failed to load withdrawal details.');
        }
    }

    /**
     * Approve a student withdrawal request.
     */
    public function approveStudentWithdrawal(PayoutRequest $payoutRequest)
    {
        try {
            $admin = Auth::user();
            
            // Verify admin is authenticated
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }
            
            // Verify that the request is from a student
            $student = $payoutRequest->user;
            if (!$student || $student->role !== 'student') {
                return response()->json([
                    'success' => false,
                    'message' => 'This is not a student withdrawal request.'
                ], 422);
            }
            
            // Verify that the payout request is still pending
            if ($payoutRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending withdrawal requests can be approved.'
                ], 422);
            }
            
            // Process the approval in a database transaction
            \Illuminate\Support\Facades\DB::transaction(function () use ($payoutRequest, $admin, $student) {
                // Update status to 'approved'
                $payoutRequest->status = 'approved';
                $payoutRequest->processed_by_id = $admin->id;
                $payoutRequest->processed_at = now();
                $payoutRequest->save();
                
                // Create transaction record with student_id
                $transaction = Transaction::create([
                    'user_id' => $student->id,
                    'transaction_type' => 'withdrawal',
                    'description' => 'Student wallet withdrawal - Request #' . $payoutRequest->request_uuid,
                    'amount' => $payoutRequest->amount,
                    'currency' => $payoutRequest->currency ?? 'NGN',
                    'status' => 'completed',
                    'transaction_date' => now()->format('Y-m-d'),
                    'created_by_id' => $admin->id,
                    'metadata' => [
                        'payout_request_id' => $payoutRequest->id,
                        'payment_method' => $payoutRequest->payment_method,
                        'payment_details' => $payoutRequest->payment_details,
                        'approved_by' => $admin->id,
                        'approved_at' => now()->toDateTimeString(),
                    ],
                ]);
                
                // Link transaction to payout request
                $payoutRequest->transaction_id = $transaction->id;
                $payoutRequest->save();
                
                // Log admin action
                \Log::info('Student withdrawal approved', [
                    'payout_request_id' => $payoutRequest->id,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'amount' => $payoutRequest->amount,
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'transaction_id' => $transaction->id,
                ]);
            });
            
            // Send notification to student
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->createNotification(
                    $student,
                    'withdrawal_approved',
                    [
                        'title' => 'Withdrawal Approved',
                        'body' => 'Your withdrawal request of ' . $payoutRequest->currency . ' ' . number_format($payoutRequest->amount, 2) . ' has been approved and will be processed within 1-3 business days.',
                        'action_text' => 'View Details',
                        'action_url' => route('student.wallet.withdrawals.show', $payoutRequest->id),
                        'amount' => $payoutRequest->amount,
                        'currency' => $payoutRequest->currency ?? 'NGN',
                        'request_id' => $payoutRequest->request_uuid,
                    ],
                    'success'
                );
            } catch (\Exception $e) {
                \Log::error('Failed to send withdrawal approval notification', [
                    'payout_request_id' => $payoutRequest->id,
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the approval if notification fails
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Student withdrawal approved successfully!',
                'data' => $payoutRequest->fresh()->load('user')
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Error approving student withdrawal: ' . $e->getMessage(), [
                'payout_request_id' => $payoutRequest->id,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Show detailed error in development, generic in production
            $errorMessage = app()->environment('local', 'development') 
                ? 'Error: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')'
                : 'An error occurred while approving the withdrawal request. Please try again.';
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage
            ], 500);
        }
    }

    /**
     * Update payment method for a payout request.
     */
    public function updatePaymentMethod(Request $request, PayoutRequest $payoutRequest)
    {
        try {
            $validated = $request->validate([
                'payment_method' => 'required|string|in:bank_transfer,debit_credit_card,paypal,mobile_money',
            ]);
            
            $admin = Auth::user();
            
            // Verify admin is authenticated
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }
            
            // Verify that the payout request is still pending
            if ($payoutRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending payout requests can have their payment method updated.'
                ], 422);
            }
            
            // Store old payment method for logging
            $oldPaymentMethod = $payoutRequest->payment_method;
            
            // Update payment method
            $payoutRequest->payment_method = $validated['payment_method'];
            
            // Add note about the change
            $existingNotes = $payoutRequest->notes ? $payoutRequest->notes . "\n\n" : '';
            $payoutRequest->notes = $existingNotes . 
                '[' . now()->format('Y-m-d H:i:s') . '] ' .
                'Payment method changed by ' . $admin->name . 
                ' from ' . $oldPaymentMethod . ' to ' . $validated['payment_method'];
            
            $payoutRequest->save();
            
            // Log the change
            \Log::info('Payout request payment method updated', [
                'payout_request_id' => $payoutRequest->id,
                'old_method' => $oldPaymentMethod,
                'new_method' => $validated['payment_method'],
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment method updated successfully.',
                'data' => $payoutRequest->fresh()
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Error updating payment method: ' . $e->getMessage(), [
                'payout_request_id' => $payoutRequest->id,
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the payment method. Please try again.'
            ], 500);
        }
    }

    /**
     * Reject a student withdrawal request.
     */
    public function rejectStudentWithdrawal(Request $request, PayoutRequest $payoutRequest)
    {
        try {
            // Validate rejection reason
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
            ]);
            
            $admin = Auth::user();
            
            // Verify admin is authenticated
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }
            
            // Verify that the request is from a student
            $student = $payoutRequest->user;
            if (!$student || $student->role !== 'student') {
                return response()->json([
                    'success' => false,
                    'message' => 'This is not a student withdrawal request.'
                ], 422);
            }
            
            // Verify that the payout request is still pending
            if ($payoutRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending withdrawal requests can be rejected.'
                ], 422);
            }
            
            // Process the rejection in a database transaction
            \Illuminate\Support\Facades\DB::transaction(function () use ($payoutRequest, $admin, $student, $validated) {
                // Refund amount to student's wallet_balance
                $student->increment('wallet_balance', $payoutRequest->amount);
                
                // Update status to 'rejected'
                $payoutRequest->status = 'rejected';
                $payoutRequest->processed_by_id = $admin->id;
                $payoutRequest->processed_at = now();
                
                // Log rejection reason in notes
                $existingNotes = $payoutRequest->notes ? $payoutRequest->notes . "\n\n" : '';
                $payoutRequest->notes = $existingNotes . 'Rejected by ' . $admin->name . ' on ' . now()->format('Y-m-d H:i:s') . "\nReason: " . $validated['reason'];
                $payoutRequest->save();
                
                // Log admin action
                \Log::info('Student withdrawal rejected', [
                    'payout_request_id' => $payoutRequest->id,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'amount' => $payoutRequest->amount,
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'reason' => $validated['reason'],
                    'refunded_amount' => $payoutRequest->amount,
                ]);
            });
            
            // Send notification to student with reason
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->createNotification(
                    $student,
                    'withdrawal_rejected',
                    [
                        'title' => 'Withdrawal Request Rejected',
                        'body' => 'Your withdrawal request of ' . $payoutRequest->currency . ' ' . number_format($payoutRequest->amount, 2) . ' has been rejected. The amount has been refunded to your wallet. Reason: ' . $validated['reason'],
                        'action_text' => 'View Wallet',
                        'action_url' => route('student.wallet.index'),
                        'amount' => $payoutRequest->amount,
                        'currency' => $payoutRequest->currency ?? 'NGN',
                        'request_id' => $payoutRequest->request_uuid,
                        'reason' => $validated['reason'],
                    ],
                    'error'
                );
            } catch (\Exception $e) {
                \Log::error('Failed to send withdrawal rejection notification', [
                    'payout_request_id' => $payoutRequest->id,
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the rejection if notification fails
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Student withdrawal rejected and amount refunded to wallet.',
                'data' => $payoutRequest->fresh()->load('user')
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Error rejecting student withdrawal: ' . $e->getMessage(), [
                'payout_request_id' => $payoutRequest->id,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Show detailed error in development, generic in production
            $errorMessage = app()->environment('local', 'development') 
                ? 'Error: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')'
                : 'An error occurred while rejecting the withdrawal request. Please try again.';
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage
            ], 500);
        }
    }

    /**
     * Check the payment gateway status for a payout request.
     */
    public function checkPayoutStatus(PayoutRequest $payoutRequest)
    {
        try {
            $admin = Auth::user();
            
            // Verify admin is authenticated
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }
            
            // Only check status for approved or completed payouts
            if (!in_array($payoutRequest->status, ['approved', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Can only check status for approved or completed payout requests.'
                ], 422);
            }
            
            // Check if payout has external reference (was sent to payment gateway)
            if (!$payoutRequest->external_reference && !$payoutRequest->external_transfer_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payment gateway reference found. Payout may not have been sent to payment gateway yet.'
                ], 422);
            }
            
            // Use PayoutService to verify status with actual payment gateway
            $payoutService = app(\App\Services\PayoutService::class);
            $verificationResult = $payoutService->verifyPayoutStatus($payoutRequest);
            
            if (!$verificationResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $verificationResult['message'] ?? 'Failed to verify payout status with payment gateway.'
                ], 422);
            }
            
            $gatewayStatus = $verificationResult['data'] ?? [];
            $statusChanged = false;
            
            // Map payment gateway status to our internal status
            $mappedStatus = $this->mapGatewayStatus($gatewayStatus['status'] ?? 'unknown');
            
            // Update payout request status if payment is completed
            if ($mappedStatus === 'completed' && $payoutRequest->status !== 'completed') {
                $payoutRequest->status = 'completed';
                $payoutRequest->processed_at = now();
                
                // Add transaction ID and completion note
                $existingNotes = $payoutRequest->notes ? $payoutRequest->notes . "\n\n" : '';
                $payoutRequest->notes = $existingNotes . 
                    '[' . now()->format('Y-m-d H:i:s') . '] ' .
                    'Payment completed via ' . $payoutRequest->payment_method . '. ' .
                    'Gateway Status: ' . ($gatewayStatus['status'] ?? 'completed') . '. ' .
                    'Verified by: ' . $admin->name;
                
                $payoutRequest->save();
                $statusChanged = true;
                
                \Log::info('Payout status updated to completed via gateway verification', [
                    'payout_request_id' => $payoutRequest->id,
                    'gateway_status' => $gatewayStatus['status'] ?? 'completed',
                    'external_reference' => $payoutRequest->external_reference,
                    'admin_id' => $admin->id,
                ]);
            }
            
            // Update status if payment failed
            if ($mappedStatus === 'failed' && $payoutRequest->status !== 'failed') {
                $payoutRequest->status = 'failed';
                
                $existingNotes = $payoutRequest->notes ? $payoutRequest->notes . "\n\n" : '';
                $payoutRequest->notes = $existingNotes . 
                    '[' . now()->format('Y-m-d H:i:s') . '] ' .
                    'Payment failed. Reason: ' . ($gatewayStatus['failure_reason'] ?? 'Unknown') . '. ' .
                    'Verified by: ' . $admin->name;
                
                $payoutRequest->save();
                $statusChanged = true;
                
                \Log::warning('Payout status updated to failed via gateway verification', [
                    'payout_request_id' => $payoutRequest->id,
                    'gateway_status' => $gatewayStatus['status'] ?? 'failed',
                    'failure_reason' => $gatewayStatus['failure_reason'] ?? 'Unknown',
                    'admin_id' => $admin->id,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Payment status retrieved successfully from payment gateway.',
                'data' => [
                    'status' => $mappedStatus,
                    'gateway_status' => $gatewayStatus['status'] ?? 'unknown',
                    'transaction_id' => $gatewayStatus['transaction_id'] ?? $payoutRequest->external_reference,
                    'estimated_completion' => $gatewayStatus['estimated_completion'] ?? null,
                    'failure_reason' => $gatewayStatus['failure_reason'] ?? null,
                    'status_changed' => $statusChanged,
                    'last_checked' => now()->toDateTimeString(),
                    'payment_method' => $payoutRequest->payment_method,
                    'gateway' => $this->getGatewayName($payoutRequest->payment_method),
                ]
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Error checking payout status: ' . $e->getMessage(), [
                'payout_request_id' => $payoutRequest->id,
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking payment status. Please try again.'
            ], 500);
        }
    }

    /**
     * Map payment gateway status to internal status
     */
    private function mapGatewayStatus(string $gatewayStatus): string
    {
        return match(strtolower($gatewayStatus)) {
            'success', 'completed', 'paid', 'settled' => 'completed',
            'failed', 'reversed', 'cancelled' => 'failed',
            'pending', 'processing', 'queued', 'otp' => 'processing',
            default => 'unknown'
        };
    }

    /**
     * Get gateway name for payment method
     */
    private function getGatewayName(string $paymentMethod): string
    {
        return match($paymentMethod) {
            'bank_transfer', 'mobile_money' => 'PayStack',
            'paypal' => 'PayPal',
            'stripe', 'debit_credit_card' => 'Stripe',
            default => 'Unknown'
        };
    }

    /**
     * Send notification to teacher/student about payout
     */
    public function sendPayoutNotification(Request $request, PayoutRequest $payoutRequest)
    {
        try {
            $admin = Auth::user();
            
            // Verify admin is authenticated
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }
            
            // Validate request
            $validated = $request->validate([
                'type' => 'required|string|in:payout_success,reminder,rejected',
                'message' => 'required|string|max:1000',
                'channels' => 'required|array',
                'channels.inApp' => 'boolean',
                'channels.email' => 'boolean',
                'channels.sms' => 'boolean',
                'channels.all' => 'boolean',
                'delivery_time' => 'required|string|in:now,later',
            ]);
            
            // Get the recipient (teacher or student)
            $recipient = $payoutRequest->user;
            
            if (!$recipient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recipient user not found for this payout request.'
                ], 404);
            }
            
            // Determine notification level based on type
            $level = match($validated['type']) {
                'payout_success' => 'success',
                'reminder' => 'warning',
                'rejected' => 'error',
                default => 'info'
            };
            
            // Determine notification title based on type
            $title = match($validated['type']) {
                'payout_success' => 'Payout Approved',
                'reminder' => 'Account Update Reminder',
                'rejected' => 'Payout Request Rejected',
                default => 'Payout Notification'
            };
            
            // Create notification data
            $notificationData = [
                'title' => $title,
                'body' => $validated['message'],
                'payout_request_id' => $payoutRequest->id,
                'payout_amount' => $payoutRequest->amount,
                'currency' => $payoutRequest->currency ?? 'NGN',
                'payment_method' => $payoutRequest->payment_method,
                'notification_type' => $validated['type'],
                'sent_by' => $admin->name,
                'sent_by_id' => $admin->id,
                'action_url' => $recipient->role === 'teacher' 
                    ? route('teacher.financial.payout-requests.show', $payoutRequest->id)
                    : route('student.wallet.index'),
            ];
            
            // Create the notification using NotificationService
            $notification = $this->notificationService->createNotification(
                $recipient,
                'payout_notification',
                $notificationData,
                $level
            );
            
            // Handle email channel if requested
            $emailSent = false;
            if ($validated['channels']['email'] || $validated['channels']['all']) {
                try {
                    // Send email using Laravel Mail
                    \Mail::to($recipient->email)->send(
                        new \App\Mail\PayoutNotificationMail(
                            $recipient,
                            $title,
                            $validated['message'],
                            $payoutRequest,
                            $validated['type']
                        )
                    );
                    $emailSent = true;
                    
                    \Log::info('Email notification sent', [
                        'payout_request_id' => $payoutRequest->id,
                        'recipient_email' => $recipient->email,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to send email notification', [
                        'payout_request_id' => $payoutRequest->id,
                        'recipient_email' => $recipient->email,
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail the whole request if email fails
                }
            }
            
            // Handle SMS channel if requested
            // NOTE: SMS is currently disabled - coming soon
            $smsSent = false;
            if ($validated['channels']['sms'] || $validated['channels']['all']) {
                \Log::info('SMS notification requested but feature is disabled (coming soon)', [
                    'payout_request_id' => $payoutRequest->id,
                    'recipient_id' => $recipient->id,
                    'recipient_phone' => $recipient->phone ?? 'N/A',
                    'message' => 'SMS feature will be implemented when budget allows',
                ]);
                
                // SMS is disabled for now - will be implemented later
                // When ready, uncomment the code below and configure SMS service
                
                /*
                try {
                    if ($recipient->phone) {
                        // Send SMS using SMS service (Termii, Africa's Talking, etc.)
                        $recipient->notify(new \App\Notifications\PayoutSmsNotification(
                            $title,
                            $validated['message'],
                            $payoutRequest
                        ));
                        $smsSent = true;
                        
                        \Log::info('SMS notification sent', [
                            'payout_request_id' => $payoutRequest->id,
                            'recipient_phone' => $recipient->phone,
                        ]);
                    } else {
                        \Log::warning('SMS requested but recipient has no phone number', [
                            'payout_request_id' => $payoutRequest->id,
                            'recipient_id' => $recipient->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to send SMS notification', [
                        'payout_request_id' => $payoutRequest->id,
                        'recipient_phone' => $recipient->phone ?? 'N/A',
                        'error' => $e->getMessage(),
                    ]);
                }
                */
            }
            
            // Log the notification
            \Log::info('Payout notification sent', [
                'payout_request_id' => $payoutRequest->id,
                'recipient_id' => $recipient->id,
                'recipient_name' => $recipient->name,
                'notification_type' => $validated['type'],
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
            ]);
            
            // Build success message based on channels used
            $channelsUsed = [];
            if (true) $channelsUsed[] = 'in-app';
            if ($emailSent) $channelsUsed[] = 'email';
            if ($smsSent) $channelsUsed[] = 'SMS';
            
            $message = 'Notification sent successfully to ' . $recipient->name;
            if (count($channelsUsed) > 0) {
                $message .= ' via ' . implode(', ', $channelsUsed);
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'notification_id' => $notification->id,
                    'recipient' => [
                        'id' => $recipient->id,
                        'name' => $recipient->name,
                        'email' => $recipient->email,
                    ],
                    'channels_used' => [
                        'in_app' => true,
                        'email' => $emailSent,
                        'sms' => $smsSent,
                    ],
                ]
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Error sending payout notification: ' . $e->getMessage(), [
                'payout_request_id' => $payoutRequest->id,
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending the notification. Please try again.'
            ], 500);
        }
    }

    /**
     * Update payment settings.
     */
    public function updatePaymentSettings(Request $request)
    {
        try {
            $validated = $request->validate([
                'commission_rate' => 'required|numeric|min:0|max:100',
                'commission_type' => 'required|in:fixed_percentage,tiered',
                'auto_payout_threshold' => 'required|numeric|min:0',
                'minimum_withdrawal_amount' => 'required|numeric|min:0',
                'bank_verification_enabled' => 'required|boolean',
                'withdrawal_note' => 'nullable|string|max:500',
                'apply_time' => 'required|in:now,scheduled',
                'scheduled_date' => 'required_if:apply_time,scheduled|nullable|date|after:now',
            ]);

            $admin = Auth::user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }

            // Update settings
            FinancialSetting::set('commission_rate', $validated['commission_rate']);
            FinancialSetting::set('commission_type', $validated['commission_type']);
            FinancialSetting::set('auto_payout_threshold', $validated['auto_payout_threshold']);
            FinancialSetting::set('minimum_withdrawal_amount', $validated['minimum_withdrawal_amount']);
            FinancialSetting::set('bank_verification_enabled', $validated['bank_verification_enabled'] ? 'true' : 'false');
            FinancialSetting::set('withdrawal_note', $validated['withdrawal_note'] ?? '');

            // Log the change
            \Log::info('Payment settings updated', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'settings' => $validated,
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment settings updated successfully!',
                'data' => [
                    'commission_rate' => (float) $validated['commission_rate'],
                    'commission_type' => $validated['commission_type'],
                    'auto_payout_threshold' => (float) $validated['auto_payout_threshold'],
                    'minimum_withdrawal_amount' => (float) $validated['minimum_withdrawal_amount'],
                    'bank_verification_enabled' => $validated['bank_verification_enabled'],
                    'withdrawal_note' => $validated['withdrawal_note'] ?? '',
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Error updating payment settings: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating payment settings. Please try again.'
            ], 500);
        }
    }

    /**
     * Update withdrawal limits settings.
     */
    public function updateWithdrawalLimits(Request $request)
    {
        try {
            $validated = $request->validate([
                'daily_withdrawal_limit' => 'required|numeric|min:0',
                'monthly_withdrawal_limit' => 'required|numeric|min:0',
                'instant_payouts_enabled' => 'required|boolean',
            ]);

            $admin = Auth::user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }

            // Update settings
            FinancialSetting::set('daily_withdrawal_limit', $validated['daily_withdrawal_limit']);
            FinancialSetting::set('monthly_withdrawal_limit', $validated['monthly_withdrawal_limit']);
            FinancialSetting::set('instant_payouts_enabled', $validated['instant_payouts_enabled'] ? 'true' : 'false');

            // Log the change
            \Log::info('Withdrawal limits updated', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'settings' => $validated,
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal limits updated successfully!',
                'data' => $validated
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Error updating withdrawal limits: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating withdrawal limits. Please try again.'
            ], 500);
        }
    }

    /**
     * Update payment methods settings.
     */
    public function updatePaymentMethods(Request $request)
    {
        try {
            $validated = $request->validate([
                'bank_transfer_fee_type' => 'required|in:flat,percentage',
                'bank_transfer_fee_amount' => 'required|numeric|min:0',
                'bank_transfer_processing_time' => 'required|string',
                'mobile_money_fee_type' => 'required|in:flat,percentage',
                'mobile_money_fee_amount' => 'required|numeric|min:0',
                'mobile_money_processing_time' => 'required|string',
                'paypal_fee_type' => 'required|in:flat,percentage',
                'paypal_fee_amount' => 'required|numeric|min:0',
                'paypal_processing_time' => 'required|string',
                'flutterwave_fee_type' => 'required|in:flat,percentage',
                'flutterwave_fee_amount' => 'required|numeric|min:0',
                'flutterwave_processing_time' => 'required|string',
                'paystack_fee_type' => 'required|in:flat,percentage',
                'paystack_fee_amount' => 'required|numeric|min:0',
                'paystack_processing_time' => 'required|string',
                'stripe_fee_type' => 'required|in:flat,percentage',
                'stripe_fee_amount' => 'required|numeric|min:0',
                'stripe_processing_time' => 'required|string',
            ]);

            $admin = Auth::user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }

            // Update all payment method settings
            foreach ($validated as $key => $value) {
                FinancialSetting::set($key, $value);
            }

            // Log the change
            \Log::info('Payment methods updated', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment methods updated successfully!',
                'data' => $validated
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Error updating payment methods: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating payment methods. Please try again.'
            ], 500);
        }
    }

    /**
     * Update currency settings.
     */
    public function updateCurrencySettings(Request $request)
    {
        try {
            $validated = $request->validate([
                'platform_currency' => 'required|string|in:NGN,USD,EUR,GBP',
                'multi_currency_mode' => 'required|boolean',
            ]);

            $admin = Auth::user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 401);
            }

            // Update settings
            FinancialSetting::set('platform_currency', $validated['platform_currency']);
            FinancialSetting::set('multi_currency_mode', $validated['multi_currency_mode'] ? 'true' : 'false');

            // Log the change
            \Log::info('Currency settings updated', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'settings' => $validated,
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Currency settings updated successfully!',
                'data' => $validated
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Error updating currency settings: ' . $e->getMessage(), [
                'admin_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating currency settings. Please try again.'
            ], 500);
        }
    }
} 
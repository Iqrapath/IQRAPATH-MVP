<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\Transaction;
use App\Services\FinancialService;
use App\Services\CurrencyService;
use App\Services\WalletSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FinancialController extends Controller
{
    protected $financialService;
    protected $currencyService;
    protected $walletSyncService;

    public function __construct(
        FinancialService $financialService,
        CurrencyService $currencyService,
        WalletSyncService $walletSyncService
    ) {
        $this->financialService = $financialService;
        $this->currencyService = $currencyService;
        $this->walletSyncService = $walletSyncService;
    }

    /**
     * Display the teacher's financial dashboard.
     */
    public function index()
    {
        $teacher = Auth::user();
        
        // Verify that the user is a teacher
        if (!$teacher->isTeacher()) {
            return redirect()->route('dashboard')->with('error', 'You do not have access to this section.');
        }
        
        // Get or create teacher wallet (primary source of truth)
        $teacherWallet = $teacher->teacherWallet;
        if (!$teacherWallet) {
            // Create wallet if it doesn't exist with zero values
            $teacherWallet = \App\Models\TeacherWallet::create([
                'user_id' => $teacher->id,
                'balance' => 0.00,
                'total_earned' => 0.00,
                'total_withdrawn' => 0.00,
                'pending_payouts' => 0.00,
                'withdrawal_settings' => ['preferred_currency' => 'NGN'],
                'auto_withdrawal_enabled' => false,
            ]);
        }
        
        // Get teacher profile for rates
        $teacherProfile = $teacher->teacherProfile;
        $hourlyRateUSD = $teacherProfile?->hourly_rate_usd ?? 0;
        $hourlyRateNGN = $teacherProfile?->hourly_rate_ngn ?? 0;
        
        // Get preferred currency from teacher profile or platform default
        $preferredCurrency = $this->currencyService->getTeacherPreferredCurrency($teacher->id);
        
        // Read current wallet values directly (no sync on page load for performance)
        // Wallet is synced after transactions via background jobs
        $walletBalance = $teacherWallet->balance;
        $totalEarned = $teacherWallet->total_earned;
        $pendingPayouts = $teacherWallet->pending_payouts;
        
        // Get upcoming earnings from scheduled sessions (only if teacher has rates set)
        $upcomingEarnings = [];
        if ($hourlyRateUSD > 0 || $hourlyRateNGN > 0) {
            $upcomingEarnings = \App\Models\TeachingSession::where('teacher_id', $teacher->id)
                ->where('status', 'scheduled')
                ->where('session_date', '>=', now())
                ->with(['student', 'subject'])
                ->orderBy('session_date', 'asc')
                ->take(5)
                ->get()
                ->map(function ($session) use ($hourlyRateUSD, $hourlyRateNGN, $preferredCurrency) {
                    // Calculate session duration in hours
                    $startTime = \Carbon\Carbon::parse($session->start_time);
                    $endTime = \Carbon\Carbon::parse($session->end_time);
                    $durationHours = $startTime->diffInHours($endTime);
                    
                    // Calculate amounts based on teacher's rates
                    $amountUSD = $hourlyRateUSD * $durationHours;
                    $amountNGN = $hourlyRateNGN * $durationHours;
                    
                    // Use preferred currency as primary amount
                    $primaryAmount = $preferredCurrency === 'USD' ? $amountUSD : $amountNGN;
                    $secondaryAmount = $preferredCurrency === 'USD' ? $amountNGN : $amountUSD;
                    $secondaryCurrency = $preferredCurrency === 'USD' ? 'NGN' : 'USD';
                    
                    return [
                        'id' => $session->id,
                        'amount' => round($primaryAmount, 2),
                        'amountSecondary' => round($secondaryAmount, 2),
                        'currency' => $preferredCurrency,
                        'secondaryCurrency' => $secondaryCurrency,
                        'studentName' => $session->student->name,
                        'subject' => $session->subject->name ?? 'General Class',
                        'dueDate' => $session->session_date->format('jS F Y'),
                        'status' => 'pending'
                    ];
                });
        }
        
        // Get recent EARNING transactions only (not withdrawals)
        $recentTransactions = [];
        
        // First try unified transactions - ONLY earnings from teaching sessions
        $unifiedTransactions = \App\Models\UnifiedTransaction::where('wallet_type', 'App\\Models\\TeacherWallet')
            ->where('wallet_id', $teacherWallet->id)
            ->whereIn('transaction_type', ['credit', 'session_payment'])  // Credits and session payments are earnings
            ->whereNotNull('session_id')  // Must have a session
            ->with(['session.student', 'session.subject'])
            ->orderBy('transaction_date', 'desc')
            ->take(10)
            ->get();
        
        if ($unifiedTransactions->count() > 0) {
            $recentTransactions = $unifiedTransactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->transaction_date->format('j M'),
                    'subject' => $transaction->session->subject->name ?? 'General Class',
                    'studentName' => $transaction->session->student->name ?? 'Unknown Student',
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'status' => $transaction->status,
                    'type' => $transaction->transaction_type
                ];
            });
        } else {
            // Fallback to old transactions table - only earnings
            $oldTransactions = \App\Models\Transaction::where('teacher_id', $teacher->id)
                ->where('transaction_type', 'earning')  // Only earnings
                ->whereNotNull('session_id')  // Must have a session
                ->with(['session.student', 'session.subject'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
                
            $recentTransactions = $oldTransactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->created_at->format('j M'),
                    'subject' => $transaction->session->subject->name ?? 'General Class',
                    'studentName' => $transaction->session->student->name ?? 'Unknown Student',
                    'amount' => $transaction->amount,
                    'currency' => 'NGN', // Default currency for old transactions
                    'status' => $transaction->status,
                    'type' => $transaction->transaction_type
                ];
            });
        }
        
        // No mock data - only use real transactions from database
        
        // Get pending payout requests
        $pendingPayoutRequests = $teacher->payoutRequests()
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get available currencies from CurrencyService
        $availableCurrencies = $this->currencyService->getAvailableCurrencies();

        // Get teacher's payment methods
        $paymentMethods = $teacher->paymentMethods()
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Debug logging
        \Log::info('Teacher Earnings Page Data', [
            'teacher_id' => $teacher->id,
            'teacher_name' => $teacher->name,
            'hourly_rate_usd' => $hourlyRateUSD,
            'hourly_rate_ngn' => $hourlyRateNGN,
            'upcoming_earnings_count' => count($upcomingEarnings),
            'upcoming_earnings_data' => $upcomingEarnings,
        ]);

        return Inertia::render('teacher/earnings/index', [
            'walletBalance' => $walletBalance,
            'totalEarned' => $totalEarned,
            'pendingPayouts' => $pendingPayouts,
            'recentTransactions' => $recentTransactions,
            'pendingPayoutRequests' => $pendingPayoutRequests,
            'upcomingEarnings' => $upcomingEarnings,
            'paymentMethods' => $paymentMethods,
            'earningsSettings' => [
                'preferredCurrency' => $preferredCurrency,
                'automaticPayouts' => $teacherWallet->auto_withdrawal_enabled
            ],
            'availableCurrencies' => $availableCurrencies,
            'teacherRates' => [
                'hourlyRateUSD' => $hourlyRateUSD,
                'hourlyRateNGN' => $hourlyRateNGN
            ]
        ]);
    }

    /**
     * Display the teacher's transaction history.
     */
    public function transactions(Request $request)
    {
        $teacher = Auth::user();
        
        // Verify that the user is a teacher
        if (!$teacher->isTeacher()) {
            return redirect()->route('dashboard')->with('error', 'You do not have access to this section.');
        }
        
        $query = Transaction::where('teacher_id', $teacher->id);
        
        // Apply filters
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
        
        $transactions = $query->paginate(10);
        
        return Inertia::render('Teacher/Financial/Transactions', [
            'transactions' => $transactions,
            'filters' => $request->only(['type', 'status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Display the teacher's transaction history with advanced filtering.
     */
    public function history(Request $request)
    {
        $teacher = Auth::user();
        
        // Verify that the user is a teacher
        if (!$teacher->isTeacher()) {
            return redirect()->route('dashboard')->with('error', 'You do not have access to this section.');
        }
        
        $teacherWallet = $teacher->teacherWallet;
        
        if (!$teacherWallet) {
            return Inertia::render('teacher/earnings/history', [
                'transactions' => [],
                'filters' => $request->only(['type', 'status', 'date_from', 'date_to', 'currency']),
                'availableCurrencies' => [],
                'transactionTypes' => [],
                'statuses' => [],
            ]);
        }
        
        // Build query for unified transactions
        $query = \App\Models\UnifiedTransaction::where('wallet_type', 'App\\Models\\TeacherWallet')
            ->where('wallet_id', $teacherWallet->id)
            ->with(['session.student', 'session.subject']);
        
        // Apply filters
        if ($request->has('type') && $request->type !== '') {
            $query->where('transaction_type', $request->type);
        }
        
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        
        if ($request->has('currency') && $request->currency !== '') {
            $query->where('currency', $request->currency);
        }
        
        if ($request->has('date_from') && $request->date_from !== '') {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && $request->date_to !== '') {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }
        
        // Sort transactions
        $query->orderBy('transaction_date', 'desc')
              ->orderBy('created_at', 'desc');
        
        $transactions = $query->paginate(20);
        
        // Get available currencies from transactions
        $availableCurrencies = \App\Models\UnifiedTransaction::where('wallet_type', 'App\\Models\\TeacherWallet')
            ->where('wallet_id', $teacherWallet->id)
            ->select('currency')
            ->distinct()
            ->whereNotNull('currency')
            ->pluck('currency')
            ->toArray();
        
        // Get available transaction types
        $transactionTypes = \App\Models\UnifiedTransaction::where('wallet_type', 'App\\Models\\TeacherWallet')
            ->where('wallet_id', $teacherWallet->id)
            ->select('transaction_type')
            ->distinct()
            ->pluck('transaction_type')
            ->toArray();
        
        // Get available statuses
        $statuses = \App\Models\UnifiedTransaction::where('wallet_type', 'App\\Models\\TeacherWallet')
            ->where('wallet_id', $teacherWallet->id)
            ->select('status')
            ->distinct()
            ->pluck('status')
            ->toArray();
        
        return Inertia::render('teacher/earnings/history', [
            'transactions' => $transactions,
            'filters' => $request->only(['type', 'status', 'date_from', 'date_to', 'currency']),
            'availableCurrencies' => $availableCurrencies,
            'transactionTypes' => $transactionTypes,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Display the teacher's payout requests.
     */
    public function payoutRequests(Request $request)
    {
        $teacher = Auth::user();
        
        // Verify that the user is a teacher
        if (!$teacher->isTeacher()) {
            return redirect()->route('dashboard')->with('error', 'You do not have access to this section.');
        }
        
        $query = PayoutRequest::where('user_id', $teacher->id);
        
        // Apply filters
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
        
        $payoutRequests = $query->paginate(10);
        
        return Inertia::render('Teacher/Financial/PayoutRequests', [
            'payoutRequests' => $payoutRequests,
            'filters' => $request->only(['status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show the form for creating a new payout request.
     */
    public function createPayoutRequest()
    {
        $teacher = Auth::user();
        
        // Verify that the user is a teacher
        if (!$teacher->isTeacher()) {
            return redirect()->route('dashboard')->with('error', 'You do not have access to this section.');
        }
        $earnings = $teacher->earnings;
        
        if (!$earnings) {
            return redirect()->route('teacher.financial.dashboard')
                ->with('error', 'You do not have any earnings yet.');
        }
        
        $availableBalance = $earnings->wallet_balance;
        
        return Inertia::render('Teacher/Financial/CreatePayoutRequest', [
            'availableBalance' => $availableBalance,
            'paymentMethods' => [
                'bank_transfer' => 'Bank Transfer',
                'paypal' => 'PayPal',
                'mobile_money' => 'Mobile Money',
            ],
        ]);
    }

    /**
     * Store a newly created payout request.
     */
    public function storePayoutRequest(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'payment_method' => 'required|in:bank_transfer,paypal,mobile_money',
            'payment_details' => 'required|array',
        ]);
        
        $teacher = Auth::user();
        
        // Verify that the user is a teacher
        if (!$teacher->isTeacher()) {
            return redirect()->route('dashboard')->with('error', 'You do not have access to this section.');
        }
        $earnings = $teacher->earnings;
        
        if (!$earnings || $earnings->wallet_balance < $validated['amount']) {
            return redirect()->back()->with('error', 'Insufficient balance for this payout request.');
        }
        
        // Create payout request
        $payoutRequest = PayoutRequest::create([
            'teacher_id' => $teacher->id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_details' => $validated['payment_details'],
            'status' => 'pending',
            'request_date' => now(),
        ]);
        
        return redirect()->route('teacher.financial.payout-requests')
            ->with('success', 'Payout request created successfully.');
    }

    /**
     * Cancel a pending payout request.
     */
    public function cancelPayoutRequest(PayoutRequest $payoutRequest)
    {
        $teacher = Auth::user();
        
        // Verify that the user is a teacher
        if (!$teacher->isTeacher()) {
            return redirect()->route('dashboard')->with('error', 'You do not have access to this section.');
        }
        
        // Verify that the payout request belongs to the teacher
        if ($payoutRequest->user_id !== $teacher->id) {
            return redirect()->back()->with('error', 'You do not have permission to cancel this payout request.');
        }
        
        // Verify that the payout request is still pending
        if ($payoutRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending payout requests can be cancelled.');
        }
        
        // Update payout request status
        $payoutRequest->status = 'declined';
        $payoutRequest->notes = 'Cancelled by teacher';
        $payoutRequest->save();
        
        // Update teacher earnings
        $earnings = $teacher->earnings;
        if ($earnings) {
            $earnings->removePendingPayout($payoutRequest->amount);
        }
        
        return redirect()->back()->with('success', 'Payout request cancelled successfully.');
    }

    /**
     * Show transaction details.
     */
    public function showTransaction(Transaction $transaction)
    {
        $teacher = Auth::user();
        
        // Verify that the user is a teacher
        if (!$teacher->isTeacher()) {
            return redirect()->route('dashboard')->with('error', 'You do not have access to this section.');
        }
        
        // Verify that the transaction belongs to the teacher
        if ($transaction->teacher_id !== $teacher->id) {
            return redirect()->back()->with('error', 'You do not have permission to view this transaction.');
        }
        
        return Inertia::render('Teacher/Financial/TransactionDetails', [
            'transaction' => $transaction->load(['session', 'createdBy']),
        ]);
    }

    /**
     * Show payout request details.
     */
    public function showPayoutRequest(PayoutRequest $payoutRequest)
    {
        $teacher = Auth::user();
        
        // Verify that the user is a teacher
        if (!$teacher->isTeacher()) {
            return redirect()->route('dashboard')->with('error', 'You do not have access to this section.');
        }
        
        // Verify that the payout request belongs to the teacher
        if ($payoutRequest->user_id !== $teacher->id) {
            return redirect()->back()->with('error', 'You do not have permission to view this payout request.');
        }
        
        return Inertia::render('Teacher/Financial/PayoutRequestDetails', [
            'payoutRequest' => $payoutRequest->load(['processedBy', 'transaction']),
        ]);
    }

    /**
     * Manually sync wallet balance from transactions.
     */
    public function syncWallet(Request $request)
    {
        try {
            $teacher = Auth::user();

            // Use WalletSyncService for consistent syncing
            $walletData = $this->walletSyncService->syncTeacherWallet($teacher);

            return response()->json([
                'success' => true,
                'message' => 'Balance refreshed successfully',
                'data' => [
                    'balance' => $walletData['balance'],
                    'totalEarned' => $walletData['total_earned'],
                    'pendingPayouts' => $walletData['pending_payouts']
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to sync wallet', [
                'teacher_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh balance. Please try again.'
            ], 500);
        }
    }

    /**
     * Save earnings settings (preferred currency and automatic payouts).
     */
    public function saveSettings(Request $request)
    {
        try {
            // Accept both camelCase and snake_case for flexibility
            $validated = $request->validate([
                'preferred_currency' => 'required|string|in:NGN,USD,EUR,GBP',
                'automatic_payouts' => 'required|boolean'
            ]);

            $teacher = Auth::user();
            $teacherWallet = $teacher->teacherWallet;

            if (!$teacherWallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            // Update wallet settings
            $withdrawalSettings = $teacherWallet->withdrawal_settings ?? [];
            $withdrawalSettings['preferred_currency'] = $validated['preferred_currency'];

            $teacherWallet->update([
                'auto_withdrawal_enabled' => $validated['automatic_payouts'],
                'withdrawal_settings' => $withdrawalSettings
            ]);

            // Also update teacher profile if it exists
            if ($teacher->teacherProfile) {
                $teacher->teacherProfile->update([
                    'preferred_currency' => $validated['preferred_currency']
                ]);
            }

            \Illuminate\Support\Facades\Log::info('Earnings settings updated', [
                'teacher_id' => $teacher->id,
                'preferred_currency' => $validated['preferred_currency'],
                'automatic_payouts' => $validated['automatic_payouts']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully'
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to save earnings settings', [
                'teacher_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings. Please try again.'
            ], 500);
        }
    }

    /**
     * Request a payout
     */
    public function requestPayout(Request $request)
    {
        try {
            $teacher = Auth::user();
            $teacherWallet = $teacher->teacherWallet;

            if (!$teacherWallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found'
                ], 404);
            }

            // Get teacher's preferred currency from teacher profile
            $teacherProfile = $teacher->teacherProfile;
            $preferredCurrency = $teacherProfile ? $teacherProfile->preferred_currency : 'NGN';

            // Set minimum withdrawal based on currency
            $minWithdrawal = $preferredCurrency === 'NGN' ? 5000 : 10;
            
            // Format minimum for display
            $minFormatted = $preferredCurrency === 'NGN' 
                ? 'NGN ' . number_format($minWithdrawal)
                : $preferredCurrency . ' ' . number_format($minWithdrawal);

            // Log the request for debugging
            \Illuminate\Support\Facades\Log::info('Payout request received', [
                'teacher_id' => $teacher->id,
                'amount' => $request->input('amount'),
                'preferred_currency' => $preferredCurrency,
                'min_withdrawal' => $minWithdrawal
            ]);

            $validated = $request->validate([
                'amount' => "required|numeric|min:{$minWithdrawal}",
                'payment_method_id' => 'required|exists:payment_methods,id',
                'notes' => 'nullable|string|max:500'
            ], [
                'amount.min' => "Minimum withdrawal amount is {$minFormatted}",
                'amount.required' => 'Please enter a withdrawal amount',
                'amount.numeric' => 'Amount must be a valid number',
                'payment_method_id.required' => 'Please select a payment method',
                'payment_method_id.exists' => 'Invalid payment method selected'
            ]);

            // Check for existing pending payout requests
            $existingPendingCount = PayoutRequest::where('user_id', $teacher->id)
                ->where('status', 'pending')
                ->count();

            if ($existingPendingCount >= 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have too many pending payout requests. Please wait for them to be processed.'
                ], 400);
            }

            // Check if amount is available (with buffer for safety)
            if ($validated['amount'] > $teacherWallet->balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance. Available: ' . $teacherWallet->getFormattedBalanceAttribute()
                ], 400);
            }

            // Additional safety check: ensure balance won't go negative
            if (($teacherWallet->balance - $validated['amount']) < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Withdrawal would result in negative balance'
                ], 400);
            }

            // Verify payment method belongs to teacher and is verified
            $paymentMethod = \App\Models\PaymentMethod::where('id', $validated['payment_method_id'])
                ->where('user_id', $teacher->id)
                ->where('is_active', true)
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment method'
                ], 400);
            }

            if (!$paymentMethod->is_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment method must be verified before withdrawal'
                ], 400);
            }

            // Create payout request with database locking to prevent race conditions
            \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $teacher, $teacherWallet, $paymentMethod) {
                // Lock the wallet row to prevent concurrent modifications
                $lockedWallet = \App\Models\TeacherWallet::where('id', $teacherWallet->id)
                    ->lockForUpdate()
                    ->first();

                // Double-check balance after lock (in case of concurrent requests)
                if ($lockedWallet->balance < $validated['amount']) {
                    throw new \Exception('Insufficient balance after lock check');
                }

                // Create payout request
                $payout = PayoutRequest::create([
                    'teacher_id' => $teacher->id,
                    'amount' => $validated['amount'],
                    'payment_method' => $paymentMethod->type,
                    'payment_details' => [
                        'payment_method_id' => $paymentMethod->id,
                        'bank_name' => $paymentMethod->bank_name,
                        'account_name' => $paymentMethod->account_name,
                        'last_four' => $paymentMethod->last_four,
                    ],
                    'status' => 'pending',
                    'request_date' => now(),
                    'notes' => $validated['notes']
                ]);

                // Update wallet: move from available balance to pending payouts
                $lockedWallet->balance -= $validated['amount'];
                $lockedWallet->pending_payouts += $validated['amount'];
                $lockedWallet->save();

                // Sync with earnings table
                $lockedWallet->syncWithTeacherEarning();

                \Illuminate\Support\Facades\Log::info('Payout request created', [
                    'teacher_id' => $teacher->id,
                    'payout_id' => $payout->id,
                    'amount' => $validated['amount'],
                    'payment_method_id' => $paymentMethod->id,
                    'new_balance' => $lockedWallet->balance,
                    'new_pending_payouts' => $lockedWallet->pending_payouts,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Payout request submitted successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Let Laravel handle validation errors automatically
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Payout request failed', [
                'teacher_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to request payout. Please try again.'
            ], 500);
        }
    }

    /**
     * Sync financial data between different tables to ensure consistency.
     * Only uses real transaction data from the database.
     */
    private function syncFinancialData($teacher, $teacherWallet)
    {
        // Calculate totals from unified transactions (real data only)
        $unifiedTransactions = \App\Models\UnifiedTransaction::where('wallet_type', 'App\\Models\\TeacherWallet')
            ->where('wallet_id', $teacherWallet->id)
            ->get();

        $totalEarned = $unifiedTransactions
            ->whereIn('transaction_type', ['session_payment', 'credit', 'bonus', 'refund'])
            ->where('status', 'completed')
            ->sum('amount');

        $totalWithdrawn = $unifiedTransactions
            ->whereIn('transaction_type', ['withdrawal', 'debit'])
            ->where('status', 'completed')
            ->sum('amount');

        $pendingPayouts = $unifiedTransactions
            ->whereIn('transaction_type', ['withdrawal'])
            ->where('status', 'pending')
            ->sum('amount');

        $currentBalance = $totalEarned - $totalWithdrawn;

        // Update wallet with calculated values from real transactions only
        $teacherWallet->update([
            'total_earned' => $totalEarned,
            'total_withdrawn' => $totalWithdrawn,
            'pending_payouts' => $pendingPayouts,
            'balance' => $currentBalance,
            'last_sync_at' => now(),
        ]);

        // Also sync with teacher_earnings table for backward compatibility
        \App\Models\TeacherEarning::updateOrCreate(
            ['teacher_id' => $teacher->id],
            [
                'wallet_balance' => $currentBalance,
                'total_earned' => $totalEarned,
                'total_withdrawn' => $totalWithdrawn,
                'pending_payouts' => $pendingPayouts,
            ]
        );
    }

    /**
     * Email activity report to teacher
     */
    public function emailActivityReport(Request $request)
    {
        try {
            $teacher = Auth::user();
            
            if (!$teacher->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'No email address found'
                ], 400);
            }

            // Get recent transactions (last 30 days)
            $recentTransactions = \App\Models\UnifiedTransaction::where('wallet_type', 'App\\Models\\TeacherWallet')
                ->where('wallet_id', $teacher->teacherWallet->id)
                ->where('created_at', '>=', now()->subDays(30))
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'date' => $transaction->created_at->format('M d, Y'),
                        'description' => $transaction->description ?? ucfirst(str_replace('_', ' ', $transaction->transaction_type)),
                        'amount' => $transaction->amount,
                        'type' => $transaction->transaction_type,
                        'status' => $transaction->status,
                    ];
                });

            // Get earnings summary
            $teacherWallet = $teacher->teacherWallet;
            $summary = [
                'total_earned' => $teacherWallet->total_earned ?? 0,
                'current_balance' => $teacherWallet->balance ?? 0,
                'pending_payouts' => $teacherWallet->pending_payouts ?? 0,
                'total_withdrawn' => $teacherWallet->total_withdrawn ?? 0,
            ];

            // Send email
            \Illuminate\Support\Facades\Mail::to($teacher->email)->send(
                new \App\Mail\Teacher\EarningsActivityReport($teacher, $summary, $recentTransactions->toArray())
            );

            \Illuminate\Support\Facades\Log::info('Activity report emailed', [
                'teacher_id' => $teacher->id,
                'email' => $teacher->email,
                'transactions_count' => $recentTransactions->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Activity report sent to your email'
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to email activity report', [
                'teacher_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send activity report. Please try again.'
            ], 500);
        }
    }
}
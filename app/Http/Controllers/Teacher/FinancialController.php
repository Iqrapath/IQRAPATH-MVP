<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\Transaction;
use App\Services\FinancialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FinancialController extends Controller
{
    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isTeacher()) {
                return redirect()->route('dashboard')->with('error', 'You do not have access to this section.');
            }
            return $next($request);
        });
    }

    /**
     * Display the teacher's financial dashboard.
     */
    public function index()
    {
        $teacher = Auth::user();
        $financialSummary = $this->financialService->getTeacherFinancialSummary($teacher);

        return Inertia::render('Teacher/Financial/Dashboard', [
            'walletBalance' => $financialSummary['wallet_balance'],
            'totalEarned' => $financialSummary['total_earned'],
            'pendingPayouts' => $financialSummary['pending_payouts'],
            'recentTransactions' => $financialSummary['recent_transactions'],
            'pendingPayoutRequests' => $financialSummary['pending_payout_requests'],
        ]);
    }

    /**
     * Display the teacher's transaction history.
     */
    public function transactions(Request $request)
    {
        $teacher = Auth::user();
        
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
     * Display the teacher's payout requests.
     */
    public function payoutRequests(Request $request)
    {
        $teacher = Auth::user();
        
        $query = PayoutRequest::where('teacher_id', $teacher->id);
        
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
        
        // Verify that the payout request belongs to the teacher
        if ($payoutRequest->teacher_id !== $teacher->id) {
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
        
        // Verify that the payout request belongs to the teacher
        if ($payoutRequest->teacher_id !== $teacher->id) {
            return redirect()->back()->with('error', 'You do not have permission to view this payout request.');
        }
        
        return Inertia::render('Teacher/Financial/PayoutRequestDetails', [
            'payoutRequest' => $payoutRequest->load(['processedBy', 'transaction']),
        ]);
    }
} 
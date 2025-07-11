<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\TeacherEarning;
use App\Models\Transaction;
use App\Models\User;
use App\Services\FinancialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FinancialManagementController extends Controller
{
    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
        $this->middleware(['auth', 'role:super-admin']);
    }

    /**
     * Display the financial management dashboard.
     */
    public function index()
    {
        // Get summary statistics
        $totalTeachers = User::where('role', 'teacher')->count();
        $totalEarnings = TeacherEarning::sum('total_earned');
        $pendingPayouts = PayoutRequest::where('status', 'pending')->count();
        $pendingPayoutsAmount = PayoutRequest::where('status', 'pending')->sum('amount');
        
        // Get recent transactions
        $recentTransactions = Transaction::with(['teacher'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
            
        // Get pending payout requests
        $pendingPayoutRequests = PayoutRequest::with(['teacher'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return Inertia::render('Admin/Financial/Dashboard', [
            'totalTeachers' => $totalTeachers,
            'totalEarnings' => $totalEarnings,
            'pendingPayouts' => $pendingPayouts,
            'pendingPayoutsAmount' => $pendingPayoutsAmount,
            'recentTransactions' => $recentTransactions,
            'pendingPayoutRequests' => $pendingPayoutRequests,
        ]);
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
        
        return Inertia::render('Admin/Financial/Transactions', [
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
        $query = PayoutRequest::with(['teacher']);
        
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
        
        return Inertia::render('Admin/Financial/PayoutRequests', [
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
        return Inertia::render('Admin/Financial/TransactionDetails', [
            'transaction' => $transaction->load(['teacher', 'session', 'createdBy']),
        ]);
    }

    /**
     * Show payout request details.
     */
    public function showPayoutRequest(PayoutRequest $payoutRequest)
    {
        return Inertia::render('Admin/Financial/PayoutRequestDetails', [
            'payoutRequest' => $payoutRequest->load(['teacher', 'processedBy', 'transaction']),
        ]);
    }

    /**
     * Approve a payout request.
     */
    public function approvePayoutRequest(PayoutRequest $payoutRequest)
    {
        $admin = Auth::user();
        
        // Verify that the payout request is still pending
        if ($payoutRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending payout requests can be approved.');
        }
        
        // Approve the payout request
        $payoutRequest->approve($admin);
        
        return redirect()->back()->with('success', 'Payout request approved successfully.');
    }

    /**
     * Decline a payout request.
     */
    public function declinePayoutRequest(Request $request, PayoutRequest $payoutRequest)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);
        
        $admin = Auth::user();
        
        // Verify that the payout request is still pending
        if ($payoutRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending payout requests can be declined.');
        }
        
        // Decline the payout request
        $payoutRequest->decline($admin, $validated['reason']);
        
        return redirect()->back()->with('success', 'Payout request declined successfully.');
    }

    /**
     * Mark a payout request as paid.
     */
    public function markPayoutRequestAsPaid(PayoutRequest $payoutRequest)
    {
        $admin = Auth::user();
        
        // Verify that the payout request is approved
        if ($payoutRequest->status !== 'approved') {
            return redirect()->back()->with('error', 'Only approved payout requests can be marked as paid.');
        }
        
        // Mark the payout request as paid
        $payoutRequest->markAsPaid($admin);
        
        return redirect()->back()->with('success', 'Payout request marked as paid successfully.');
    }

    /**
     * Show form to create a system adjustment.
     */
    public function createSystemAdjustment()
    {
        // Get teachers for dropdown
        $teachers = User::where('role', 'teacher')->get(['id', 'name']);
        
        return Inertia::render('Admin/Financial/CreateSystemAdjustment', [
            'teachers' => $teachers,
        ]);
    }

    /**
     * Store a system adjustment.
     */
    public function storeSystemAdjustment(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|not_in:0',
            'reason' => 'required|string|max:255',
        ]);
        
        $admin = Auth::user();
        $teacher = User::findOrFail($validated['teacher_id']);
        
        // Create the system adjustment
        $this->financialService->createSystemAdjustment(
            $teacher, 
            $validated['amount'], 
            $validated['reason'], 
            $admin
        );
        
        return redirect()->route('admin.financial.transactions')
            ->with('success', 'System adjustment created successfully.');
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
        
        return Inertia::render('Admin/Financial/CreateRefund', [
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
        
        return Inertia::render('Admin/Financial/TeacherEarnings', [
            'earnings' => $earnings,
            'filters' => $request->only(['search']),
        ]);
    }
} 
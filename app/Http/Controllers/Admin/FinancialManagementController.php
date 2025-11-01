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
                
            // Get all recent payout requests (not just pending) with error handling
            $pendingPayoutRequests = PayoutRequest::with(['teacher'])
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

            return Inertia::render('admin/financial/index', [
                'totalTeachers' => $totalTeachers,
                'totalEarnings' => $totalEarnings,
                'pendingPayouts' => $pendingPayouts,
                'pendingPayoutsAmount' => $pendingPayoutsAmount,
                'recentTransactions' => $recentTransactions,
                'pendingPayoutRequests' => $pendingPayoutRequests,
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
        return Inertia::render('admin/financial/payout-request-details', [
            'payoutRequest' => $payoutRequest->load(['teacher', 'processedBy', 'transaction']),
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
} 
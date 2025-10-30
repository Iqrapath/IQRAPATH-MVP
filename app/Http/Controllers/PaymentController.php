<?php

namespace App\Http\Controllers;

use App\Models\StudentWallet;
use App\Models\SubscriptionPlan;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Show payment method selection page.
     */
    public function showPaymentMethods(SubscriptionPlan $plan)
    {
        $user = Auth::user();
        $wallet = StudentWallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'payment_methods' => [],
                'default_payment_method' => null,
            ]
        );
        
        return Inertia::render('Payments/Methods', [
            'plan' => $plan,
            'wallet' => [
                'balance' => $wallet->balance,
                'payment_methods' => $wallet->payment_methods,
                'default_payment_method' => $wallet->default_payment_method,
            ],
        ]);
    }

    /**
     * Process payment with Paystack.
     */
    public function processPaystack(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'currency' => 'required|in:naira,dollar',
            'auto_renew' => 'boolean',
        ]);
        
        $user = Auth::user();
        
        try {
            $result = $this->paymentService->initializePaystackPayment($user, $plan, $validated);
            
            return redirect()->away($result['authorization_url']);
        } catch (Exception $e) {
            Log::error('Paystack payment error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);
            
            return redirect()->route('subscriptions.checkout', $plan->id)
                ->with('error', 'Payment initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Process payment with wallet.
     */
    public function processWallet(Request $request, SubscriptionPlan $plan)
    {
        $validated = $request->validate([
            'currency' => 'required|in:naira,dollar',
            'auto_renew' => 'boolean',
        ]);
        
        $user = Auth::user();
        $wallet = StudentWallet::where('user_id', $user->id)->first();
        
        if (!$wallet) {
            return redirect()->route('subscriptions.checkout', $plan->id)
                ->with('error', 'You do not have a wallet.');
        }
        
        $amount = $plan->getPriceForCurrency($validated['currency']);
        
        if ($wallet->balance < $amount) {
            return redirect()->route('subscriptions.checkout', $plan->id)
                ->with('error', 'Insufficient wallet balance.');
        }
        
        try {
            $result = $this->paymentService->processWalletPayment($user, $plan, $validated);
            
            return redirect()->route('subscriptions.my')
                ->with('success', 'Subscription purchased successfully!');
        } catch (Exception $e) {
            Log::error('Wallet payment error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);
            
            return redirect()->route('subscriptions.checkout', $plan->id)
                ->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify payment callback.
     */
    public function verifyPayment(Request $request, string $gateway, string $reference)
    {
        try {
            if ($gateway === 'paystack') {
                $result = $this->paymentService->verifyPaystackPayment($reference);
                
                if ($result['success']) {
                    return redirect()->route('subscriptions.my')
                        ->with('success', 'Payment successful! Your subscription is now active.');
                } else {
                    return redirect()->route('subscriptions.my')
                        ->with('error', 'Payment verification failed: ' . ($result['message'] ?? 'Unknown error'));
                }
            }
            
            return redirect()->route('subscriptions.my')
                ->with('error', 'Unsupported payment gateway.');
        } catch (Exception $e) {
            Log::error('Payment verification error', [
                'error' => $e->getMessage(),
                'gateway' => $gateway,
                'reference' => $reference,
            ]);
            
            return redirect()->route('subscriptions.my')
                ->with('error', 'Payment verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle payment callback (redirect after payment).
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');
        $trxref = $request->query('trxref');
        
        // Paystack uses 'reference' or 'trxref'
        $paymentReference = $reference ?? $trxref;
        
        if (!$paymentReference) {
            return redirect()->route('subscriptions.my')
                ->with('error', 'Payment reference not found.');
        }
        
        // Redirect to verification route
        return redirect()->route('payment.verify', [
            'gateway' => 'paystack',
            'reference' => $paymentReference
        ]);
    }

    /**
     * Handle payment webhook.
     */
    public function webhook(Request $request, string $gateway)
    {
        try {
            if ($gateway === 'paystack') {
                // Verify Paystack webhook signature
                $payload = $request->all();
                
                $result = $this->paymentService->handlePaystackWebhook($payload);
                
                return response()->json(['status' => $result ? 'success' : 'error']);
            }
            
            return response()->json(['status' => 'error', 'message' => 'Unsupported gateway'], 400);
        } catch (Exception $e) {
            Log::error('Webhook error', [
                'error' => $e->getMessage(),
                'gateway' => $gateway,
            ]);
            
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Add funds to wallet.
     */
    public function addFunds(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100',
            'currency' => 'required|in:naira,dollar',
            'payment_method' => 'required|in:paystack,bank_transfer',
        ]);
        
        $user = Auth::user();
        $wallet = StudentWallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'payment_methods' => [],
                'default_payment_method' => null,
            ]
        );
        
        // Implementation will depend on the selected payment method
        if ($validated['payment_method'] === 'paystack') {
            // Redirect to Paystack for wallet funding
            // This would be similar to the processPaystack method but for wallet funding
            return redirect()->route('wallet.paystack.initialize', [
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
            ]);
        } elseif ($validated['payment_method'] === 'bank_transfer') {
            // Show bank transfer instructions
            return Inertia::render('Payments/BankTransfer', [
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'reference' => 'IQRA_FUND_' . time() . '_' . $user->id,
            ]);
        }
        
        return redirect()->back()->with('error', 'Unsupported payment method.');
    }

    /**
     * Manage payment methods.
     */
    public function managePaymentMethods()
    {
        $user = Auth::user();
        $wallet = StudentWallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'payment_methods' => [],
                'default_payment_method' => null,
            ]
        );
        
        return Inertia::render('Payments/ManagePaymentMethods', [
            'wallet' => [
                'balance' => $wallet->balance,
                'payment_methods' => $wallet->payment_methods,
                'default_payment_method' => $wallet->default_payment_method,
            ],
        ]);
    }

    /**
     * Remove payment method.
     */
    public function removePaymentMethod(Request $request)
    {
        $validated = $request->validate([
            'payment_method_id' => 'required|string',
        ]);
        
        $user = Auth::user();
        $wallet = StudentWallet::where('user_id', $user->id)->first();
        
        if (!$wallet) {
            return redirect()->back()->with('error', 'You do not have a wallet.');
        }
        
        if ($wallet->removePaymentMethod($validated['payment_method_id'])) {
            return redirect()->back()->with('success', 'Payment method removed successfully.');
        }
        
        return redirect()->back()->with('error', 'Failed to remove payment method.');
    }

    /**
     * Set default payment method.
     */
    public function setDefaultPaymentMethod(Request $request)
    {
        $validated = $request->validate([
            'payment_method_id' => 'required|string',
        ]);
        
        $user = Auth::user();
        $wallet = StudentWallet::where('user_id', $user->id)->first();
        
        if (!$wallet) {
            return redirect()->back()->with('error', 'You do not have a wallet.');
        }
        
        if ($wallet->setDefaultPaymentMethod($validated['payment_method_id'])) {
            return redirect()->back()->with('success', 'Default payment method updated successfully.');
        }
        
        return redirect()->back()->with('error', 'Failed to update default payment method.');
    }

    /**
     * Initialize Paystack payment for wallet funding.
     */
    public function initializePaystackWalletFunding(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100',
            'currency' => 'required|in:naira,dollar',
        ]);
        
        $user = Auth::user();
        
        try {
            $result = $this->paymentService->initializePaystackWalletFunding($user, $validated['amount'], $validated['currency']);
            
            return redirect()->away($result['authorization_url']);
        } catch (Exception $e) {
            Log::error('Paystack wallet funding error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'amount' => $validated['amount'],
            ]);
            
            return redirect()->route('wallet.manage')
                ->with('error', 'Wallet funding initialization failed: ' . $e->getMessage());
        }
    }
} 
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StorePaymentMethodRequest;
use App\Http\Requests\Teacher\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Services\BankVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function __construct(
        private BankVerificationService $bankVerificationService
    ) {}

    /**
     * Display a listing of the teacher's payment methods.
     */
    public function index(): JsonResponse
    {
        $paymentMethods = Auth::user()
            ->paymentMethods()
            ->active()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($paymentMethods);
    }

    /**
     * Store a newly created payment method.
     */
    public function store(StorePaymentMethodRequest $request)
    {
        try {
            $user = Auth::user();
            $validated = $request->validated();

            DB::beginTransaction();

            // Verify bank account if type is bank_transfer
            if ($validated['type'] === 'bank_transfer') {
                try {
                    $verificationResult = $this->bankVerificationService->verifyBankAccount(
                        $validated['account_number'],
                        $validated['bank_code']
                    );

                    if ($verificationResult['success']) {
                        // Verification successful - use verified data
                        $validated['account_name'] = $verificationResult['data']['account_name'] ?? $validated['account_name'];
                        $validated['bank_name'] = $verificationResult['data']['bank_name'] ?? $validated['bank_name'];
                        $validated['is_verified'] = true;
                        $validated['verification_status'] = 'verified';
                        $validated['verified_at'] = now();
                        
                        Log::info('Bank account verified successfully', [
                            'account_number' => $validated['account_number'],
                            'bank_code' => $validated['bank_code']
                        ]);
                    } else {
                        // Verification failed - store as pending for manual verification
                        // Keep the user-provided bank_name and account_name
                        $validated['verification_status'] = 'pending';
                        $validated['is_verified'] = false;
                        $validated['verification_notes'] = 'Automatic verification failed. Pending manual review.';
                        
                        Log::warning('Bank verification failed, storing as pending with user-provided data', [
                            'account_number' => $validated['account_number'],
                            'bank_code' => $validated['bank_code'],
                            'bank_name' => $validated['bank_name'],
                            'account_name' => $validated['account_name'],
                            'error' => $verificationResult['message'] ?? 'Unknown error'
                        ]);
                    }
                } catch (\Exception $e) {
                    // Verification error - store as pending
                    // Keep the user-provided bank_name and account_name
                    $validated['verification_status'] = 'pending';
                    $validated['is_verified'] = false;
                    $validated['verification_notes'] = 'Verification service unavailable. Pending manual review.';
                    
                    Log::error('Bank verification exception, storing as pending with user-provided data', [
                        'account_number' => $validated['account_number'],
                        'bank_code' => $validated['bank_code'],
                        'bank_name' => $validated['bank_name'],
                        'account_name' => $validated['account_name'],
                        'error' => $e->getMessage()
                    ]);
                }
            } elseif ($validated['type'] === 'paypal') {
                // PayPal payment method
                $validated['gateway'] = 'paypal';
                $validated['verification_status'] = 'pending';
                $validated['is_verified'] = false;
                $validated['verification_notes'] = 'PayPal account pending email verification';
                
                // Extract PayPal email from metadata
                $paypalEmail = $validated['metadata']['paypal_email'] ?? null;
                
                if ($paypalEmail) {
                    // Check if this PayPal email is already registered for this user
                    $existingPayPal = $user->paymentMethods()
                        ->where('type', 'paypal')
                        ->where('is_active', true)
                        ->whereJsonContains('metadata->paypal_email', $paypalEmail)
                        ->exists();
                    
                    if ($existingPayPal) {
                        DB::rollBack();
                        
                        if ($request->expectsJson()) {
                            return response()->json([
                                'success' => false,
                                'message' => 'This PayPal account is already registered'
                            ], 422);
                        }
                        
                        return back()->with('error', 'This PayPal account is already registered');
                    }
                    
                    Log::info('PayPal account added', [
                        'user_id' => $user->id,
                        'paypal_email' => $paypalEmail
                    ]);
                }
            } else {
                // Other payment types start as pending
                $validated['verification_status'] = 'pending';
                $validated['is_verified'] = false;
            }

            // Store only last 4 digits of account number for security (for bank transfers)
            if (isset($validated['account_number'])) {
                $validated['last_four'] = substr($validated['account_number'], -4);
                $validated['account_number'] = null; // Don't store full number
            }

            // Set as default if this is the first payment method
            $hasExistingMethods = $user->paymentMethods()->active()->exists();
            if (!$hasExistingMethods) {
                $validated['is_default'] = true;
            }

            // Create payment method
            $paymentMethod = $user->paymentMethods()->create($validated);

            DB::commit();

            Log::info('Payment method created', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
                'bank_name' => $paymentMethod->bank_name,
                'account_name' => $paymentMethod->account_name,
                'last_four' => $paymentMethod->last_four,
                'verified' => $paymentMethod->is_verified
            ]);

            // Return JSON for AJAX requests (PayPal modal)
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment method added successfully',
                    'payment_method' => $paymentMethod
                ]);
            }

            // Return redirect for form submissions (Bank transfer modal)
            return back()->with('success', 'Payment method added successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create payment method', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return JSON for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add payment method. Please try again.'
                ], 500);
            }

            // Return redirect for form submissions
            return back()->withErrors([
                'error' => 'Failed to add payment method. Please try again.'
            ]);
        }
    }

    /**
     * Update the specified payment method.
     */
    public function update(PaymentMethod $paymentMethod, UpdatePaymentMethodRequest $request): RedirectResponse
    {
        // Ensure user owns this payment method
        if ($paymentMethod->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $validated = $request->validated();

            DB::beginTransaction();

            // Re-verify if bank details changed
            if ($paymentMethod->type === 'bank_transfer' && 
                (isset($validated['account_number']) || isset($validated['bank_code']))) {
                
                $accountNumber = $validated['account_number'] ?? $paymentMethod->account_number;
                $bankCode = $validated['bank_code'] ?? $paymentMethod->bank_code;

                $verificationResult = $this->bankVerificationService->verifyBankAccount(
                    $accountNumber,
                    $bankCode
                );

                if (!$verificationResult['success']) {
                    return back()->withErrors([
                        'account_number' => $verificationResult['message'] ?? 'Unable to verify bank account'
                    ]);
                }

                $validated['is_verified'] = true;
                $validated['verification_status'] = 'verified';
                $validated['verified_at'] = now();
            }

            // Update last 4 digits if account number changed
            if (isset($validated['account_number'])) {
                $validated['last_four'] = substr($validated['account_number'], -4);
                $validated['account_number'] = null;
            }

            $paymentMethod->update($validated);

            DB::commit();

            return back()->with('success', 'Payment method updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update payment method', [
                'payment_method_id' => $paymentMethod->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors([
                'error' => 'Failed to update payment method. Please try again.'
            ]);
        }
    }

    /**
     * Remove the specified payment method.
     */
    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        // Ensure user owns this payment method
        if ($paymentMethod->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Check if this is the default method
            if ($paymentMethod->is_default) {
                // Check if there are other active methods
                $otherMethods = Auth::user()
                    ->paymentMethods()
                    ->active()
                    ->where('id', '!=', $paymentMethod->id)
                    ->exists();

                if ($otherMethods) {
                    return back()->withErrors([
                        'error' => 'Please set another payment method as default before deleting this one.'
                    ]);
                }
            }

            // Soft delete
            $paymentMethod->delete();

            Log::info('Payment method deleted', [
                'payment_method_id' => $paymentMethod->id,
                'user_id' => Auth::id()
            ]);

            return back()->with('success', 'Payment method removed successfully');

        } catch (\Exception $e) {
            Log::error('Failed to delete payment method', [
                'payment_method_id' => $paymentMethod->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors([
                'error' => 'Failed to remove payment method. Please try again.'
            ]);
        }
    }

    /**
     * Set the specified payment method as default.
     */
    public function setDefault(PaymentMethod $paymentMethod): RedirectResponse
    {
        // Ensure user owns this payment method
        if ($paymentMethod->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Only verified methods can be set as default
        if (!$paymentMethod->is_verified) {
            return back()->withErrors([
                'error' => 'Only verified payment methods can be set as default.'
            ]);
        }

        try {
            DB::beginTransaction();

            // Remove default flag from all user's payment methods
            Auth::user()->paymentMethods()->update(['is_default' => false]);

            // Set this method as default
            $paymentMethod->update(['is_default' => true]);

            DB::commit();

            Log::info('Default payment method updated', [
                'user_id' => Auth::id(),
                'payment_method_id' => $paymentMethod->id
            ]);

            return back()->with('success', 'Default payment method updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to set default payment method', [
                'payment_method_id' => $paymentMethod->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors([
                'error' => 'Failed to update default payment method. Please try again.'
            ]);
        }
    }

    /**
     * Retry verification for the specified payment method.
     */
    public function verify(PaymentMethod $paymentMethod): JsonResponse
    {
        // Ensure user owns this payment method
        if ($paymentMethod->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if ($paymentMethod->type === 'bank_transfer') {
                // Get account number and bank code (handle legacy details format)
                $accountNumber = $paymentMethod->account_number 
                    ?? $paymentMethod->details['account_number'] 
                    ?? null;
                $bankCode = $paymentMethod->bank_code 
                    ?? $paymentMethod->details['bank_code'] 
                    ?? null;

                // Check if we have the required data
                if (!$accountNumber || !$bankCode) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot verify: Missing account number or bank code. Please add a new payment method.'
                    ], 400);
                }

                $verificationResult = $this->bankVerificationService->verifyBankAccount(
                    $accountNumber,
                    $bankCode
                );

                if ($verificationResult['success']) {
                    $paymentMethod->update([
                        'is_verified' => true,
                        'verification_status' => 'verified',
                        'verified_at' => now(),
                        'verification_notes' => null
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment method verified successfully',
                        'data' => $paymentMethod->fresh()
                    ]);
                } else {
                    $paymentMethod->update([
                        'verification_status' => 'failed',
                        'verification_notes' => $verificationResult['message'] ?? 'Verification failed'
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => $verificationResult['message'] ?? 'Verification failed'
                    ], 400);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'This payment method type does not support automatic verification'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Failed to verify payment method', [
                'payment_method_id' => $paymentMethod->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Get list of supported banks.
     */
    public function getBanks(): JsonResponse
    {
        try {
            Log::info('Fetching banks list');
            
            $banks = $this->bankVerificationService->getBankList('NG');
            
            Log::info('Banks fetched from Paystack', [
                'count' => count($banks)
            ]);

            // If Paystack returns empty, use fallback list
            if (empty($banks)) {
                Log::warning('Paystack returned empty banks, using fallback list');
                $banks = $this->getFallbackBanks();
            }

            return response()->json($banks);

        } catch (\Exception $e) {
            Log::error('Failed to fetch banks from Paystack, using fallback', [
                'error' => $e->getMessage()
            ]);

            // Return fallback banks on error
            return response()->json($this->getFallbackBanks());
        }
    }

    /**
     * Get fallback list of Nigerian banks.
     */
    private function getFallbackBanks(): array
    {
        return [
            ['id' => 1, 'name' => 'Access Bank', 'code' => '044', 'slug' => 'access-bank', 'country' => 'NG'],
            ['id' => 2, 'name' => 'Citibank Nigeria', 'code' => '023', 'slug' => 'citibank-nigeria', 'country' => 'NG'],
            ['id' => 3, 'name' => 'Ecobank Nigeria', 'code' => '050', 'slug' => 'ecobank-nigeria', 'country' => 'NG'],
            ['id' => 4, 'name' => 'Fidelity Bank', 'code' => '070', 'slug' => 'fidelity-bank', 'country' => 'NG'],
            ['id' => 5, 'name' => 'First Bank of Nigeria', 'code' => '011', 'slug' => 'first-bank-nigeria', 'country' => 'NG'],
            ['id' => 6, 'name' => 'First City Monument Bank', 'code' => '214', 'slug' => 'fcmb', 'country' => 'NG'],
            ['id' => 7, 'name' => 'Guaranty Trust Bank', 'code' => '058', 'slug' => 'gtbank', 'country' => 'NG'],
            ['id' => 8, 'name' => 'Heritage Bank', 'code' => '030', 'slug' => 'heritage-bank', 'country' => 'NG'],
            ['id' => 9, 'name' => 'Keystone Bank', 'code' => '082', 'slug' => 'keystone-bank', 'country' => 'NG'],
            ['id' => 10, 'name' => 'Kuda Bank', 'code' => '50211', 'slug' => 'kuda-bank', 'country' => 'NG'],
            ['id' => 11, 'name' => 'Polaris Bank', 'code' => '076', 'slug' => 'polaris-bank', 'country' => 'NG'],
            ['id' => 12, 'name' => 'Providus Bank', 'code' => '101', 'slug' => 'providus-bank', 'country' => 'NG'],
            ['id' => 13, 'name' => 'Stanbic IBTC Bank', 'code' => '221', 'slug' => 'stanbic-ibtc', 'country' => 'NG'],
            ['id' => 14, 'name' => 'Standard Chartered Bank', 'code' => '068', 'slug' => 'standard-chartered', 'country' => 'NG'],
            ['id' => 15, 'name' => 'Sterling Bank', 'code' => '232', 'slug' => 'sterling-bank', 'country' => 'NG'],
            ['id' => 16, 'name' => 'Union Bank of Nigeria', 'code' => '032', 'slug' => 'union-bank', 'country' => 'NG'],
            ['id' => 17, 'name' => 'United Bank for Africa', 'code' => '033', 'slug' => 'uba', 'country' => 'NG'],
            ['id' => 18, 'name' => 'Unity Bank', 'code' => '215', 'slug' => 'unity-bank', 'country' => 'NG'],
            ['id' => 19, 'name' => 'Wema Bank', 'code' => '035', 'slug' => 'wema-bank', 'country' => 'NG'],
            ['id' => 20, 'name' => 'Zenith Bank', 'code' => '057', 'slug' => 'zenith-bank', 'country' => 'NG'],
            ['id' => 21, 'name' => 'Opay', 'code' => '999992', 'slug' => 'opay', 'country' => 'NG'],
            ['id' => 22, 'name' => 'PalmPay', 'code' => '999991', 'slug' => 'palmpay', 'country' => 'NG'],
        ];
    }
}

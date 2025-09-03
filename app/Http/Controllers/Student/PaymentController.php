<?php
declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Process wallet funding payment
     */
    public function fundWallet(Request $request): JsonResponse
    {
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

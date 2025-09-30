<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\WithdrawalService;
use App\Services\PaymentService;
use App\Models\User;
use App\Models\PayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class WithdrawalController extends Controller
{
    public function __construct(
        private WithdrawalService $withdrawalService,
        private PaymentService $paymentService
    ) {}

    /**
     * Process withdrawal request
     */
    public function processWithdrawal(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1000|max:1000000',
                'method' => 'required|in:bank_transfer,mobile_money,paypal',
                'currency' => 'required|in:NGN,USD',
                'payment_method_id' => 'required_if:method,stripe|string',
                'bank_name' => 'required_if:method,bank_transfer|string',
                'account_number' => 'required_if:method,bank_transfer|string',
                'account_name' => 'required_if:method,bank_transfer|string',
                'mobile_provider' => 'required_if:method,mobile_money|string',
                'mobile_number' => 'required_if:method,mobile_money|string',
                'paypal_email' => 'required_if:method,paypal|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            
            // Check if user has sufficient balance
            $wallet = $user->getOrCreateWallet();
            if ($wallet->balance < $request->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance'
                ], 400);
            }

            // Check withdrawal limits
            $limitCheck = $this->withdrawalService->checkWithdrawalLimits($user, $request->amount, $request->currency);
            if (!$limitCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $limitCheck['message']
                ], 400);
            }

            // Calculate fees
            $feeCalculation = $this->withdrawalService->calculateWithdrawalFee($request->method, $request->amount);
            
            // Process based on method
            switch ($request->method) {
                case 'paypal':
                    return $this->processPayPalWithdrawal($user, $request->all(), $feeCalculation);
                default:
                    return $this->processManualWithdrawal($user, $request->all(), $feeCalculation);
            }

        } catch (\Exception $e) {
            Log::error('Withdrawal processing error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your withdrawal. Please try again.'
            ], 500);
        }
    }

    /**
     * Process PayPal withdrawal
     */
    private function processPayPalWithdrawal(User $user, array $data, array $feeCalculation): JsonResponse
    {
        try {
            // Create payout request
            $payoutRequest = PayoutRequest::create([
                'user_id' => $user->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'method' => 'paypal',
                'status' => 'pending',
                'fee_amount' => $feeCalculation['fee_amount'],
                'fee_currency' => $data['currency'],
                'net_amount' => $feeCalculation['net_amount'],
                'payment_details' => json_encode([
                    'email' => $data['paypal_email'],
                    'method' => 'paypal'
                ]),
                'requested_at' => now(),
            ]);

            // Initialize PayPal payout
            $paypalResponse = $this->withdrawalService->initializePayPalPayout($payoutRequest);

            if (!$paypalResponse['success']) {
                $payoutRequest->update(['status' => 'failed']);
                return response()->json([
                    'success' => false,
                    'message' => $paypalResponse['message']
                ], 400);
            }

            // Update payout request with PayPal reference
            $payoutRequest->update([
                'external_reference' => $paypalResponse['payout_batch_id'],
                'status' => 'processing'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PayPal withdrawal initiated successfully',
                'data' => [
                    'payout_request_id' => $payoutRequest->id,
                    'payout_batch_id' => $paypalResponse['payout_batch_id'],
                    'status' => 'processing',
                    'redirect_url' => $paypalResponse['redirect_url'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('PayPal withdrawal error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process PayPal withdrawal'
            ], 500);
        }
    }


    /**
     * Process manual withdrawal (Bank Transfer, Mobile Money)
     */
    private function processManualWithdrawal(User $user, array $data, array $feeCalculation): JsonResponse
    {
        try {
            // Create payout request
            $payoutRequest = PayoutRequest::create([
                'user_id' => $user->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'method' => $data['method'],
                'status' => 'pending',
                'fee_amount' => $feeCalculation['fee_amount'],
                'fee_currency' => $data['currency'],
                'net_amount' => $feeCalculation['net_amount'],
                'payment_details' => json_encode([
                    'bank_name' => $data['bank_name'] ?? null,
                    'account_number' => $data['account_number'] ?? null,
                    'account_name' => $data['account_name'] ?? null,
                    'mobile_provider' => $data['mobile_provider'] ?? null,
                    'mobile_number' => $data['mobile_number'] ?? null,
                ]),
                'requested_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted successfully. It will be processed within 1-3 business days.',
                'data' => [
                    'payout_request_id' => $payoutRequest->id,
                    'status' => 'pending',
                    'processing_time' => $this->withdrawalService->getProcessingTime($data['method'])
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Manual withdrawal error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit withdrawal request'
            ], 500);
        }
    }

    /**
     * Handle webhook callbacks
     */
    public function handleCallback(Request $request, string $method): JsonResponse
    {
        try {
            switch ($method) {
                case 'paypal':
                    return $this->handlePayPalWebhook($request);
                default:
                    return response()->json(['success' => false, 'message' => 'Invalid method'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Webhook callback error', [
                'method' => $method,
                'error' => $e->getMessage()
            ]);

            return response()->json(['success' => false, 'message' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle PayPal webhook
     */
    private function handlePayPalWebhook(Request $request): JsonResponse
    {
        // Verify webhook signature
        $isValid = $this->withdrawalService->verifyPayPalWebhook($request);
        
        if (!$isValid) {
            return response()->json(['success' => false, 'message' => 'Invalid webhook signature'], 400);
        }

        $payload = $request->json()->all();
        
        // Process webhook event
        $this->withdrawalService->processPayPalWebhook($payload);

        return response()->json(['success' => true]);
    }


    /**
     * Get withdrawal fee preview
     */
    public function getFeePreview(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1000|max:1000000',
                'method' => 'required|in:bank_transfer,mobile_money,paypal',
                'currency' => 'required|in:NGN,USD',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $feeCalculation = $this->withdrawalService->calculateWithdrawalFee(
                $request->method, 
                $request->amount, 
                $request->currency
            );

            $processingTime = $this->withdrawalService->getProcessingTime($request->method);

            return response()->json([
                'success' => true,
                'data' => [
                    'amount' => $request->amount,
                    'currency' => $request->currency,
                    'method' => $request->method,
                    'fee_amount' => $feeCalculation['fee_amount'],
                    'fee_currency' => $request->currency,
                    'net_amount' => $feeCalculation['net_amount'],
                    'processing_time' => $processingTime,
                    'fee_breakdown' => $feeCalculation['breakdown'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Fee preview error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate fees'
            ], 500);
        }
    }

    /**
     * Get withdrawal status
     */
    public function getWithdrawalStatus(Request $request, int $payoutRequestId): JsonResponse
    {
        try {
            $user = Auth::user();
            $payoutRequest = PayoutRequest::where('id', $payoutRequestId)
                ->where('user_id', $user->id)
                ->first();

            if (!$payoutRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Withdrawal request not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $payoutRequest->id,
                    'amount' => $payoutRequest->amount,
                    'currency' => $payoutRequest->currency,
                    'method' => $payoutRequest->method,
                    'status' => $payoutRequest->status,
                    'fee_amount' => $payoutRequest->fee_amount,
                    'net_amount' => $payoutRequest->net_amount,
                    'requested_at' => $payoutRequest->requested_at,
                    'processed_at' => $payoutRequest->processed_at,
                    'external_reference' => $payoutRequest->external_reference,
                    'processing_time' => $this->withdrawalService->getProcessingTime($payoutRequest->method)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Withdrawal status error', [
                'user_id' => Auth::id(),
                'payout_request_id' => $payoutRequestId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get withdrawal status'
            ], 500);
        }
    }
}

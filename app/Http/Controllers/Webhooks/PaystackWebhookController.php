<?php
declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\PayStackTransferService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PayStackWebhookController extends Controller
{
    public function __construct(
        private PayStackTransferService $payStackService,
        private \App\Services\PaystackVirtualAccountService $virtualAccountService
    ) {}

    /**
     * Handle PayStack transfer webhook
     */
    public function handleTransferWebhook(Request $request): JsonResponse
    {
        try {
            // Verify webhook signature
            if (!$this->verifySignature($request)) {
                Log::warning('PayStack webhook signature verification failed', [
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature'
                ], 401);
            }

            $webhookData = $request->all();
            
            Log::info('PayStack webhook received', [
                'event' => $webhookData['event'] ?? 'unknown',
                'data' => $webhookData['data'] ?? [],
            ]);

            // Process the webhook
            $result = $this->payStackService->handleTransferWebhook($webhookData);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook processed successfully'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Webhook processing failed'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('PayStack webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Verify PayStack webhook signature
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('x-paystack-signature');
        
        if (!$signature) {
            return false;
        }

        $secretKey = config('services.paystack.secret_key');
        $computedSignature = hash_hmac('sha512', $request->getContent(), $secretKey);

        return hash_equals($computedSignature, $signature);
    }

    /**
     * Handle Paystack virtual account webhook
     */
    public function handleVirtualAccountWebhook(Request $request): JsonResponse
    {
        try {
            // Verify webhook signature
            if (!$this->verifySignature($request)) {
                Log::warning('Paystack virtual account webhook signature verification failed', [
                    'ip' => $request->ip(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature'
                ], 401);
            }

            $webhookData = $request->all();
            
            Log::info('Paystack virtual account webhook received', [
                'event' => $webhookData['event'] ?? 'unknown',
            ]);

            // Process the webhook
            $result = $this->virtualAccountService->handleWebhook($webhookData);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook processed successfully'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Webhook processing failed'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Paystack virtual account webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }
}

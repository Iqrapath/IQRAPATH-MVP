<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private WithdrawalService $withdrawalService
    ) {}

    /**
     * Handle PayStack transfer webhooks
     */
    public function handlePayStackWebhook(Request $request): JsonResponse
    {
        try {
            // Verify webhook signature (implement proper verification)
            if (!$this->verifyPayStackWebhook($request)) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $payload = $request->all();
            
            // Process the webhook
            $result = $this->withdrawalService->processPayStackWebhook($payload);

            if ($result['success']) {
                return response()->json(['status' => 'success'], 200);
            } else {
                Log::error('PayStack webhook processing failed', [
                    'payload' => $payload,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                
                return response()->json(['error' => 'Processing failed'], 500);
            }

        } catch (\Exception $e) {
            Log::error('PayStack webhook error', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle PayPal webhooks
     */
    public function handlePayPalWebhook(Request $request): JsonResponse
    {
        try {
            // Verify webhook signature (implement proper verification)
            if (!$this->verifyPayPalWebhook($request)) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $payload = $request->all();
            
            // Process the webhook
            $this->withdrawalService->processPayPalWebhook($payload);

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('PayPal webhook error', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Verify PayStack webhook signature
     */
    private function verifyPayStackWebhook(Request $request): bool
    {
        $signature = $request->header('X-Paystack-Signature');
        $payload = $request->getContent();
        $secret = config('services.paystack.secret_key');

        if (!$signature || !$secret) {
            return false;
        }

        $computedSignature = hash_hmac('sha512', $payload, $secret);
        
        return hash_equals($signature, $computedSignature);
    }

    /**
     * Verify PayPal webhook signature
     */
    private function verifyPayPalWebhook(Request $request): bool
    {
        // Implement PayPal webhook verification
        // This would verify the webhook signature using PayPal's verification method
        return true; // Placeholder for now
    }
}

<?php
declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\StripePayoutService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(
        private StripePayoutService $stripeService
    ) {}

    /**
     * Handle Stripe payout webhook
     */
    public function handlePayoutWebhook(Request $request): JsonResponse
    {
        try {
            // Verify webhook signature
            if (!$this->verifySignature($request)) {
                Log::warning('Stripe webhook signature verification failed', [
                    'ip' => $request->ip(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature'
                ], 401);
            }

            $webhookData = $request->all();
            
            Log::info('Stripe webhook received', [
                'type' => $webhookData['type'] ?? 'unknown',
                'id' => $webhookData['id'] ?? 'unknown',
            ]);

            // Process the webhook
            $result = $this->stripeService->handleWebhook($webhookData);

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
            Log::error('Stripe webhook processing error', [
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
     * Verify Stripe webhook signature
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('stripe-signature');
        
        if (!$signature) {
            return false;
        }

        $webhookSecret = config('services.stripe.webhook_secret');
        
        if (!$webhookSecret) {
            Log::warning('Stripe webhook secret not configured');
            return true; // Skip verification if not configured
        }

        try {
            $payload = $request->getContent();
            $sigHeader = $signature;
            
            // Parse signature header
            $elements = explode(',', $sigHeader);
            $timestamp = null;
            $signatures = [];
            
            foreach ($elements as $element) {
                $item = explode('=', $element, 2);
                if (count($item) === 2) {
                    if ($item[0] === 't') {
                        $timestamp = $item[1];
                    } elseif ($item[0] === 'v1') {
                        $signatures[] = $item[1];
                    }
                }
            }
            
            if (!$timestamp || empty($signatures)) {
                return false;
            }
            
            // Compute expected signature
            $signedPayload = $timestamp . '.' . $payload;
            $expectedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);
            
            // Compare signatures
            foreach ($signatures as $signature) {
                if (hash_equals($expectedSignature, $signature)) {
                    return true;
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('Stripe signature verification error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

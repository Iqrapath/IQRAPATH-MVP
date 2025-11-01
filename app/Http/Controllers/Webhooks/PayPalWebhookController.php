<?php
declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\PayPalPayoutService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PayPalWebhookController extends Controller
{
    public function __construct(
        private PayPalPayoutService $payPalService
    ) {}

    /**
     * Handle PayPal payout webhook
     */
    public function handlePayoutWebhook(Request $request): JsonResponse
    {
        try {
            // Verify webhook signature
            if (!$this->verifySignature($request)) {
                Log::warning('PayPal webhook signature verification failed', [
                    'ip' => $request->ip(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature'
                ], 401);
            }

            $webhookData = $request->all();
            
            Log::info('PayPal webhook received', [
                'event_type' => $webhookData['event_type'] ?? 'unknown',
                'id' => $webhookData['id'] ?? 'unknown',
            ]);

            // Process the webhook
            $result = $this->payPalService->handleWebhook($webhookData);

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
            Log::error('PayPal webhook processing error', [
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
     * Verify PayPal webhook signature
     */
    private function verifySignature(Request $request): bool
    {
        $headers = [
            'paypal-auth-algo' => $request->header('paypal-auth-algo'),
            'paypal-cert-url' => $request->header('paypal-cert-url'),
            'paypal-transmission-id' => $request->header('paypal-transmission-id'),
            'paypal-transmission-sig' => $request->header('paypal-transmission-sig'),
            'paypal-transmission-time' => $request->header('paypal-transmission-time'),
        ];

        // Check if all required headers are present
        foreach ($headers as $key => $value) {
            if (empty($value)) {
                Log::warning('PayPal webhook missing header: ' . $key);
                return false;
            }
        }

        return $this->payPalService->verifyWebhookSignature($headers, $request->getContent());
    }
}

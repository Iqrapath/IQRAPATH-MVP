<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Models\PaymentIntent;
use App\Models\PayoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayPalWebhookController extends Controller
{
    /**
     * Handle PayPal webhook events.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $eventId = $payload['id'] ?? null;
        $eventType = $payload['event_type'] ?? null;

        if (!$eventId || !$eventType) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // Check if event already processed (idempotency)
        if (WebhookEvent::isProcessed($eventId, 'paypal')) {
            Log::info('PayPal webhook already processed', ['event_id' => $eventId]);
            return response()->json(['status' => 'already_processed'], 200);
        }

        // Store webhook event
        $webhookEvent = WebhookEvent::create([
            'event_id' => $eventId,
            'gateway' => 'paypal',
            'type' => $eventType,
            'payload' => $payload,
            'status' => 'pending',
        ]);

        try {
            $webhookEvent->markAsProcessing();

            // Route to appropriate handler
            $this->routeEvent($eventType, $payload);

            $webhookEvent->markAsProcessed();

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            $webhookEvent->markAsFailed($e->getMessage());
            
            Log::error('PayPal webhook processing failed', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Route event to appropriate handler.
     */
    private function routeEvent(string $eventType, array $payload): void
    {
        match ($eventType) {
            'PAYMENT.CAPTURE.COMPLETED' => $this->handlePaymentCaptureCompleted($payload),
            'PAYMENT.CAPTURE.DENIED' => $this->handlePaymentCaptureDenied($payload),
            'PAYMENT.CAPTURE.REFUNDED' => $this->handlePaymentCaptureRefunded($payload),
            'BILLING.SUBSCRIPTION.CREATED' => $this->handleSubscriptionCreated($payload),
            'BILLING.SUBSCRIPTION.CANCELLED' => $this->handleSubscriptionCancelled($payload),
            'BILLING.SUBSCRIPTION.SUSPENDED' => $this->handleSubscriptionSuspended($payload),
            'BILLING.SUBSCRIPTION.EXPIRED' => $this->handleSubscriptionExpired($payload),
            'PAYOUT-ITEM.SUCCEEDED' => $this->handlePayoutSucceeded($payload),
            'PAYOUT-ITEM.FAILED' => $this->handlePayoutFailed($payload),
            'PAYOUT-ITEM.BLOCKED' => $this->handlePayoutBlocked($payload),
            default => Log::info('Unhandled PayPal event type', ['type' => $eventType]),
        };
    }

    /**
     * Handle PAYMENT.CAPTURE.COMPLETED event.
     */
    private function handlePaymentCaptureCompleted(array $payload): void
    {
        $resource = $payload['resource'] ?? [];
        $captureId = $resource['id'] ?? null;

        if (!$captureId) {
            return;
        }

        $paymentIntent = PaymentIntent::where('gateway_intent_id', $captureId)->first();

        if ($paymentIntent) {
            DB::transaction(function () use ($paymentIntent) {
                $paymentIntent->markAsSucceeded();
                
                // Update related records
                if ($paymentIntent->reference_type === 'subscription') {
                    $subscription = \App\Models\Subscription::find($paymentIntent->reference_id);
                    if ($subscription && $subscription->status === 'pending') {
                        $subscription->update(['status' => 'active']);
                    }
                }
            });

            Log::info('PayPal payment capture completed', [
                'payment_intent_id' => $paymentIntent->id,
                'capture_id' => $captureId,
            ]);
        }
    }

    /**
     * Handle PAYMENT.CAPTURE.DENIED event.
     */
    private function handlePaymentCaptureDenied(array $payload): void
    {
        $resource = $payload['resource'] ?? [];
        $captureId = $resource['id'] ?? null;

        if (!$captureId) {
            return;
        }

        $paymentIntent = PaymentIntent::where('gateway_intent_id', $captureId)->first();

        if ($paymentIntent) {
            $paymentIntent->markAsFailed(
                'payment_denied',
                'Payment capture was denied'
            );

            Log::warning('PayPal payment capture denied', [
                'payment_intent_id' => $paymentIntent->id,
                'capture_id' => $captureId,
            ]);
        }
    }

    /**
     * Handle PAYMENT.CAPTURE.REFUNDED event.
     */
    private function handlePaymentCaptureRefunded(array $payload): void
    {
        $resource = $payload['resource'] ?? [];
        $captureId = $resource['id'] ?? null;

        Log::info('PayPal payment refunded', [
            'capture_id' => $captureId,
            'amount' => $resource['amount'] ?? null,
        ]);
    }

    /**
     * Handle BILLING.SUBSCRIPTION.CREATED event.
     */
    private function handleSubscriptionCreated(array $payload): void
    {
        $resource = $payload['resource'] ?? [];
        
        Log::info('PayPal subscription created', [
            'subscription_id' => $resource['id'] ?? null,
            'status' => $resource['status'] ?? null,
        ]);
    }

    /**
     * Handle BILLING.SUBSCRIPTION.CANCELLED event.
     */
    private function handleSubscriptionCancelled(array $payload): void
    {
        $resource = $payload['resource'] ?? [];
        
        Log::info('PayPal subscription cancelled', [
            'subscription_id' => $resource['id'] ?? null,
        ]);
    }

    /**
     * Handle BILLING.SUBSCRIPTION.SUSPENDED event.
     */
    private function handleSubscriptionSuspended(array $payload): void
    {
        $resource = $payload['resource'] ?? [];
        
        Log::info('PayPal subscription suspended', [
            'subscription_id' => $resource['id'] ?? null,
        ]);
    }

    /**
     * Handle BILLING.SUBSCRIPTION.EXPIRED event.
     */
    private function handleSubscriptionExpired(array $payload): void
    {
        $resource = $payload['resource'] ?? [];
        
        Log::info('PayPal subscription expired', [
            'subscription_id' => $resource['id'] ?? null,
        ]);
    }

    /**
     * Handle PAYOUT-ITEM.SUCCEEDED event.
     */
    private function handlePayoutSucceeded(array $payload): void
    {
        $resource = $payload['resource'] ?? [];
        $payoutItemId = $resource['payout_item_id'] ?? null;

        if (!$payoutItemId) {
            return;
        }

        $payoutRequest = PayoutRequest::where('gateway_reference', $payoutItemId)->first();

        if ($payoutRequest) {
            $payoutRequest->update([
                'status' => 'completed',
                'processed_at' => now(),
                'gateway_response' => $resource,
            ]);

            Log::info('PayPal payout succeeded', [
                'payout_request_id' => $payoutRequest->id,
                'payout_item_id' => $payoutItemId,
            ]);
        }
    }

    /**
     * Handle PAYOUT-ITEM.FAILED event.
     */
    private function handlePayoutFailed(array $payload): void
    {
        $resource = $payload['resource'] ?? [];
        $payoutItemId = $resource['payout_item_id'] ?? null;

        if (!$payoutItemId) {
            return;
        }

        $payoutRequest = PayoutRequest::where('gateway_reference', $payoutItemId)->first();

        if ($payoutRequest) {
            $payoutRequest->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $resource['errors'][0]['message'] ?? 'Payout failed',
                'gateway_response' => $resource,
            ]);

            Log::warning('PayPal payout failed', [
                'payout_request_id' => $payoutRequest->id,
                'payout_item_id' => $payoutItemId,
            ]);
        }
    }

    /**
     * Handle PAYOUT-ITEM.BLOCKED event.
     */
    private function handlePayoutBlocked(array $payload): void
    {
        $resource = $payload['resource'] ?? [];
        $payoutItemId = $resource['payout_item_id'] ?? null;

        Log::warning('PayPal payout blocked', [
            'payout_item_id' => $payoutItemId,
            'reason' => $resource['errors'][0]['message'] ?? 'Unknown',
        ]);
    }
}

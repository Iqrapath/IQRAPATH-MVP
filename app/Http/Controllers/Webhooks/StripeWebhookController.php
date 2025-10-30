<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Models\PaymentIntent;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    /**
     * Handle Stripe webhook events.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $eventId = $payload['id'] ?? null;
        $eventType = $payload['type'] ?? null;

        if (!$eventId || !$eventType) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // Check if event already processed (idempotency)
        if (WebhookEvent::isProcessed($eventId, 'stripe')) {
            Log::info('Stripe webhook already processed', ['event_id' => $eventId]);
            return response()->json(['status' => 'already_processed'], 200);
        }

        // Store webhook event
        $webhookEvent = WebhookEvent::create([
            'event_id' => $eventId,
            'gateway' => 'stripe',
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
            
            Log::error('Stripe webhook processing failed', [
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
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($payload),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($payload),
            'payment_method.attached' => $this->handlePaymentMethodAttached($payload),
            'payment_method.detached' => $this->handlePaymentMethodDetached($payload),
            'customer.subscription.created' => $this->handleSubscriptionCreated($payload),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($payload),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($payload),
            'charge.refunded' => $this->handleChargeRefunded($payload),
            default => Log::info('Unhandled Stripe event type', ['type' => $eventType]),
        };
    }

    /**
     * Handle payment_intent.succeeded event.
     */
    private function handlePaymentIntentSucceeded(array $payload): void
    {
        $stripeIntentId = $payload['data']['object']['id'];

        $paymentIntent = PaymentIntent::where('gateway_intent_id', $stripeIntentId)->first();

        if ($paymentIntent) {
            DB::transaction(function () use ($paymentIntent) {
                $paymentIntent->markAsSucceeded();
                
                // Update related records (wallet, subscription, etc.)
                if ($paymentIntent->reference_type === 'subscription') {
                    $subscription = Subscription::find($paymentIntent->reference_id);
                    if ($subscription && $subscription->status === 'pending') {
                        $subscription->update(['status' => 'active']);
                    }
                }
            });

            Log::info('Payment intent succeeded', [
                'payment_intent_id' => $paymentIntent->id,
                'stripe_intent_id' => $stripeIntentId,
            ]);
        }
    }

    /**
     * Handle payment_intent.payment_failed event.
     */
    private function handlePaymentIntentFailed(array $payload): void
    {
        $stripeIntentId = $payload['data']['object']['id'];
        $error = $payload['data']['object']['last_payment_error'] ?? [];

        $paymentIntent = PaymentIntent::where('gateway_intent_id', $stripeIntentId)->first();

        if ($paymentIntent) {
            $paymentIntent->markAsFailed(
                $error['code'] ?? 'unknown',
                $error['message'] ?? 'Payment failed'
            );

            Log::warning('Payment intent failed', [
                'payment_intent_id' => $paymentIntent->id,
                'stripe_intent_id' => $stripeIntentId,
                'error' => $error,
            ]);
        }
    }

    /**
     * Handle payment_method.attached event.
     */
    private function handlePaymentMethodAttached(array $payload): void
    {
        Log::info('Payment method attached', [
            'payment_method_id' => $payload['data']['object']['id'],
            'customer' => $payload['data']['object']['customer'],
        ]);
    }

    /**
     * Handle payment_method.detached event.
     */
    private function handlePaymentMethodDetached(array $payload): void
    {
        Log::info('Payment method detached', [
            'payment_method_id' => $payload['data']['object']['id'],
        ]);
    }

    /**
     * Handle customer.subscription.created event.
     */
    private function handleSubscriptionCreated(array $payload): void
    {
        Log::info('Subscription created', [
            'subscription_id' => $payload['data']['object']['id'],
            'customer' => $payload['data']['object']['customer'],
        ]);
    }

    /**
     * Handle customer.subscription.updated event.
     */
    private function handleSubscriptionUpdated(array $payload): void
    {
        Log::info('Subscription updated', [
            'subscription_id' => $payload['data']['object']['id'],
            'status' => $payload['data']['object']['status'],
        ]);
    }

    /**
     * Handle customer.subscription.deleted event.
     */
    private function handleSubscriptionDeleted(array $payload): void
    {
        Log::info('Subscription deleted', [
            'subscription_id' => $payload['data']['object']['id'],
        ]);
    }

    /**
     * Handle charge.refunded event.
     */
    private function handleChargeRefunded(array $payload): void
    {
        Log::info('Charge refunded', [
            'charge_id' => $payload['data']['object']['id'],
            'amount_refunded' => $payload['data']['object']['amount_refunded'],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Models\PaymentIntent;
use App\Models\Subscription;
use App\Models\PayoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    /**
     * Handle Paystack webhook events.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        if (!$event) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // Generate event ID from reference or use timestamp
        $eventId = $data['reference'] ?? 'paystack_' . time();

        // Check if event already processed (idempotency)
        if (WebhookEvent::isProcessed($eventId, 'paystack')) {
            Log::info('Paystack webhook already processed', ['event_id' => $eventId]);
            return response()->json(['status' => 'already_processed'], 200);
        }

        // Store webhook event
        $webhookEvent = WebhookEvent::create([
            'event_id' => $eventId,
            'gateway' => 'paystack',
            'type' => $event,
            'payload' => $payload,
            'status' => 'pending',
        ]);

        try {
            $webhookEvent->markAsProcessing();

            // Route to appropriate handler
            $this->routeEvent($event, $data);

            $webhookEvent->markAsProcessed();

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            $webhookEvent->markAsFailed($e->getMessage());
            
            Log::error('Paystack webhook processing failed', [
                'event_id' => $eventId,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Route event to appropriate handler.
     */
    private function routeEvent(string $event, array $data): void
    {
        match ($event) {
            'charge.success' => $this->handleChargeSuccess($data),
            'charge.failed' => $this->handleChargeFailed($data),
            'transfer.success' => $this->handleTransferSuccess($data),
            'transfer.failed' => $this->handleTransferFailed($data),
            'subscription.create' => $this->handleSubscriptionCreate($data),
            'subscription.disable' => $this->handleSubscriptionDisable($data),
            'subscription.not_renew' => $this->handleSubscriptionNotRenew($data),
            default => Log::info('Unhandled Paystack event type', ['type' => $event]),
        };
    }

    /**
     * Handle charge.success event.
     */
    private function handleChargeSuccess(array $data): void
    {
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            return;
        }

        $paymentIntent = PaymentIntent::where('gateway_intent_id', $reference)->first();

        if ($paymentIntent) {
            DB::transaction(function () use ($paymentIntent, $data) {
                $paymentIntent->markAsSucceeded();
                
                // Update related records
                if ($paymentIntent->reference_type === 'subscription') {
                    $subscription = Subscription::find($paymentIntent->reference_id);
                    if ($subscription && $subscription->status === 'pending') {
                        $subscription->update(['status' => 'active']);
                    }
                }

                // Update wallet if wallet funding
                if ($paymentIntent->reference_type === 'wallet_funding') {
                    $user = $paymentIntent->user;
                    $wallet = $user->studentWallet ?? $user->guardianWallet;
                    
                    if ($wallet) {
                        $wallet->increment('balance', $paymentIntent->amount);
                    }
                }
            });

            Log::info('Paystack charge succeeded', [
                'payment_intent_id' => $paymentIntent->id,
                'reference' => $reference,
                'amount' => $data['amount'] ?? 0,
            ]);
        }
    }

    /**
     * Handle charge.failed event.
     */
    private function handleChargeFailed(array $data): void
    {
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            return;
        }

        $paymentIntent = PaymentIntent::where('gateway_intent_id', $reference)->first();

        if ($paymentIntent) {
            $paymentIntent->markAsFailed(
                $data['gateway_response'] ?? 'unknown',
                $data['message'] ?? 'Payment failed'
            );

            Log::warning('Paystack charge failed', [
                'payment_intent_id' => $paymentIntent->id,
                'reference' => $reference,
                'message' => $data['message'] ?? 'Unknown error',
            ]);
        }
    }

    /**
     * Handle transfer.success event (teacher payouts).
     */
    private function handleTransferSuccess(array $data): void
    {
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            return;
        }

        $payoutRequest = PayoutRequest::where('reference', $reference)->first();

        if ($payoutRequest) {
            DB::transaction(function () use ($payoutRequest, $data) {
                $payoutRequest->update([
                    'status' => 'completed',
                    'processed_at' => now(),
                    'gateway_response' => $data,
                ]);

                Log::info('Paystack transfer succeeded', [
                    'payout_request_id' => $payoutRequest->id,
                    'reference' => $reference,
                    'amount' => $data['amount'] ?? 0,
                ]);
            });
        }
    }

    /**
     * Handle transfer.failed event.
     */
    private function handleTransferFailed(array $data): void
    {
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            return;
        }

        $payoutRequest = PayoutRequest::where('reference', $reference)->first();

        if ($payoutRequest) {
            $payoutRequest->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $data['message'] ?? 'Transfer failed',
                'gateway_response' => $data,
            ]);

            Log::warning('Paystack transfer failed', [
                'payout_request_id' => $payoutRequest->id,
                'reference' => $reference,
                'message' => $data['message'] ?? 'Unknown error',
            ]);
        }
    }

    /**
     * Handle subscription.create event.
     */
    private function handleSubscriptionCreate(array $data): void
    {
        Log::info('Paystack subscription created', [
            'subscription_code' => $data['subscription_code'] ?? null,
            'customer' => $data['customer']['email'] ?? null,
        ]);
    }

    /**
     * Handle subscription.disable event.
     */
    private function handleSubscriptionDisable(array $data): void
    {
        Log::info('Paystack subscription disabled', [
            'subscription_code' => $data['subscription_code'] ?? null,
        ]);
    }

    /**
     * Handle subscription.not_renew event.
     */
    private function handleSubscriptionNotRenew(array $data): void
    {
        Log::info('Paystack subscription will not renew', [
            'subscription_code' => $data['subscription_code'] ?? null,
        ]);
    }
}

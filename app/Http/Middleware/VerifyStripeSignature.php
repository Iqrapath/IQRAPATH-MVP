<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyStripeSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (!$signature || !$webhookSecret) {
            Log::warning('Stripe webhook rejected: Missing signature or secret');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $payload = $request->getContent();
            $this->verifySignature($payload, $signature, $webhookSecret);
            
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('Stripe signature verification failed', [
                'error' => $e->getMessage(),
                'signature' => $signature,
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }
    }

    /**
     * Verify Stripe webhook signature.
     */
    private function verifySignature(string $payload, string $signature, string $secret): void
    {
        $signedPayload = '';
        $timestamp = '';

        // Parse signature header
        foreach (explode(',', $signature) as $element) {
            [$key, $value] = explode('=', $element, 2);
            
            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 'v1') {
                $signedPayload = $value;
            }
        }

        if (!$timestamp || !$signedPayload) {
            throw new \Exception('Invalid signature format');
        }

        // Check timestamp tolerance (5 minutes)
        $tolerance = 300;
        if (abs(time() - $timestamp) > $tolerance) {
            throw new \Exception('Timestamp outside tolerance');
        }

        // Compute expected signature
        $signedData = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedData, $secret);

        // Compare signatures
        if (!hash_equals($expectedSignature, $signedPayload)) {
            throw new \Exception('Signature mismatch');
        }
    }
}

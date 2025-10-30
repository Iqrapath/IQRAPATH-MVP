<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyPaystackSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Paystack-Signature');
        $webhookSecret = config('services.paystack.secret_key');

        if (!$signature || !$webhookSecret) {
            Log::warning('Paystack webhook rejected: Missing signature or secret');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $payload = $request->getContent();
            $this->verifySignature($payload, $signature, $webhookSecret);
            
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('Paystack signature verification failed', [
                'error' => $e->getMessage(),
                'signature' => $signature,
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }
    }

    /**
     * Verify Paystack webhook signature.
     */
    private function verifySignature(string $payload, string $signature, string $secret): void
    {
        $computedSignature = hash_hmac('sha512', $payload, $secret);

        if (!hash_equals($computedSignature, $signature)) {
            throw new \Exception('Signature mismatch');
        }
    }
}

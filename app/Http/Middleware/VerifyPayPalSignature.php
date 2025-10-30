<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyPayPalSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $transmissionId = $request->header('PAYPAL-TRANSMISSION-ID');
        $transmissionTime = $request->header('PAYPAL-TRANSMISSION-TIME');
        $transmissionSig = $request->header('PAYPAL-TRANSMISSION-SIG');
        $certUrl = $request->header('PAYPAL-CERT-URL');
        $authAlgo = $request->header('PAYPAL-AUTH-ALGO');
        $webhookId = config('services.paypal.webhook_id');

        if (!$transmissionId || !$transmissionSig || !$webhookId) {
            Log::warning('PayPal webhook rejected: Missing required headers');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $this->verifySignature($request, [
                'transmission_id' => $transmissionId,
                'transmission_time' => $transmissionTime,
                'transmission_sig' => $transmissionSig,
                'cert_url' => $certUrl,
                'auth_algo' => $authAlgo,
                'webhook_id' => $webhookId,
            ]);
            
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('PayPal signature verification failed', [
                'error' => $e->getMessage(),
                'transmission_id' => $transmissionId,
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);
        }
    }

    /**
     * Verify PayPal webhook signature using PayPal API.
     */
    private function verifySignature(Request $request, array $headers): void
    {
        $baseUrl = config('services.paypal.base_url');
        $clientId = config('services.paypal.client_id');
        $clientSecret = config('services.paypal.client_secret');

        // Get access token
        $tokenResponse = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post("{$baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (!$tokenResponse->successful()) {
            throw new \Exception('Failed to get PayPal access token');
        }

        $accessToken = $tokenResponse->json('access_token');

        // Verify webhook signature
        $verifyResponse = Http::withToken($accessToken)
            ->post("{$baseUrl}/v1/notifications/verify-webhook-signature", [
                'transmission_id' => $headers['transmission_id'],
                'transmission_time' => $headers['transmission_time'],
                'cert_url' => $headers['cert_url'],
                'auth_algo' => $headers['auth_algo'],
                'transmission_sig' => $headers['transmission_sig'],
                'webhook_id' => $headers['webhook_id'],
                'webhook_event' => $request->json()->all(),
            ]);

        if (!$verifyResponse->successful()) {
            throw new \Exception('PayPal signature verification failed');
        }

        $verificationStatus = $verifyResponse->json('verification_status');

        if ($verificationStatus !== 'SUCCESS') {
            throw new \Exception('PayPal signature verification returned: ' . $verificationStatus);
        }
    }
}

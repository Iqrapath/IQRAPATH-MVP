<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\OAuthService;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleOAuthRequests
{
    /**
     * The rate limiter instance.
     */
    protected RateLimiter $limiter;

    /**
     * Create a new middleware instance.
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 10, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            // Log rate limit violation
            $provider = $this->extractProvider($request);
            OAuthService::logRateLimitViolation($provider);

            // Calculate retry after
            $retryAfter = $this->limiter->availableIn($key);

            return redirect()->route('login')
                ->withErrors([
                    'oauth' => "Too many authentication attempts. Please try again in {$retryAfter} seconds."
                ])
                ->header('Retry-After', (string) $retryAfter)
                ->header('X-RateLimit-Limit', (string) $maxAttempts)
                ->header('X-RateLimit-Remaining', '0');
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        return sha1(
            'oauth_callback|' . $request->ip() . '|' . $request->path()
        );
    }

    /**
     * Extract provider from request.
     */
    protected function extractProvider(Request $request): string
    {
        if (str_contains($request->path(), 'google')) {
            return 'google';
        }
        if (str_contains($request->path(), 'facebook')) {
            return 'facebook';
        }
        return 'unknown';
    }

    /**
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $this->limiter->retriesLeft($key, $maxAttempts);
    }

    /**
     * Add rate limit headers to response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $remainingAttempts);

        return $response;
    }
}

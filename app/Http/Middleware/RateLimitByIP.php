<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimitByIP
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        $key = "rate_limit:{$ip}";
        $maxRequests = 60; // 60 requests per minute
        $decayMinutes = 1;

        // Get current request count
        $requests = Cache::get($key, 0);

        // Check if limit exceeded
        if ($requests >= $maxRequests) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => Cache::get("{$key}:reset_time") - now()->timestamp
            ], 429, [
                'Retry-After' => Cache::get("{$key}:reset_time") - now()->timestamp,
                'X-RateLimit-Limit' => $maxRequests,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => Cache::get("{$key}:reset_time")
            ]);
        }

        // Increment request count
        Cache::put($key, $requests + 1, now()->addMinutes($decayMinutes));

        // Set reset time if not set
        if (!Cache::has("{$key}:reset_time")) {
            Cache::put("{$key}:reset_time", now()->addMinutes($decayMinutes)->timestamp, $decayMinutes);
        }

        $response = $next($request);

        // Add rate limit headers to response
        $remaining = max(0, $maxRequests - Cache::get($key, 0));
        $response->headers->set('X-RateLimit-Limit', $maxRequests);
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        $response->headers->set('X-RateLimit-Reset', Cache::get("{$key}:reset_time"));

        return $response;
    }
}

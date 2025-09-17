<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SensorAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get sensor configuration from environment
        $allowedIps = $this->getAllowedIps();
        $requiresApiKey = config('app.sensor_require_api_key', false);
        $validApiKey = config('app.sensor_api_key');

        // Log the request for monitoring
        Log::info('Sensor request received', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'path' => $request->path(),
            'content_type' => $request->header('Content-Type'),
        ]);

        // Check IP whitelist if configured
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps)) {
            Log::warning('Sensor request from unauthorized IP', [
                'ip' => $request->ip(),
                'allowed_ips' => $allowedIps
            ]);

            return response()->json([
                'error' => 'Unauthorized IP address'
            ], 403);
        }

        // Check API key if required
        if ($requiresApiKey && $validApiKey) {
            $providedKey = $request->header('X-API-Key') ?? $request->input('api_key');

            if (!$providedKey || $providedKey !== $validApiKey) {
                Log::warning('Sensor request with invalid API key', [
                    'ip' => $request->ip(),
                    'provided_key' => $providedKey ? 'provided' : 'missing'
                ]);

                return response()->json([
                    'error' => 'Invalid or missing API key'
                ], 401);
            }
        }

        // Rate limiting for sensor endpoints
        $this->applySensorRateLimit($request);

        return $next($request);
    }

    /**
     * Get allowed IP addresses from configuration
     */
    private function getAllowedIps(): array
    {
        $ips = config('app.sensor_allowed_ips', '');

        if (empty($ips)) {
            return [];
        }

        return array_map('trim', explode(',', $ips));
    }

    /**
     * Apply rate limiting specific to sensor requests
     */
    private function applySensorRateLimit(Request $request): void
    {
        // Basic rate limiting - you could implement more sophisticated limiting
        $cacheKey = 'sensor_rate_limit:' . $request->ip();
        $requestCount = cache()->get($cacheKey, 0);

        // Allow max 1000 requests per hour per IP
        if ($requestCount > 1000) {
            Log::warning('Sensor rate limit exceeded', [
                'ip' => $request->ip(),
                'request_count' => $requestCount
            ]);

            abort(429, 'Rate limit exceeded');
        }

        cache()->put($cacheKey, $requestCount + 1, 3600); // 1 hour TTL
    }
}

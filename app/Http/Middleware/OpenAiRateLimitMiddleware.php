<?php

namespace App\Http\Middleware;

use App\Models\ChatGptApiLog;
use App\Exceptions\OpenAi\RateLimitExceededException;
use App\Exceptions\OpenAi\SpendingLimitExceededException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class OpenAiRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return $next($request);
        }

        // Check request rate limit
        $this->checkRequestRateLimit($user->id);

        // Check spending limit
        $this->checkSpendingLimit($user->id);

        return $next($request);
    }

    /**
     * Check request rate limit
     */
    protected function checkRequestRateLimit(int $userId): void
    {
        $key = "openai_requests:{$userId}";
        $maxRequests = config('services.openai.max_requests_per_minute', 10);

        $executed = RateLimiter::attempt(
            $key,
            $maxRequests,
            function() {},
            60 // 1 minute
        );

        if (!$executed) {
            $availableIn = RateLimiter::availableIn($key);
            
            throw new RateLimitExceededException(
                "Too many OpenAI requests. Please try again in {$availableIn} seconds.",
                ['retry_after' => $availableIn]
            );
        }
    }

    /**
     * Check spending limit
     */
    protected function checkSpendingLimit(int $userId): void
    {
        $cacheKey = "openai_spending:{$userId}:" . now()->format('Y-m-d-H');
        
        $hourlySpending = Cache::remember($cacheKey, 3600, function () use ($userId) {
            return ChatGptApiLog::where('user_id', $userId)
                ->where('created_at', '>=', now()->subHour())
                ->sum('estimated_cost');
        });

        $hourlyLimit = config('services.openai.hourly_spending_limit', 5.00);

        if ($hourlySpending >= $hourlyLimit) {
            throw new SpendingLimitExceededException($hourlySpending, $hourlyLimit);
        }
    }
}

/**
 * Middleware to track API usage patterns
 */
class TrackOpenAiUsageMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $user = $request->user();

        // Process request
        $response = $next($request);

        // Track usage after request completes
        if ($user) {
            $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            
            $this->trackUsage($user->id, $request, $duration);
        }

        return $response;
    }

    /**
     * Track usage patterns
     */
    protected function trackUsage(int $userId, Request $request, float $duration): void
    {
        $key = "user_api_patterns:{$userId}";
        
        $patterns = Cache::get($key, [
            'total_requests' => 0,
            'avg_duration' => 0,
            'request_types' => [],
        ]);

        $patterns['total_requests']++;
        $patterns['avg_duration'] = (
            ($patterns['avg_duration'] * ($patterns['total_requests'] - 1)) + $duration
        ) / $patterns['total_requests'];

        $requestType = $request->input('request_type', 'unknown');
        $patterns['request_types'][$requestType] = 
            ($patterns['request_types'][$requestType] ?? 0) + 1;

        Cache::put($key, $patterns, now()->addDay());
    }
}

/**
 * Middleware to validate content size before OpenAI processing
 */
class ValidateOpenAiContentMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $content = $request->input('content', '');
        $maxSize = config('services.openai.max_content_size', 50000);

        if (strlen($content) > $maxSize) {
            return response()->json([
                'error' => 'Content too large',
                'message' => "Content exceeds maximum size of {$maxSize} characters",
                'current_size' => strlen($content),
                'max_size' => $maxSize,
            ], 413);
        }

        return $next($request);
    }
}

/**
 * Middleware to ensure user has OpenAI access
 */
class EnsureOpenAiAccessMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Authentication required for OpenAI access',
            ], 401);
        }

        // Check if user has OpenAI access permission
        if (!$this->hasOpenAiAccess($user)) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You do not have permission to use OpenAI features',
            ], 403);
        }

        // Check if user's account is active
        if ($user->status !== 'active') {
            return response()->json([
                'error' => 'Account inactive',
                'message' => 'Your account is not active. Please contact support.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if user has OpenAI access
     */
    protected function hasOpenAiAccess($user): bool
    {
        // Implement your logic here
        // For example, check user role or specific permission
        
        return $user->hasPermission('use-openai') || 
               $user->hasRole(['instructor', 'admin']);
    }
}

/**
 * Middleware to add OpenAI headers to response
 */
class AddOpenAiHeadersMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add custom headers
        $response->headers->set('X-OpenAI-Integration', 'enabled');
        $response->headers->set('X-API-Version', config('services.openai.model'));
        
        // Add rate limit headers if available
        $user = $request->user();
        if ($user) {
            $key = "openai_requests:{$user->id}";
            $remaining = RateLimiter::remaining($key, 10);
            $response->headers->set('X-RateLimit-Remaining', $remaining);
        }

        return $response;
    }
}
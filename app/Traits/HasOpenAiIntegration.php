<?php

namespace App\Traits;

use App\Models\ChatGptApiLog;
use App\Services\OpenAiService;
use Illuminate\Support\Facades\Cache;

trait HasOpenAiIntegration
{
    /**
     * Get OpenAI service instance
     */
    public function openai(): OpenAiService
    {
        return app(OpenAiService::class);
    }

    /**
     * Get user's API usage statistics
     */
    public function getOpenAiUsageStats(?string $period = 'month'): array
    {
        $cacheKey = "openai_stats:{$this->id}:{$period}";
        
        return Cache::remember($cacheKey, 300, function () use ($period) {
            $dateFrom = match($period) {
                'today' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                'year' => now()->startOfYear(),
                default => now()->startOfMonth(),
            };

            $logs = ChatGptApiLog::where('user_id', $this->id)
                ->where('created_at', '>=', $dateFrom)
                ->get();

            return [
                'total_requests' => $logs->count(),
                'successful_requests' => $logs->where('success', true)->count(),
                'failed_requests' => $logs->where('success', false)->count(),
                'total_tokens' => $logs->sum('total_tokens'),
                'total_cost' => round($logs->sum('estimated_cost'), 4),
                'average_response_time' => round($logs->avg('response_time_ms'), 2),
                'by_request_type' => $logs->groupBy('request_type')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'cost' => round($group->sum('estimated_cost'), 4),
                    ];
                }),
            ];
        });
    }

    /**
     * Check if user has exceeded spending limit
     */
    public function hasExceededSpendingLimit(): bool
    {
        $hourlyLimit = config('services.openai.hourly_spending_limit', 5.00);
        
        $hourlySpending = ChatGptApiLog::where('user_id', $this->id)
            ->where('created_at', '>=', now()->subHour())
            ->sum('estimated_cost');

        return $hourlySpending >= $hourlyLimit;
    }

    /**
     * Get remaining spending allowance
     */
    public function getRemainingSpendingAllowance(): float
    {
        $hourlyLimit = config('services.openai.hourly_spending_limit', 5.00);
        
        $hourlySpending = ChatGptApiLog::where('user_id', $this->id)
            ->where('created_at', '>=', now()->subHour())
            ->sum('estimated_cost');

        return max(0, $hourlyLimit - $hourlySpending);
    }

    /**
     * Check if user can make OpenAI request
     */
    public function canMakeOpenAiRequest(): bool
    {
        // Check if user has OpenAI permission
        if (!$this->can('use-openai')) {
            return false;
        }

        // Check spending limit
        if ($this->hasExceededSpendingLimit()) {
            return false;
        }

        // Check account status
        if ($this->status !== 'active') {
            return false;
        }

        return true;
    }

    /**
     * Get user's most used request type
     */
    public function getMostUsedRequestType(): ?string
    {
        $mostUsed = ChatGptApiLog::where('user_id', $this->id)
            ->selectRaw('request_type, COUNT(*) as count')
            ->groupBy('request_type')
            ->orderByDesc('count')
            ->first();

        return $mostUsed?->request_type;
    }

    /**
     * Get user's total OpenAI cost
     */
    public function getTotalOpenAiCost(): float
    {
        return ChatGptApiLog::where('user_id', $this->id)
            ->sum('estimated_cost');
    }
}
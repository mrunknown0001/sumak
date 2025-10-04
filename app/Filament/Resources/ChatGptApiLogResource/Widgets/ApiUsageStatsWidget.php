<?php

namespace App\Filament\Resources\ChatGptApiLogResource\Widgets;

use App\Models\ChatGptApiLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApiUsageStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        // Today's stats
        $todayLogs = ChatGptApiLog::whereDate('created_at', $today)->get();
        $todayCount = $todayLogs->count();
        $todayCost = $todayLogs->sum('estimated_cost');
        $todayTokens = $todayLogs->sum('total_tokens');

        // Yesterday's stats for comparison
        $yesterday = now()->subDay()->startOfDay();
        $yesterdayCount = ChatGptApiLog::whereDate('created_at', $yesterday)->count();

        // Month stats
        $monthLogs = ChatGptApiLog::where('created_at', '>=', $thisMonth)->get();
        $monthCount = $monthLogs->count();
        $monthCost = $monthLogs->sum('estimated_cost');
        $monthTokens = $monthLogs->sum('total_tokens');

        // Success rate
        $totalRequests = ChatGptApiLog::count();
        $successfulRequests = ChatGptApiLog::where('success', true)->count();
        $successRate = $totalRequests > 0 ? ($successfulRequests / $totalRequests) * 100 : 0;

        // Calculate trends
        $countTrend = $yesterdayCount > 0 
            ? (($todayCount - $yesterdayCount) / $yesterdayCount) * 100 
            : 0;

        return [
            Stat::make('Requests Today', $todayCount)
                ->description($countTrend >= 0 
                    ? '+' . number_format($countTrend, 1) . '% from yesterday'
                    : number_format($countTrend, 1) . '% from yesterday'
                )
                ->descriptionIcon($countTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($countTrend >= 0 ? 'success' : 'danger')
                ->chart($this->getLastSevenDaysChart()),

            Stat::make('Cost Today', '$' . number_format($todayCost, 4))
                ->description('$' . number_format($monthCost, 2) . ' this month')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),

            Stat::make('Tokens Today', number_format($todayTokens))
                ->description(number_format($monthTokens) . ' this month')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('info'),

            Stat::make('Success Rate', number_format($successRate, 1) . '%')
                ->description($successfulRequests . ' of ' . $totalRequests . ' successful')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($successRate >= 95 ? 'success' : ($successRate >= 90 ? 'warning' : 'danger')),

            Stat::make('Avg Response Time', $this->getAverageResponseTime())
                ->description('Last 24 hours')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),

            Stat::make('Most Used Model', $this->getMostUsedModel())
                ->description($this->getModelUsageCount() . ' requests')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('primary'),
        ];
    }

    protected function getLastSevenDaysChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = ChatGptApiLog::whereDate('created_at', $date)->count();
            $data[] = $count;
        }
        return $data;
    }

    protected function getAverageResponseTime(): string
    {
        $avg = ChatGptApiLog::where('created_at', '>=', now()->subDay())
            ->avg('response_time_ms');

        if ($avg < 1000) {
            return round($avg) . ' ms';
        }
        return round($avg / 1000, 2) . ' s';
    }

    protected function getMostUsedModel(): string
    {
        $model = ChatGptApiLog::selectRaw('model, COUNT(*) as count')
            ->groupBy('model')
            ->orderByDesc('count')
            ->first();

        return $model?->model ?? 'N/A';
    }

    protected function getModelUsageCount(): int
    {
        $model = ChatGptApiLog::selectRaw('model, COUNT(*) as count')
            ->groupBy('model')
            ->orderByDesc('count')
            ->first();

        return $model?->count ?? 0;
    }
}
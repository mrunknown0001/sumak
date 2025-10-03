<?php

namespace App\Filament\Resources\ChatGptApiLogResource\Widgets;

use App\Models\ChatGptApiLog;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class ApiCostChartWidget extends ChartWidget
{
    protected static ?string $heading = 'API Usage & Cost Trends';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = '7days';

    protected function getData(): array
    {
        $days = match ($this->filter) {
            'today' => 1,
            '7days' => 7,
            '30days' => 30,
            '90days' => 90,
            default => 7,
        };

        $startDate = now()->subDays($days)->startOfDay();

        // Get request counts per day
        $requestData = Trend::model(ChatGptApiLog::class)
            ->between(
                start: $startDate,
                end: now(),
            )
            ->perDay()
            ->count();

        // Get costs per day
        $costData = ChatGptApiLog::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(estimated_cost) as total_cost')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get token usage per day
        $tokenData = ChatGptApiLog::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(total_tokens) as total_tokens')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = $requestData->map(fn (TrendValue $value) => $value->date);

        return [
            'datasets' => [
                [
                    'label' => 'Requests',
                    'data' => $requestData->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Cost (USD)',
                    'data' => $costData->pluck('total_cost')->map(fn ($cost) => round($cost, 4)),
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'yAxisID' => 'y1',
                ],
                [
                    'label' => 'Tokens (K)',
                    'data' => $tokenData->pluck('total_tokens')->map(fn ($tokens) => round($tokens / 1000, 2)),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'yAxisID' => 'y2',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            '7days' => 'Last 7 days',
            '30days' => 'Last 30 days',
            '90days' => 'Last 90 days',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Requests'
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Cost (USD)'
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
                'y2' => [
                    'type' => 'linear',
                    'display' => false,
                    'position' => 'right',
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }
}
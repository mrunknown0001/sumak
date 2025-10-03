<?php

namespace App\Filament\Resources\ChatGptApiLogResource\Pages;

use App\Filament\Resources\ChatGptApiLogResource;
use App\Models\ChatGptApiLog;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListChatGptApiLogs extends ListRecords
{
    protected static string $resource = ChatGptApiLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    // Implement CSV export
                    $this->exportToCSV();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ChatGptApiLogResource\Widgets\ApiUsageStatsWidget::class,
            ChatGptApiLogResource\Widgets\ApiCostChartWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Requests')
                ->badge(ChatGptApiLog::count()),

            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereDate('created_at', today())
                )
                ->badge(ChatGptApiLog::whereDate('created_at', today())->count()),

            'this_week' => Tab::make('This Week')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                )
                ->badge(ChatGptApiLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count()),

            'successful' => Tab::make('Successful')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('success', true))
                ->badge(ChatGptApiLog::where('success', true)->count())
                ->badgeColor('success'),

            'failed' => Tab::make('Failed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('success', false))
                ->badge(ChatGptApiLog::where('success', false)->count())
                ->badgeColor('danger'),

            'high_cost' => Tab::make('High Cost')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('estimated_cost', '>', 0.10)
                )
                ->badge(ChatGptApiLog::where('estimated_cost', '>', 0.10)->count())
                ->badgeColor('warning'),
        ];
    }

    protected function exportToCSV(): void
    {
        $logs = $this->getFilteredTableQuery()->get();
        
        $filename = 'api_logs_' . now()->format('Y-m-d_His') . '.csv';
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Headers
        fputcsv($handle, [
            'ID',
            'User',
            'Request Type',
            'Model',
            'Success',
            'Total Tokens',
            'Prompt Tokens',
            'Completion Tokens',
            'Cost (USD)',
            'Response Time (ms)',
            'Error Message',
            'Created At'
        ]);
        
        // Data
        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->id,
                $log->user?->name ?? 'N/A',
                $log->request_type,
                $log->model,
                $log->success ? 'Yes' : 'No',
                $log->total_tokens,
                $log->prompt_tokens,
                $log->completion_tokens,
                $log->estimated_cost,
                $log->response_time_ms,
                $log->error_message ?? '',
                $log->created_at->toDateTimeString()
            ]);
        }
        
        fclose($handle);
        exit;
    }
}
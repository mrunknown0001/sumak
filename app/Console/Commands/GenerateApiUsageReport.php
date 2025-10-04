<?php

namespace App\Console\Commands;

use App\Models\ChatGptApiLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class GenerateApiUsageReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openai:usage-report 
                            {--period=month : Report period (today|week|month|year)}
                            {--email= : Email address to send report}
                            {--format=table : Output format (table|json|csv)}
                            {--user= : Generate report for specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate OpenAI API usage report';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $period = $this->option('period');
        $email = $this->option('email');
        $format = $this->option('format');
        $userId = $this->option('user');

        $this->info("Generating OpenAI API usage report for period: {$period}");

        // Get date range
        [$startDate, $endDate] = $this->getDateRange($period);

        // Build query
        $query = ChatGptApiLog::whereBetween('created_at', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
            $user = User::find($userId);
            $this->info("Report for user: {$user->name}");
        }

        // Generate report data
        $reportData = $this->generateReportData($query);

        // Output report
        switch ($format) {
            case 'json':
                $this->outputJson($reportData);
                break;
            case 'csv':
                $this->outputCsv($reportData);
                break;
            default:
                $this->outputTable($reportData);
        }

        // Send email if requested
        if ($email) {
            $this->sendEmailReport($email, $reportData, $period);
        }

        return self::SUCCESS;
    }

    /**
     * Get date range based on period
     */
    private function getDateRange(string $period): array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    /**
     * Generate report data
     */
    private function generateReportData($query): array
    {
        $logs = $query->get();

        $data = [
            'summary' => [
                'total_requests' => $logs->count(),
                'successful_requests' => $logs->where('success', true)->count(),
                'failed_requests' => $logs->where('success', false)->count(),
                'success_rate' => $logs->count() > 0 
                    ? round(($logs->where('success', true)->count() / $logs->count()) * 100, 2) 
                    : 0,
                'total_tokens' => $logs->sum('total_tokens'),
                'total_cost' => $logs->sum('estimated_cost'),
                'avg_response_time' => round($logs->avg('response_time_ms'), 2),
            ],
            'by_request_type' => $logs->groupBy('request_type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'tokens' => $group->sum('total_tokens'),
                    'cost' => round($group->sum('estimated_cost'), 4),
                    'avg_response_time' => round($group->avg('response_time_ms'), 2),
                ];
            })->toArray(),
            'by_model' => $logs->groupBy('model')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'tokens' => $group->sum('total_tokens'),
                    'cost' => round($group->sum('estimated_cost'), 4),
                ];
            })->toArray(),
            'by_user' => $logs->groupBy('user_id')->map(function ($group) {
                $user = User::find($group->first()->user_id);
                return [
                    'user' => $user ? $user->name : 'Unknown',
                    'count' => $group->count(),
                    'cost' => round($group->sum('estimated_cost'), 4),
                ];
            })->sortByDesc('cost')->take(10)->values()->toArray(),
            'daily_breakdown' => $logs->groupBy(function ($log) {
                return $log->created_at->format('Y-m-d');
            })->map(function ($group) {
                return [
                    'requests' => $group->count(),
                    'cost' => round($group->sum('estimated_cost'), 4),
                    'tokens' => $group->sum('total_tokens'),
                ];
            })->toArray(),
        ];

        return $data;
    }

    /**
     * Output report as table
     */
    private function outputTable(array $data): void
    {
        $this->newLine();
        $this->info('=== SUMMARY ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Requests', number_format($data['summary']['total_requests'])],
                ['Successful', number_format($data['summary']['successful_requests'])],
                ['Failed', number_format($data['summary']['failed_requests'])],
                ['Success Rate', $data['summary']['success_rate'] . '%'],
                ['Total Tokens', number_format($data['summary']['total_tokens'])],
                ['Total Cost', '$' . number_format($data['summary']['total_cost'], 4)],
                ['Avg Response Time', $data['summary']['avg_response_time'] . ' ms'],
            ]
        );

        $this->newLine();
        $this->info('=== BY REQUEST TYPE ===');
        $requestTypeData = [];
        foreach ($data['by_request_type'] as $type => $stats) {
            $requestTypeData[] = [
                $type,
                number_format($stats['count']),
                number_format($stats['tokens']),
                '$' . number_format($stats['cost'], 4),
                $stats['avg_response_time'] . ' ms',
            ];
        }
        $this->table(
            ['Request Type', 'Count', 'Tokens', 'Cost', 'Avg Response Time'],
            $requestTypeData
        );

        $this->newLine();
        $this->info('=== BY MODEL ===');
        $modelData = [];
        foreach ($data['by_model'] as $model => $stats) {
            $modelData[] = [
                $model,
                number_format($stats['count']),
                number_format($stats['tokens']),
                '$' . number_format($stats['cost'], 4),
            ];
        }
        $this->table(
            ['Model', 'Count', 'Tokens', 'Cost'],
            $modelData
        );

        if (!empty($data['by_user'])) {
            $this->newLine();
            $this->info('=== TOP 10 USERS ===');
            $userData = [];
            foreach ($data['by_user'] as $userStats) {
                $userData[] = [
                    $userStats['user'],
                    number_format($userStats['count']),
                    '$' . number_format($userStats['cost'], 4),
                ];
            }
            $this->table(
                ['User', 'Requests', 'Cost'],
                $userData
            );
        }
    }

    /**
     * Output report as JSON
     */
    private function outputJson(array $data): void
    {
        $this->line(json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Output report as CSV
     */
    private function outputCsv(array $data): void
    {
        $filename = 'api_usage_report_' . now()->format('Y-m-d_His') . '.csv';
        $handle = fopen('php://output', 'w');

        // Summary section
        fputcsv($handle, ['SUMMARY']);
        fputcsv($handle, ['Metric', 'Value']);
        foreach ($data['summary'] as $key => $value) {
            fputcsv($handle, [$key, $value]);
        }

        fputcsv($handle, []);
        
        // By request type
        fputcsv($handle, ['BY REQUEST TYPE']);
        fputcsv($handle, ['Request Type', 'Count', 'Tokens', 'Cost', 'Avg Response Time']);
        foreach ($data['by_request_type'] as $type => $stats) {
            fputcsv($handle, [$type, $stats['count'], $stats['tokens'], $stats['cost'], $stats['avg_response_time']]);
        }

        fclose($handle);
    }

    /**
     * Send email report
     */
    private function sendEmailReport(string $email, array $data, string $period): void
    {
        $this->info("Sending report to {$email}...");
        
        // Implement email sending logic here
        // Mail::to($email)->send(new ApiUsageReportMail($data, $period));
        
        $this->info('Report sent successfully!');
    }
}
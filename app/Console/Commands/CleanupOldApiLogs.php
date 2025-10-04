<?php

namespace App\Console\Commands;

use App\Models\ChatGptApiLog;
use Illuminate\Console\Command;

class CleanupOldApiLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openai:cleanup-logs 
                            {--days=90 : Number of days to keep logs}
                            {--keep-failed : Keep failed request logs}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old OpenAI API logs to save database space';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $keepFailed = $this->option('keep-failed');
        $dryRun = $this->option('dry-run');

        $cutoffDate = now()->subDays($days);

        $this->info("Cleaning up API logs older than {$days} days (before {$cutoffDate->toDateTimeString()})");

        // Build query
        $query = ChatGptApiLog::where('created_at', '<', $cutoffDate);

        if ($keepFailed) {
            $query->where('success', true);
            $this->info('Keeping failed request logs...');
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No logs to clean up.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("DRY RUN: Would delete {$count} log entries");
            
            // Show statistics
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Logs to Delete', number_format($count)],
                    ['Total Tokens', number_format($query->sum('total_tokens'))],
                    ['Total Cost', '$' . number_format($query->sum('estimated_cost'), 2)],
                ]
            );

            return self::SUCCESS;
        }

        // Confirm deletion
        if (!$this->confirm("This will delete {$count} log entries. Continue?")) {
            $this->info('Cleanup cancelled.');
            return self::SUCCESS;
        }

        // Get statistics before deletion
        $stats = [
            'count' => $count,
            'tokens' => $query->sum('total_tokens'),
            'cost' => $query->sum('estimated_cost'),
        ];

        // Perform deletion in chunks
        $this->withProgressBar($count, function () use ($query) {
            $query->chunk(1000, function ($logs) {
                $ids = $logs->pluck('id')->toArray();
                ChatGptApiLog::whereIn('id', $ids)->delete();
            });
        });

        $this->newLine(2);
        $this->info('Cleanup completed successfully!');
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Deleted Logs', number_format($stats['count'])],
                ['Deleted Tokens', number_format($stats['tokens'])],
                ['Deleted Cost Records', '$' . number_format($stats['cost'], 2)],
            ]
        );

        return self::SUCCESS;
    }
}
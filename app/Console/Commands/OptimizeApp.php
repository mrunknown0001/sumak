<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OptimizeApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'optimize:app';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize the application by clearing caches and optimizing configurations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('cache:clear');
        $this->call('config:cache');
        $this->call('optimize:clear');
        $this->call('filament:optimize-clear');
        $this->call('filament:optimize');
        $this->call('optimize');
        $this->info('Application optimized successfully.');
    }
}

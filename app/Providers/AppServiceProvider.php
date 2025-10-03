<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\OpenAiService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OpenAiService::class, function ($app) {
            return new OpenAiService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

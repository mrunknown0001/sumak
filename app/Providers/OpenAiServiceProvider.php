<?php

namespace App\Providers;

use App\Services\OpenAiService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

class OpenAiServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\OpenAi\ContentAnalyzed::class => [
            \App\Listeners\OpenAi\ProcessAnalyzedContent::class,
        ],
        \App\Events\OpenAi\TosGenerated::class => [
            \App\Listeners\OpenAi\TriggerQuizGeneration::class,
        ],
        \App\Events\OpenAi\QuizGenerated::class => [
            \App\Listeners\OpenAi\NotifyQuizReady::class,
        ],
        \App\Events\OpenAi\QuestionRegenerated::class => [
            \App\Listeners\OpenAi\UpdateItemBank::class,
        ],
        \App\Events\OpenAi\FeedbackGenerated::class => [
            \App\Listeners\OpenAi\NotifyFeedbackReady::class,
        ],
        \App\Events\OpenAi\OpenAiRequestFailed::class => [
            \App\Listeners\OpenAi\HandleFailedRequest::class,
        ],
        \App\Events\OpenAi\SpendingLimitWarning::class => [
            \App\Listeners\OpenAi\SendSpendingAlert::class,
        ],
    ];
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind OpenAiService as singleton
        $this->app->singleton(OpenAiService::class, function ($app) {
            return new OpenAiService();
        });

        // Alias for easier access
        $this->app->alias(OpenAiService::class, 'openai');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure rate limiting for OpenAI API calls
        $this->configureRateLimiting();

        // Register custom validation rules if needed
        $this->registerValidationRules();

        // Schedule cleanup of old logs
        $this->scheduleLogCleanup();
    }

    /**
     * Configure rate limiting for OpenAI API
     */
    protected function configureRateLimiting(): void
    {
        // User-level rate limiting
        RateLimiter::for('openai-user', function ($job) {
            return Limit::perMinute(10)
                ->by(optional($job->user)->id ?: $job->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many OpenAI requests. Please slow down.'
                    ], 429);
                });
        });

        // Global rate limiting
        RateLimiter::for('openai-global', function () {
            return Limit::perMinute(60);
        });

        // Cost-based rate limiting
        RateLimiter::for('openai-cost', function ($job) {
            $userId = optional($job->user)->id;
            
            if (!$userId) {
                return Limit::none();
            }

            // Get user's spending in last hour
            $hourlySpending = \App\Models\ChatGptApiLog::where('user_id', $userId)
                ->where('created_at', '>=', now()->subHour())
                ->sum('estimated_cost');

            $spendingLimit = config('services.openai.hourly_spending_limit', 5.00);

            if ($hourlySpending >= $spendingLimit) {
                return Limit::none()->response(function () use ($spendingLimit) {
                    return response()->json([
                        'message' => "Hourly spending limit of \${$spendingLimit} reached. Please try again later."
                    ], 429);
                });
            }

            return Limit::none();
        });
    }

    /**
     * Register custom validation rules
     */
    protected function registerValidationRules(): void
    {
        \Illuminate\Support\Facades\Validator::extend('openai_content_size', function ($attribute, $value, $parameters, $validator) {
            $maxSize = $parameters[0] ?? 50000; // Default 50k characters
            return strlen($value) <= $maxSize;
        });

        \Illuminate\Support\Facades\Validator::replacer('openai_content_size', function ($message, $attribute, $rule, $parameters) {
            $maxSize = $parameters[0] ?? 50000;
            return str_replace(':max', number_format($maxSize), $message);
        });
    }

    /**
     * Schedule cleanup of old API logs
     */
    protected function scheduleLogCleanup(): void
    {
        // This will be called by the scheduler
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\CleanupOldApiLogs::class,
                \App\Console\Commands\GenerateApiUsageReport::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            OpenAiService::class,
            'openai',
        ];
    }
}
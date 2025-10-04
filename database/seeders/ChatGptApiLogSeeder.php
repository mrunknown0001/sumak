<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChatGptApiLog;
use App\Models\User;
use Carbon\Carbon;

class ChatGptApiLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        $requestTypes = [
            'content_analysis' => 0.3,
            'tos_generation' => 0.2,
            'quiz_generation' => 0.25,
            'feedback_generation' => 0.15,
            'question_reword' => 0.08,
            'obtl_parsing' => 0.02,
        ];

        $totalLogs = 500;
        $this->command->info("Generating {$totalLogs} API logs...");

        $progressBar = $this->command->getOutput()->createProgressBar($totalLogs);

        for ($i = 0; $i < $totalLogs; $i++) {
            $user = $users->random();
            $requestType = $this->getWeightedRequestType($requestTypes);
            
            $createdAt = Carbon::now()
                ->subDays(rand(0, 90))
                ->subHours(rand(0, 23))
                ->subMinutes(rand(0, 59));

            ChatGptApiLog::create([
                'user_id' => $user->id,
                'request_type' => $requestType,
                'model' => $this->getRandomModel(),
                'total_tokens' => $this->getTokensForRequestType($requestType),
                'prompt_tokens' => function ($tokens) {
                    return (int)($tokens * 0.6);
                },
                'completion_tokens' => function ($tokens) {
                    return (int)($tokens * 0.4);
                },
                'response_time_ms' => rand(500, 5000),
                'estimated_cost' => function ($tokens, $model) {
                    return $this->calculateCost($tokens, $model);
                },
                'success' => rand(1, 100) > 5, // 95% success rate
                'error_message' => rand(1, 100) <= 5 ? $this->getRandomError() : null,
                'created_at' => $createdAt,
            ]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->newLine();
        $this->command->info('API logs generated successfully!');
    }

    /**
     * Get weighted request type
     */
    private function getWeightedRequestType(array $types): string
    {
        $rand = mt_rand() / mt_getrandmax();
        $cumulative = 0;

        foreach ($types as $type => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $type;
            }
        }

        return array_key_first($types);
    }

    /**
     * Get random model
     */
    private function getRandomModel(): string
    {
        $models = ['gpt-4o-mini', 'gpt-4o', 'gpt-4-turbo'];
        $weights = [0.7, 0.2, 0.1]; // Mostly using gpt-4o-mini
        
        $rand = mt_rand() / mt_getrandmax();
        $cumulative = 0;

        foreach ($weights as $index => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $models[$index];
            }
        }

        return 'gpt-4o-mini';
    }

    /**
     * Get token count for request type
     */
    private function getTokensForRequestType(string $type): int
    {
        return match($type) {
            'content_analysis' => rand(1000, 3000),
            'tos_generation' => rand(800, 2000),
            'quiz_generation' => rand(2000, 5000),
            'feedback_generation' => rand(1500, 3500),
            'question_reword' => rand(500, 1200),
            'obtl_parsing' => rand(600, 1500),
            default => rand(500, 2000),
        };
    }

    /**
     * Calculate cost based on tokens and model
     */
    private function calculateCost(int $tokens, string $model): float
    {
        $costPer1k = match($model) {
            'gpt-4o-mini' => 0.00015,
            'gpt-4o' => 0.0025,
            'gpt-4-turbo' => 0.01,
            default => 0.00015
        };

        return ($tokens / 1000) * $costPer1k;
    }

    /**
     * Get random error message
     */
    private function getRandomError(): string
    {
        $errors = [
            'OpenAI API error: Rate limit exceeded',
            'OpenAI API error: Server error',
            'OpenAI API error: Timeout',
            'OpenAI API error: Invalid request',
            'Network error: Connection timeout',
        ];

        return $errors[array_rand($errors)];
    }
}
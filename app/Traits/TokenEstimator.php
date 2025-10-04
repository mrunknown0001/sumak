<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait TokenEstimator
{
    /**
     * Estimate token count for text
     * Rough estimate: ~4 characters per token
     */
    public function estimateTokens(?string $text = null): int
    {
        $text = $text ?? $this->getContentForTokenEstimation();
        
        if (empty($text)) {
            return 0;
        }

        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Estimate cost for content
     */
    public function estimateCost(?string $model = null): float
    {
        $tokens = $this->estimateTokens();
        $model = $model ?? config('services.openai.model');

        $costPer1k = match($model) {
            'gpt-4o-mini' => 0.00015,
            'gpt-4o' => 0.0025,
            'gpt-4-turbo' => 0.01,
            default => 0.00015
        };

        return ($tokens / 1000) * $costPer1k;
    }

    /**
     * Check if content exceeds token limit
     */
    public function exceedsTokenLimit(?int $limit = null): bool
    {
        $limit = $limit ?? config('services.openai.max_tokens', 50000);
        
        return $this->estimateTokens() > $limit;
    }

    /**
     * Get content for token estimation
     * Override this method in your model
     */
    protected function getContentForTokenEstimation(): string
    {
        return $this->content ?? $this->text ?? '';
    }
}
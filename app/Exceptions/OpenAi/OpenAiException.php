<?php

namespace App\Exceptions\OpenAi;

use Exception;

/**
 * Base exception for OpenAI service errors
 */
class OpenAiException extends Exception
{
    protected array $context = [];

    public function __construct(
        string $message = "",
        int $code = 0,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function report(): void
    {
        \Illuminate\Support\Facades\Log::error($this->getMessage(), [
            'exception' => get_class($this),
            'code' => $this->getCode(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ]);
    }
}

/**
 * Exception for API key issues
 */
class InvalidApiKeyException extends OpenAiException
{
    public function __construct(string $message = "Invalid OpenAI API key")
    {
        parent::__construct($message, 401);
    }
}

/**
 * Exception for rate limit errors
 */
class RateLimitExceededException extends OpenAiException
{
    public function __construct(
        string $message = "OpenAI API rate limit exceeded",
        array $context = []
    ) {
        parent::__construct($message, 429, null, $context);
    }

    public function render()
    {
        return response()->json([
            'error' => 'Rate limit exceeded',
            'message' => $this->getMessage(),
            'retry_after' => $this->context['retry_after'] ?? 60,
        ], 429);
    }
}

/**
 * Exception for token limit errors
 */
class TokenLimitExceededException extends OpenAiException
{
    public function __construct(
        int $tokenCount,
        int $limit = 50000
    ) {
        $message = "Content exceeds token limit. Content has {$tokenCount} tokens, limit is {$limit}";
        parent::__construct($message, 413, null, [
            'token_count' => $tokenCount,
            'limit' => $limit
        ]);
    }

    public function render()
    {
        return response()->json([
            'error' => 'Token limit exceeded',
            'message' => $this->getMessage(),
            'token_count' => $this->context['token_count'],
            'limit' => $this->context['limit'],
        ], 413);
    }
}

/**
 * Exception for spending limit errors
 */
class SpendingLimitExceededException extends OpenAiException
{
    public function __construct(
        float $currentSpending,
        float $limit
    ) {
        $message = "API spending limit exceeded. Current: \${$currentSpending}, Limit: \${$limit}";
        parent::__construct($message, 402, null, [
            'current_spending' => $currentSpending,
            'limit' => $limit
        ]);
    }

    public function render()
    {
        return response()->json([
            'error' => 'Spending limit exceeded',
            'message' => $this->getMessage(),
            'current_spending' => $this->context['current_spending'],
            'limit' => $this->context['limit'],
        ], 402);
    }
}

/**
 * Exception for invalid response format
 */
class InvalidResponseException extends OpenAiException
{
    public function __construct(
        string $expectedFormat,
        string $actualFormat
    ) {
        $message = "Invalid response format. Expected: {$expectedFormat}, Got: {$actualFormat}";
        parent::__construct($message, 422, null, [
            'expected' => $expectedFormat,
            'actual' => $actualFormat
        ]);
    }
}

/**
 * Exception for timeout errors
 */
class TimeoutException extends OpenAiException
{
    public function __construct(
        int $timeout,
        string $operation = "API request"
    ) {
        $message = "{$operation} timed out after {$timeout} seconds";
        parent::__construct($message, 408, null, [
            'timeout' => $timeout,
            'operation' => $operation
        ]);
    }

    public function render()
    {
        return response()->json([
            'error' => 'Request timeout',
            'message' => $this->getMessage(),
            'timeout' => $this->context['timeout'],
        ], 408);
    }
}

/**
 * Exception for content moderation issues
 */
class ContentModerationException extends OpenAiException
{
    public function __construct(
        string $reason = "Content violates usage policies"
    ) {
        parent::__construct($reason, 400, null, ['reason' => $reason]);
    }

    public function render()
    {
        return response()->json([
            'error' => 'Content moderation failed',
            'message' => $this->getMessage(),
        ], 400);
    }
}

/**
 * Exception for regeneration limit
 */
class RegenerationLimitException extends OpenAiException
{
    public function __construct(
        int $currentCount = 3,
        int $maxCount = 3
    ) {
        $message = "Question regeneration limit reached ({$currentCount}/{$maxCount})";
        parent::__construct($message, 403, null, [
            'current_count' => $currentCount,
            'max_count' => $maxCount
        ]);
    }

    public function render()
    {
        return response()->json([
            'error' => 'Regeneration limit reached',
            'message' => $this->getMessage(),
            'current_count' => $this->context['current_count'],
            'max_count' => $this->context['max_count'],
        ], 403);
    }
}

/**
 * Exception for model unavailable
 */
class ModelUnavailableException extends OpenAiException
{
    public function __construct(string $model)
    {
        $message = "OpenAI model '{$model}' is currently unavailable";
        parent::__construct($message, 503, null, ['model' => $model]);
    }

    public function render()
    {
        return response()->json([
            'error' => 'Model unavailable',
            'message' => $this->getMessage(),
            'model' => $this->context['model'],
        ], 503);
    }
}
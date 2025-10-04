<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * OpenAI Facade for easier access to OpenAI service
 * 
 * @method static array analyzeContent(string $content, ?string $obtlContext = null)
 * @method static array generateToS(array $learningOutcomes, string $materialSummary, int $totalItems = 20)
 * @method static array generateQuizQuestions(array $tosItems, string $materialContent, int $questionCount = 20)
 * @method static array rewordQuestion(string $originalQuestion, array $originalOptions, int $regenerationCount)
 * @method static array generateFeedback(array $quizAttemptData, array $userMasteryData)
 * @method static array parseObtlDocument(string $obtlContent)
 * @method static array getUserApiStats(?int $userId = null, ?string $dateFrom = null)
 * 
 * @see \App\Services\OpenAiService
 */
class OpenAI extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'openai';
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

if (!function_exists('openai')) {
    /**
     * Get OpenAI service instance
     *
     * @return \App\Services\OpenAiService
     */
    function openai()
    {
        return app('openai');
    }
}

if (!function_exists('analyze_content')) {
    /**
     * Analyze content using OpenAI
     *
     * @param string $content
     * @param string|null $obtlContext
     * @return array
     */
    function analyze_content(string $content, ?string $obtlContext = null): array
    {
        return openai()->analyzeContent($content, $obtlContext);
    }
}

if (!function_exists('generate_tos')) {
    /**
     * Generate Table of Specification
     *
     * @param array $learningOutcomes
     * @param string $materialSummary
     * @param int $totalItems
     * @return array
     */
    function generate_tos(
        array $learningOutcomes, 
        string $materialSummary, 
        int $totalItems = 20
    ): array {
        return openai()->generateToS($learningOutcomes, $materialSummary, $totalItems);
    }
}

if (!function_exists('generate_quiz')) {
    /**
     * Generate quiz questions
     *
     * @param array $tosItems
     * @param string $materialContent
     * @param int $questionCount
     * @return array
     */
    function generate_quiz(
        array $tosItems, 
        string $materialContent, 
        int $questionCount = 20
    ): array {
        return openai()->generateQuizQuestions($tosItems, $materialContent, $questionCount);
    }
}

if (!function_exists('reword_question')) {
    /**
     * Reword a quiz question
     *
     * @param string $originalQuestion
     * @param array $originalOptions
     * @param int $regenerationCount
     * @return array
     */
    function reword_question(
        string $originalQuestion, 
        array $originalOptions, 
        int $regenerationCount
    ): array {
        return openai()->rewordQuestion($originalQuestion, $originalOptions, $regenerationCount);
    }
}

if (!function_exists('generate_feedback')) {
    /**
     * Generate personalized feedback
     *
     * @param array $quizAttemptData
     * @param array $userMasteryData
     * @return array
     */
    function generate_feedback(
        array $quizAttemptData, 
        array $userMasteryData
    ): array {
        return openai()->generateFeedback($quizAttemptData, $userMasteryData);
    }
}

if (!function_exists('parse_obtl')) {
    /**
     * Parse OBTL document
     *
     * @param string $obtlContent
     * @return array
     */
    function parse_obtl(string $obtlContent): array
    {
        return openai()->parseObtlDocument($obtlContent);
    }
}

if (!function_exists('openai_stats')) {
    /**
     * Get OpenAI usage statistics
     *
     * @param int|null $userId
     * @param string|null $dateFrom
     * @return array
     */
    function openai_stats(?int $userId = null, ?string $dateFrom = null): array
    {
        return openai()->getUserApiStats($userId, $dateFrom);
    }
}

if (!function_exists('estimate_tokens')) {
    /**
     * Estimate token count for a string
     * Rough estimate: ~4 characters per token
     *
     * @param string $text
     * @return int
     */
    function estimate_tokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }
}

if (!function_exists('estimate_cost')) {
    /**
     * Estimate cost for token count
     *
     * @param int $tokens
     * @param string $model
     * @return float
     */
    function estimate_cost(int $tokens, string $model = 'gpt-4o-mini'): float
    {
        $costPer1k = match($model) {
            'gpt-4o-mini' => 0.00015,
            'gpt-4o' => 0.0025,
            'gpt-4-turbo' => 0.01,
            default => 0.00015
        };

        return ($tokens / 1000) * $costPer1k;
    }
}

// ============================================
// REGISTER FACADE IN config/app.php
// ============================================

/*
Add this to your config/app.php 'aliases' array:

'OpenAI' => App\Facades\OpenAI::class,

Then you can use it like:

use OpenAI;

$analysis = OpenAI::analyzeContent($content);

OR simply:

$analysis = openai()->analyzeContent($content);

OR using helpers:

$analysis = analyze_content($content);
*/
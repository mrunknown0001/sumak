<?php

namespace App\Events\OpenAi;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base event for OpenAI operations
 */
abstract class OpenAiEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $requestType,
        public array $metadata = []
    ) {}
}

/**
 * Event fired when content analysis is completed
 */
class ContentAnalyzed extends OpenAiEvent
{
    public function __construct(
        int $userId,
        public int $materialId,
        public array $analysisResults,
        array $metadata = []
    ) {
        parent::__construct($userId, 'content_analysis', $metadata);
    }
}

/**
 * Event fired when ToS is generated
 */
class TosGenerated extends OpenAiEvent
{
    public function __construct(
        int $userId,
        public int $materialId,
        public int $tosId,
        public array $tosData,
        array $metadata = []
    ) {
        parent::__construct($userId, 'tos_generation', $metadata);
    }
}

/**
 * Event fired when quiz is generated
 */
class QuizGenerated extends OpenAiEvent
{
    public function __construct(
        int $userId,
        public int $quizId,
        public int $questionCount,
        array $metadata = []
    ) {
        parent::__construct($userId, 'quiz_generation', $metadata);
    }
}

/**
 * Event fired when question is regenerated
 */
class QuestionRegenerated extends OpenAiEvent
{
    public function __construct(
        int $userId,
        public int $originalQuestionId,
        public int $newQuestionId,
        public int $regenerationCount,
        array $metadata = []
    ) {
        parent::__construct($userId, 'question_reword', $metadata);
    }
}

/**
 * Event fired when feedback is generated
 */
class FeedbackGenerated extends OpenAiEvent
{
    public function __construct(
        int $userId,
        public int $quizAttemptId,
        public int $feedbackId,
        array $metadata = []
    ) {
        parent::__construct($userId, 'feedback_generation', $metadata);
    }
}

/**
 * Event fired when OpenAI request fails
 */
class OpenAiRequestFailed extends OpenAiEvent
{
    public function __construct(
        int $userId,
        string $requestType,
        public string $errorMessage,
        public int $attemptNumber,
        array $metadata = []
    ) {
        parent::__construct($userId, $requestType, $metadata);
    }
}

/**
 * Event fired when spending limit is approaching
 */
class SpendingLimitWarning extends OpenAiEvent
{
    public function __construct(
        int $userId,
        public float $currentSpending,
        public float $limit,
        public float $percentage,
        array $metadata = []
    ) {
        parent::__construct($userId, 'spending_warning', $metadata);
    }
}
<?php

namespace App\Listeners\OpenAi;

use App\Events\OpenAi\ContentAnalyzed;
use App\Jobs\GenerateQuizJob;
use Illuminate\Support\Facades\Log;

/**
 * Listener for content analyzed event
 */
class ProcessAnalyzedContent
{
    public function handle(ContentAnalyzed $event): void
    {
        Log::info("Content analyzed for material {$event->materialId}", [
            'user_id' => $event->userId,
            'key_concepts_count' => count($event->analysisResults['key_concepts'] ?? [])
        ]);

        // Additional processing if needed
        // For example, notify user, update statistics, etc.
    }
}

/**
 * Listener for ToS generated event
 */
class TriggerQuizGeneration
{
    public function handle(\App\Events\OpenAi\TosGenerated $event): void
    {
        Log::info("ToS generated, triggering quiz generation", [
            'material_id' => $event->materialId,
            'tos_id' => $event->tosId
        ]);

        // Dispatch quiz generation job
        GenerateQuizJob::dispatch($event->materialId, $event->tosId);
    }
}

/**
 * Listener for quiz generated event
 */
class NotifyQuizReady
{
    public function handle(\App\Events\OpenAi\QuizGenerated $event): void
    {
        Log::info("Quiz generated and ready", [
            'quiz_id' => $event->quizId,
            'question_count' => $event->questionCount
        ]);

        // Send notification to user
        // User::find($event->userId)->notify(new QuizReadyNotification($event->quizId));
    }
}

/**
 * Listener for question regenerated event
 */
class UpdateItemBank
{
    public function handle(\App\Events\OpenAi\QuestionRegenerated $event): void
    {
        Log::info("Question regenerated", [
            'original_id' => $event->originalQuestionId,
            'new_id' => $event->newQuestionId,
            'count' => $event->regenerationCount
        ]);

        // Update item bank statistics or perform other actions
    }
}

/**
 * Listener for feedback generated event
 */
class NotifyFeedbackReady
{
    public function handle(\App\Events\OpenAi\FeedbackGenerated $event): void
    {
        Log::info("Feedback generated", [
            'quiz_attempt_id' => $event->quizAttemptId,
            'feedback_id' => $event->feedbackId
        ]);

        // Notify user that feedback is ready
        // User::find($event->userId)->notify(new FeedbackReadyNotification($event->feedbackId));
    }
}

/**
 * Listener for failed OpenAI requests
 */
class HandleFailedRequest
{
    public function handle(\App\Events\OpenAi\OpenAiRequestFailed $event): void
    {
        Log::error("OpenAI request failed", [
            'user_id' => $event->userId,
            'request_type' => $event->requestType,
            'error' => $event->errorMessage,
            'attempt' => $event->attemptNumber
        ]);

        // Send alert if multiple failures
        if ($event->attemptNumber >= 3) {
            // Alert admin or send notification
            // Admin::notify(new OpenAiRequestFailedAlert($event));
        }
    }
}

/**
 * Listener for spending limit warnings
 */
class SendSpendingAlert
{
    public function handle(\App\Events\OpenAi\SpendingLimitWarning $event): void
    {
        Log::warning("User approaching spending limit", [
            'user_id' => $event->userId,
            'current' => $event->currentSpending,
            'limit' => $event->limit,
            'percentage' => $event->percentage
        ]);

        // Send notification to user
        if ($event->percentage >= 90) {
            // User::find($event->userId)->notify(new SpendingLimitWarningNotification($event));
        }

        // Send alert to admin if critical
        if ($event->percentage >= 95) {
            // Admin::notify(new CriticalSpendingAlert($event));
        }
    }
}

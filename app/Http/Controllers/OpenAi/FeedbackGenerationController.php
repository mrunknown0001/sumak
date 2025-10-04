<?php

namespace App\Http\Controllers\OpenAi;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\Feedback;
use App\Jobs\GenerateFeedbackJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FeedbackGenerationController extends Controller
{
    /**
     * Generate feedback for quiz attempt
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quiz_attempt_id' => 'required|exists:quiz_attempts,id',
        ]);

        $attempt = QuizAttempt::findOrFail($validated['quiz_attempt_id']);
        $this->authorize('view', $attempt);

        // Check if feedback already exists
        if ($attempt->feedback) {
            return response()->json([
                'message' => 'Feedback already exists',
                'data' => $attempt->feedback,
            ], 200);
        }

        // Dispatch feedback generation job
        GenerateFeedbackJob::dispatch($attempt->id);

        return response()->json([
            'message' => 'Feedback generation started',
            'quiz_attempt_id' => $attempt->id,
        ], 202);
    }

    /**
     * Get feedback by quiz attempt
     */
    public function getByAttempt(QuizAttempt $quizAttempt): JsonResponse
    {
        $this->authorize('view', $quizAttempt);

        $feedback = $quizAttempt->feedback;

        if (!$feedback) {
            return response()->json([
                'message' => 'Feedback not found or still being generated',
            ], 404);
        }

        return $this->show($feedback);
    }

    /**
     * Get feedback details
     */
    public function show(Feedback $feedback): JsonResponse
    {
        $this->authorize('view', $feedback);

        return response()->json([
            'data' => [
                'id' => $feedback->id,
                'quiz_attempt_id' => $feedback->quiz_attempt_id,
                'overall_feedback' => $feedback->feedback_text,
                'strengths' => json_decode($feedback->strengths),
                'areas_for_improvement' => json_decode($feedback->weaknesses),
                'recommendations' => json_decode($feedback->recommendations),
                'next_steps' => json_decode($feedback->next_steps),
                'motivational_message' => $feedback->motivational_message,
                'generated_at' => $feedback->generated_at,
            ],
        ]);
    }
}
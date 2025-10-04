<?php

namespace App\Http\Controllers\OpenAi;

use App\Http\Controllers\Controller;
use App\Models\QuizQuestion;
use App\Models\QuestionRegeneration;
use App\Jobs\RegenerateQuestionJob;
use App\Exceptions\OpenAi\RegenerationLimitException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuestionRegenerationController extends Controller
{
    /**
     * Regenerate question
     */
    public function regenerate(QuizQuestion $question): JsonResponse
    {
        $this->authorize('update', $question->quiz);

        // Check regeneration limit
        $regenerationCount = QuestionRegeneration::where('original_question_id', $question->id)
            ->count();

        if ($regenerationCount >= 3) {
            throw new RegenerationLimitException($regenerationCount, 3);
        }

        // Dispatch regeneration job
        RegenerateQuestionJob::dispatch($question->id, auth()->id());

        return response()->json([
            'message' => 'Question regeneration started',
            'question_id' => $question->id,
            'regeneration_count' => $regenerationCount + 1,
            'remaining' => 3 - ($regenerationCount + 1),
        ], 202);
    }

    /**
     * Get regeneration history
     */
    public function getRegenerations(QuizQuestion $question): JsonResponse
    {
        $this->authorize('view', $question->quiz);

        $regenerations = QuestionRegeneration::where('original_question_id', $question->id)
            ->with('regeneratedQuestion.options')
            ->orderBy('regeneration_count')
            ->get();

        return response()->json([
            'data' => $regenerations->map(function ($r) {
                return [
                    'id' => $r->id,
                    'regeneration_count' => $r->regeneration_count,
                    'regeneration_date' => $r->regeneration_date,
                    'maintains_equivalence' => $r->maintains_equivalence,
                    'question' => [
                        'id' => $r->regeneratedQuestion->id,
                        'question_text' => $r->regeneratedQuestion->question_text,
                        'options' => $r->regeneratedQuestion->options,
                    ],
                ];
            }),
        ]);
    }

    /**
     * Check if question can be regenerated
     */
    public function canRegenerate(QuizQuestion $question): JsonResponse
    {
        $this->authorize('view', $question->quiz);

        $regenerationCount = QuestionRegeneration::where('original_question_id', $question->id)
            ->count();

        return response()->json([
            'can_regenerate' => $regenerationCount < 3,
            'current_count' => $regenerationCount,
            'max_count' => 3,
            'remaining' => max(0, 3 - $regenerationCount),
        ]);
    }
}

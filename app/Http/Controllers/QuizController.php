<?php

namespace App\Http\Controllers;

use App\Models\Subtopic;
use App\Models\QuizAttempt;
use App\Models\Response;
use App\Models\StudentAbility;
use App\Services\IrtService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    protected IrtService $irtService;

    public function __construct(IrtService $irtService)
    {
        $this->irtService = $irtService;
    }

    /**
     * Start a new quiz for a subtopic
     */
    public function start(Request $request, Subtopic $subtopic): JsonResponse
    {
        $validated = $request->validate([
            'is_adaptive' => 'boolean',
        ]);

        $userId = auth()->id();
        $isAdaptive = $validated['is_adaptive'] ?? false;

        // Get appropriate questions
        if ($isAdaptive) {
            $studentAbility = StudentAbility::firstOrCreate(
                ['user_id' => $userId, 'subtopic_id' => $subtopic->id],
                ['theta' => 0, 'attempts_count' => 0, 'last_updated' => now()]
            );

            // Get adaptive questions based on student ability
            $questions = $subtopic->items()
                ->whereBetween('difficulty_b', [
                    $studentAbility->theta - 1,
                    $studentAbility->theta + 1
                ])
                ->inRandomOrder()
                ->limit(20)
                ->get();
        } else {
            // Get initial questions
            $questions = $subtopic->items()
                ->inRandomOrder()
                ->limit(20)
                ->get();
        }

        if ($questions->isEmpty()) {
            return response()->json([
                'message' => 'No questions available for this subtopic',
            ], 404);
        }

        // Get next attempt number
        $attemptNumber = QuizAttempt::where('user_id', $userId)
            ->where('subtopic_id', $subtopic->id)
            ->max('attempt_number') + 1;

        // Create quiz attempt
        $attempt = QuizAttempt::create([
            'user_id' => $userId,
            'subtopic_id' => $subtopic->id,
            'attempt_number' => $attemptNumber,
            'total_questions' => $questions->count(),
            'started_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'attempt_id' => $attempt->id,
                'subtopic' => $subtopic->name,
                'attempt_number' => $attemptNumber,
                'total_questions' => $questions->count(),
                'time_limit_per_question' => 60,
                'is_adaptive' => $isAdaptive,
                'questions' => $questions->map(fn($q) => [
                    'id' => $q->id,
                    'question' => $q->question,
                    'options' => collect($q->options)->map(fn($opt) => [
                        'letter' => $opt['option_letter'],
                        'text' => $opt['option_text'],
                    ]),
                    'time_limit' => $q->time_estimate_seconds,
                ]),
            ],
        ], 201);
    }

    /**
     * Submit answer for a question
     */
    public function submitAnswer(Request $request, QuizAttempt $attempt): JsonResponse
    {
        $this->authorize('view', $attempt);

        $validated = $request->validate([
            'item_id' => 'required|exists:item_bank,id',
            'answer' => 'required|string|size:1|in:A,B,C,D',
            'time_taken' => 'required|integer|min:0',
        ]);

        $item = \App\Models\ItemBank::findOrFail($validated['item_id']);
        $isCorrect = $item->correct_answer === $validated['answer'];

        // Record response
        Response::create([
            'quiz_attempt_id' => $attempt->id,
            'item_id' => $item->id,
            'user_id' => auth()->id(),
            'user_answer' => $validated['answer'],
            'is_correct' => $isCorrect,
            'time_taken_seconds' => $validated['time_taken'],
            'response_at' => now(),
        ]);

        return response()->json([
            'is_correct' => $isCorrect,
            'correct_answer' => $item->correct_answer,
            'explanation' => $item->explanation,
        ]);
    }

    /**
     * Complete quiz attempt
     */
    public function complete(QuizAttempt $attempt): JsonResponse
    {
        $this->authorize('view', $attempt);

        if ($attempt->isCompleted()) {
            return response()->json([
                'message' => 'Quiz already completed',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Calculate results
            $correctAnswers = $attempt->responses()->where('is_correct', true)->count();
            $scorePercentage = ($correctAnswers / $attempt->total_questions) * 100;

            // Update attempt
            $attempt->update([
                'correct_answers' => $correctAnswers,
                'score_percentage' => round($scorePercentage, 2),
                'completed_at' => now(),
                'time_spent_seconds' => now()->diffInSeconds($attempt->started_at),
            ]);

            // Update student ability using IRT
            $studentAbility = StudentAbility::firstOrCreate(
                [
                    'user_id' => $attempt->user_id,
                    'subtopic_id' => $attempt->subtopic_id
                ],
                [
                    'theta' => 0,
                    'attempts_count' => 0,
                    'last_updated' => now()
                ]
            );

            // Calculate new theta
            $responses = $attempt->responses()
                ->with('item')
                ->get()
                ->map(fn($r) => [
                    'difficulty' => $r->item->difficulty_b,
                    'correct' => $r->is_correct,
                ]);

            $newTheta = $this->irtService->estimateAbility(
                $studentAbility->theta,
                $responses->toArray()
            );

            $studentAbility->updateTheta($newTheta);

            DB::commit();

            return response()->json([
                'data' => [
                    'attempt_id' => $attempt->id,
                    'total_questions' => $attempt->total_questions,
                    'correct_answers' => $attempt->correct_answers,
                    'score_percentage' => $attempt->score_percentage,
                    'time_spent_minutes' => $attempt->time_spent_minutes,
                    'passed' => $attempt->isPassed(),
                    'new_ability_level' => $studentAbility->proficiency_level,
                    'theta' => round($studentAbility->theta, 2),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to complete quiz',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get quiz results
     */
    public function results(QuizAttempt $attempt): JsonResponse
    {
        $this->authorize('view', $attempt);

        $attempt->load([
            'responses.item.subtopic',
            'feedback',
            'subtopic',
        ]);

        return response()->json([
            'data' => [
                'attempt' => [
                    'id' => $attempt->id,
                    'attempt_number' => $attempt->attempt_number,
                    'score_percentage' => $attempt->score_percentage,
                    'correct_answers' => $attempt->correct_answers,
                    'total_questions' => $attempt->total_questions,
                    'time_spent_minutes' => $attempt->time_spent_minutes,
                    'passed' => $attempt->isPassed(),
                    'completed_at' => $attempt->completed_at,
                ],
                'subtopic' => [
                    'id' => $attempt->subtopic->id,
                    'name' => $attempt->subtopic->name,
                ],
                'responses' => $attempt->responses->map(fn($r) => [
                    'question' => $r->item->question,
                    'user_answer' => $r->user_answer,
                    'correct_answer' => $r->item->correct_answer,
                    'is_correct' => $r->is_correct,
                    'time_taken' => $r->time_taken_seconds,
                    'explanation' => $r->item->explanation,
                ]),
                'feedback' => $attempt->feedback ? [
                    'overall' => $attempt->feedback->feedback_text,
                    'strengths' => $attempt->feedback->strengths,
                    'weaknesses' => $attempt->feedback->weaknesses,
                    'recommendations' => $attempt->feedback->recommendations,
                    'next_steps' => $attempt->feedback->next_steps,
                    'motivational_message' => $attempt->feedback->motivational_message,
                ] : null,
            ],
        ]);
    }
}
<?php

namespace App\Http\Controllers\OpenAi;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Jobs\GenerateQuizJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuizGenerationController extends Controller
{
    /**
     * Generate quiz
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'tos_id' => 'required|exists:table_of_specifications,id',
        ]);

        $material = \App\Models\Material::findOrFail($validated['material_id']);
        $this->authorize('view', $material);

        // Dispatch quiz generation job
        GenerateQuizJob::dispatch($validated['material_id'], $validated['tos_id']);

        return response()->json([
            'message' => 'Quiz generation started',
            'material_id' => $validated['material_id'],
        ], 202);
    }

    /**
     * Get quiz details
     */
    public function show(Quiz $quiz): JsonResponse
    {
        $this->authorize('view', $quiz);

        return response()->json([
            'data' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'total_questions' => $quiz->total_questions,
                'time_per_question' => $quiz->time_per_question,
                'difficulty_level' => $quiz->difficulty_level,
                'status' => $quiz->status,
                'questions' => $quiz->questions->map(function ($q) {
                    return [
                        'id' => $q->id,
                        'question_text' => $q->question_text,
                        'cognitive_level' => $q->cognitive_level,
                        'subtopic' => $q->subtopic,
                        'difficulty' => $q->difficulty,
                        'options' => $q->options->map(fn($o) => [
                            'id' => $o->id,
                            'option_letter' => $o->option_letter,
                            'option_text' => $o->option_text,
                        ]),
                    ];
                }),
                'created_at' => $quiz->created_at,
            ],
        ]);
    }

    /**
     * Publish quiz
     */
    public function publish(Quiz $quiz): JsonResponse
    {
        $this->authorize('update', $quiz);

        $quiz->update(['status' => 'published']);

        return response()->json([
            'message' => 'Quiz published successfully',
            'data' => $quiz,
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Subtopic;
use App\Models\QuizRegeneration;
use App\Services\OpenAiService;
use Illuminate\Http\JsonResponse;

class QuizRegenerationController extends Controller
{
    protected OpenAiService $openAiService;

    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    public function regenerate(Subtopic $subtopic): JsonResponse
    {
        $userId = auth()->id();
        
        // Check if initial quizzes completed
        if (!$subtopic->hasCompletedAllInitialQuizzes($userId)) {
            return response()->json([
                'message' => 'Complete initial quizzes first'
            ], 400);
        }

        // Check regeneration count
        $regenCount = QuizRegeneration::where('subtopic_id', $subtopic->id)
            ->where('user_id', $userId)
            ->max('regeneration_count') ?? 0;

        if ($regenCount >= 3) {
            return response()->json([
                'message' => 'Maximum regenerations reached'
            ], 400);
        }

        // Regenerate questions
        $originalItems = $subtopic->items;
        $regeneratedItems = [];

        foreach ($originalItems as $item) {
            $reworded = $this->openAiService->rewordQuestion(
                $item->question,
                $item->options,
                $regenCount + 1
            );

            $regeneratedItem = $item->replicate();
            $regeneratedItem->question = $reworded['question'];
            $regeneratedItem->options = $reworded['options'];
            $regeneratedItem->save();

            QuizRegeneration::create([
                'original_item_id' => $item->id,
                'regenerated_item_id' => $regeneratedItem->id,
                'subtopic_id' => $subtopic->id,
                'user_id' => $userId,
                'regeneration_count' => $regenCount + 1,
                'regenerated_at' => now(),
            ]);

            $regeneratedItems[] = $regeneratedItem;
        }

        return response()->json([
            'message' => 'Quiz regenerated successfully',
            'regeneration_count' => $regenCount + 1,
            'items_count' => count($regeneratedItems)
        ], 201);
    }
}
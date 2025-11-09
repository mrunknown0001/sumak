<?php

namespace App\Jobs;

use App\Models\ItemBank;
use App\Models\QuizRegeneration;
use App\Services\OpenAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RegenerateQuestionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    protected int $questionId;
    protected int $userId;

    public function __construct(int $questionId, int $userId)
    {
        $this->questionId = $questionId;
        $this->userId = $userId;
    }

    public function handle(OpenAiService $openAiService): void
    {
        try {
            $originalItem = ItemBank::findOrFail($this->questionId);
            
            // Check regeneration limit
            $regenerationCount = QuizRegeneration::where('original_item_id', $originalItem->id)
                ->count();
                
            if ($regenerationCount >= 3) {
                Log::warning("Regeneration limit reached", ['item_id' => $originalItem->id]);
                return;
            }
            
            // Generate reworded question
            $rewordedData = $openAiService->rewordQuestion(
                $originalItem->question,
                $originalItem->options,
                $regenerationCount + 1
            );
            
            $rewordedQuestion = $rewordedData['reworded_question'];
            
            // Create new item in item bank
            $newItem = ItemBank::create([
                'tos_item_id' => $originalItem->tos_item_id,
                'topic_id' => $originalItem->topic_id,
                'learning_outcome_id' => $originalItem->learning_outcome_id,
                'question' => $rewordedQuestion['question_text'],
                'options' => $rewordedQuestion['options'],
                'correct_answer' => collect($rewordedQuestion['options'])
                    ->firstWhere('is_correct', true)['option_letter'],
                'explanation' => $rewordedQuestion['explanation'],
                'cognitive_level' => $originalItem->cognitive_level,
                'difficulty_b' => $originalItem->difficulty_b,
                'time_estimate_seconds' => $originalItem->time_estimate_seconds,
                'created_at' => now(),
            ]);
            
            // Record regeneration
            QuizRegeneration::create([
                'original_item_id' => $originalItem->id,
                'regenerated_item_id' => $newItem->id,
                'topic_id' => $originalItem->topic_id,
                'regeneration_count' => $regenerationCount + 1,
                'maintains_equivalence' => $rewordedQuestion['maintains_equivalence'] ?? true,
                'regenerated_at' => now(),
            ]);
            
            Log::info("Question regenerated successfully", [
                'original_item_id' => $originalItem->id,
                'new_item_id' => $newItem->id,
                'regeneration_count' => $regenerationCount + 1
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to regenerate question", [
                'question_id' => $this->questionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}
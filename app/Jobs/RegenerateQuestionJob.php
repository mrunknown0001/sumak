<?php

namespace App\Jobs;

use App\Models\QuizQuestion;
use App\Models\QuestionRegeneration;
use App\Models\ItemBank;
use App\Services\OpenAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RegenerateQuestionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $questionId,
        public int $userId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OpenAiService $openAiService): void
    {
        try {
            DB::beginTransaction();

            $originalQuestion = QuizQuestion::with('options')->findOrFail($this->questionId);

            // Check regeneration limit (max 3 times)
            $regenerationCount = QuestionRegeneration::where('original_question_id', $this->questionId)
                ->count();

            if ($regenerationCount >= 3) {
                throw new \Exception("Maximum regeneration limit (3) reached for question {$this->questionId}");
            }

            Log::info("Regenerating question {$this->questionId}, attempt #{(int)$regenerationCount + 1}");

            // Prepare original question data
            $originalOptions = $originalQuestion->options->map(function ($option) {
                return [
                    'option_text' => $option->option_text,
                    'is_correct' => $option->is_correct,
                ];
            })->toArray();

            // Generate reworded question
            $rewordedData = $openAiService->rewordQuestion(
                $originalQuestion->question_text,
                $originalOptions,
                $regenerationCount + 1
            );

            $rewordedQuestion = $rewordedData['reworded_question'];

            // Create new question
            $newQuestion = QuizQuestion::create([
                'quiz_id' => $originalQuestion->quiz_id,
                'question_text' => $rewordedQuestion['question_text'],
                'cognitive_level' => $originalQuestion->cognitive_level,
                'subtopic' => $originalQuestion->subtopic,
                'difficulty' => $originalQuestion->difficulty,
                'explanation' => $rewordedQuestion['explanation'],
                'order' => $originalQuestion->order,
                'is_regenerated' => true,
            ]);

            // Create new options
            foreach ($rewordedQuestion['options'] as $optionData) {
                $newQuestion->options()->create([
                    'option_letter' => $optionData['option_letter'],
                    'option_text' => $optionData['option_text'],
                    'is_correct' => $optionData['is_correct'],
                    'rationale' => $optionData['rationale'] ?? null,
                ]);
            }

            // Record regeneration
            QuestionRegeneration::create([
                'original_question_id' => $originalQuestion->id,
                'regenerated_question_id' => $newQuestion->id,
                'regeneration_count' => $regenerationCount + 1,
                'user_id' => $this->userId,
                'regeneration_date' => now(),
                'maintains_equivalence' => $rewordedQuestion['maintains_equivalence'] ?? true,
                'notes' => $rewordedQuestion['regeneration_notes'] ?? null,
            ]);

            // Update item bank
            ItemBank::create([
                'material_id' => $originalQuestion->quiz->material_id,
                'question_text' => $newQuestion->question_text,
                'cognitive_level' => $newQuestion->cognitive_level,
                'subtopic' => $newQuestion->subtopic,
                'difficulty' => $newQuestion->difficulty,
                'version_number' => $regenerationCount + 2,
                'reword_count' => $regenerationCount + 1,
                'original_question_id' => $originalQuestion->id,
            ]);

            // Deactivate original question (soft delete or flag)
            $originalQuestion->update(['is_active' => false]);

            DB::commit();

            Log::info("Question {$this->questionId} regenerated successfully", [
                'new_question_id' => $newQuestion->id,
                'regeneration_count' => $regenerationCount + 1
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Failed to regenerate question {$this->questionId}", [
                'error' => $e->getMessage(),
                'user_id' => $this->userId
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("RegenerateQuestionJob failed for question {$this->questionId}", [
            'error' => $exception->getMessage(),
            'user_id' => $this->userId
        ]);
    }
}
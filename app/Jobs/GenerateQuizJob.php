<?php

namespace App\Jobs;

use App\Models\Material;
use App\Models\TableOfSpecification;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuestionOption;
use App\Models\ItemBank;
use App\Services\OpenAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GenerateQuizJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $materialId,
        public int $tosId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OpenAiService $openAiService): void
    {
        try {
            DB::beginTransaction();

            $material = Material::findOrFail($this->materialId);
            $tos = TableOfSpecification::findOrFail($this->tosId);

            Log::info("Generating quiz for material {$this->materialId}");

            // Get ToS items
            $tosItems = $tos->tosItems()->get()->map(function ($item) {
                return [
                    'subtopic' => $item->subtopic,
                    'learning_outcome' => $item->learningOutcome?->outcome_statement ?? '',
                    'cognitive_level' => $item->cognitive_level,
                    'num_items' => $item->num_items,
                ];
            })->toArray();

            // Generate questions using AI
            $quizData = $openAiService->generateQuizQuestions(
                $tosItems,
                $material->content,
                20
            );

            // Create quiz
            $quiz = Quiz::create([
                'material_id' => $material->id,
                'course_id' => $material->course_id,
                'title' => "Quiz: " . $material->title,
                'total_questions' => 20,
                'time_per_question' => 60,
                'difficulty_level' => $this->calculateDifficulty($quizData['questions']),
                'status' => 'draft',
            ]);

            // Create questions and options
            foreach ($quizData['questions'] as $questionData) {
                $question = QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => $questionData['question_text'],
                    'cognitive_level' => $questionData['cognitive_level'],
                    'subtopic' => $questionData['subtopic'],
                    'difficulty' => $questionData['estimated_difficulty'] ?? 0.5,
                    'explanation' => $questionData['explanation'] ?? null,
                    'order' => $questionData['question_number'],
                ]);

                // Create options
                foreach ($questionData['options'] as $optionData) {
                    QuestionOption::create([
                        'quiz_question_id' => $question->id,
                        'option_letter' => $optionData['option_letter'],
                        'option_text' => $optionData['option_text'],
                        'is_correct' => $optionData['is_correct'],
                        'rationale' => $optionData['rationale'] ?? null,
                    ]);
                }

                // Add to item bank
                ItemBank::create([
                    'material_id' => $material->id,
                    'question_text' => $questionData['question_text'],
                    'cognitive_level' => $questionData['cognitive_level'],
                    'subtopic' => $questionData['subtopic'],
                    'difficulty' => $questionData['estimated_difficulty'] ?? 0.5,
                    'version_number' => 1,
                    'reword_count' => 0,
                    'original_question_id' => $question->id,
                ]);
            }

            $quiz->update(['status' => 'active']);

            DB::commit();

            Log::info("Quiz generated successfully for material {$this->materialId}", [
                'quiz_id' => $quiz->id,
                'questions_count' => 20
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Failed to generate quiz for material {$this->materialId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Calculate overall quiz difficulty
     */
    private function calculateDifficulty(array $questions): string
    {
        $avgDifficulty = collect($questions)
            ->avg('estimated_difficulty');

        if ($avgDifficulty < 0.33) {
            return 'easy';
        } elseif ($avgDifficulty < 0.67) {
            return 'medium';
        } else {
            return 'hard';
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("GenerateQuizJob failed permanently for material {$this->materialId}", [
            'error' => $exception->getMessage()
        ]);
    }
}
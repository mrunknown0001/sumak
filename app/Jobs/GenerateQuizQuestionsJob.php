<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\TosItem;
use App\Models\ItemBank;
use App\Services\OpenAiService;
use App\Services\IrtService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateQuizQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900; // 15 minutes

    protected int $documentId;

    public function __construct(int $documentId)
    {
        $this->documentId = $documentId;
    }

    public function handle(OpenAiService $openAiService, IrtService $irtService): void
    {
        try {
            $document = Document::with([
                'tableOfSpecification.tosItems.subtopic',
                'topics.subtopics'
            ])->findOrFail($this->documentId);
            
            $tos = $document->tableOfSpecification;
            
            if (!$tos) {
                Log::error("No ToS found for document", ['document_id' => $document->id]);
                return;
            }

            // Get document content
            $materialContent = $document->content_summary ?? "Content from {$document->title}";

            $subtopicQuestionCounts = [];
            $subtopicIds = $tos->tosItems
                ->pluck('subtopic_id')
                ->filter()
                ->unique()
                ->values();

            if ($subtopicIds->isNotEmpty()) {
                $subtopicQuestionCounts = ItemBank::query()
                    ->whereIn('subtopic_id', $subtopicIds)
                    ->selectRaw('subtopic_id, COUNT(*) as aggregate_count')
                    ->groupBy('subtopic_id')
                    ->pluck('aggregate_count', 'subtopic_id')
                    ->map(fn ($count) => (int) $count)
                    ->toArray();
            }

            Log::debug('Preparing quiz generation from ToS', [
                'document_id' => $document->id,
                'tos_id' => $tos->id,
                'tos_items_count' => $tos->tosItems->count(),
            ]);

            if ($tos->tosItems->isEmpty()) {
                Log::warning('ToS contains no items; skipping quiz generation', [
                    'document_id' => $document->id,
                    'tos_id' => $tos->id,
                ]);
                return;
            }
            
            // Process each ToS item
            foreach ($tos->tosItems as $tosItem) {
                $this->generateQuestionsForTosItem(
                    $tosItem,
                    $materialContent,
                    $openAiService,
                    $irtService,
                    $subtopicQuestionCounts
                );
            }
            
            Log::info("Quiz questions generated successfully", ['document_id' => $document->id]);
            
        } catch (\Exception $e) {
            Log::error("Failed to generate quiz questions", [
                'document_id' => $this->documentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    protected function generateQuestionsForTosItem(
        TosItem $tosItem,
        string $materialContent,
        OpenAiService $openAiService,
        IrtService $irtService,
        array &$subtopicQuestionCounts
    ): void {
        $subtopicId = $tosItem->subtopic_id;
        $subtopicTotalExisting = $subtopicQuestionCounts[$subtopicId] ?? 0;

        // Check if questions already exist
        $existingCount = $tosItem->items()->count();
        $targetQuestionCount = max(1, (int) $tosItem->num_items);

        Log::debug('Evaluating ToS item for question generation', [
            'tos_item_id' => $tosItem->id,
            'subtopic_id' => $subtopicId,
            'existing_questions' => $existingCount,
            'subtopic_total_questions' => $subtopicTotalExisting,
            'required_questions' => $targetQuestionCount,
        ]);
        
        $needsSubtopicBaseline = $subtopicTotalExisting === 0;

        if (!$needsSubtopicBaseline && $existingCount >= $targetQuestionCount) {
            Log::info("Questions already exist for ToS item", ['tos_item_id' => $tosItem->id]);
            return;
        }
        
        $questionsNeeded = max(
            $needsSubtopicBaseline ? 1 : 0,
            max(0, $targetQuestionCount - $existingCount)
        );

        // if ($questionsNeeded <= 0) {
        //     return;
        // }
        
        // Prepare ToS item data for AI
        $tosItemData = [
            'subtopic' => $tosItem->subtopic->name,
            'cognitive_level' => $tosItem->cognitive_level,
            'bloom_category' => $tosItem->bloom_category,
            'num_items' => $questionsNeeded,
            'sample_indicators' => $tosItem->sample_indicators,
        ];
        // Generate questions using AI
        $questionsData = $openAiService->generateQuizQuestions(
            [$tosItemData],
            $materialContent,
            rand(1,3) // TODO: Statically Assigned Values
        );

        $generatedQuestions = collect($questionsData['questions'] ?? [])
            ->map(function (array $questionData) {
                $questionText = trim($questionData['question_text'] ?? '');

                $options = collect($questionData['options'] ?? [])
                    ->map(function (array $option) {
                        return [
                            'option_letter' => $option['option_letter'] ?? null,
                            'option_text' => $option['option_text'] ?? null,
                            'is_correct' => (bool) ($option['is_correct'] ?? false),
                            'rationale' => $option['rationale'] ?? null,
                        ];
                    })
                    ->filter(function (array $option) {
                        return !empty($option['option_letter']) && !empty($option['option_text']);
                    })
                    ->values();

                if ($questionText === '' || $options->isEmpty()) {
                    return null;
                }

                $correctOption = $options->firstWhere('is_correct', true);

                if (!$correctOption) {
                    $options = $options->map(function (array $option, int $index) use (&$correctOption) {
                        if ($index === 0) {
                            $option['is_correct'] = true;
                            $correctOption = $option;
                        }

                        return $option;
                    });
                }

                if (!$correctOption) {
                    return null;
                }

                return [
                    'question_text' => $questionText,
                    'options' => $options->map(function (array $option) {
                        return [
                            'option_letter' => $option['option_letter'],
                            'option_text' => $option['option_text'],
                            'is_correct' => $option['is_correct'],
                            'rationale' => $option['rationale'],
                        ];
                    })->toArray(),
                    'correct_answer_letter' => $correctOption['option_letter'],
                    'explanation' => $questionData['explanation'] ?? null,
                    'cognitive_level' => $questionData['cognitive_level'] ?? null,
                    'estimated_difficulty' => is_numeric($questionData['estimated_difficulty'] ?? null)
                        ? (float) $questionData['estimated_difficulty']
                        : 0.5,
                    'time_estimate_seconds' => (int) ($questionData['time_estimate_seconds'] ?? 60),
                ];
            })
            ->filter()
            ->values();

        if ($generatedQuestions->isEmpty()) {
            $fallbackQuestions = collect($this->generateFallbackQuestions($tosItem, $questionsNeeded));

            if ($fallbackQuestions->isEmpty()) {
                Log::warning('Unable to generate fallback questions for ToS item', [
                    'tos_item_id' => $tosItem->id,
                    'questions_requested' => $questionsNeeded,
                    'openai_response_keys' => is_array($questionsData) ? array_keys($questionsData) : null,
                ]);
                return;
            }

            Log::notice('AI returned no usable questions; using fallback generator', [
                'tos_item_id' => $tosItem->id,
                'fallback_questions' => $fallbackQuestions->count(),
            ]);

            $generatedQuestions = $fallbackQuestions;
        }

        if ($generatedQuestions->count() < $questionsNeeded) {
            $fallbackNeeded = $questionsNeeded - $generatedQuestions->count();
            $fallbackQuestions = collect($this->generateFallbackQuestions($tosItem, $fallbackNeeded));

            if ($fallbackQuestions->isNotEmpty()) {
                $generatedQuestions = $generatedQuestions->concat($fallbackQuestions)->values();

                Log::notice('Supplemented AI output with fallback questions', [
                    'tos_item_id' => $tosItem->id,
                    'fallback_questions' => $fallbackQuestions->count(),
                    'requested_total' => $questionsNeeded,
                    'final_total' => $generatedQuestions->count(),
                ]);
            }
        }
        
        $persistedCount = 0;
        
        // Save questions to item bank
        foreach ($generatedQuestions as $questionData) {
            // Estimate initial difficulty (will be refined based on responses)
            $estimatedDifficulty = $questionData['estimated_difficulty'];
            
            // Convert difficulty from 0-1 scale to IRT scale (-3 to 3)
            $difficultyB = ($estimatedDifficulty - 0.5) * 4;
            
            ItemBank::create([
                'tos_item_id' => $tosItem->id,
                'subtopic_id' => $tosItem->subtopic_id,
                'learning_outcome_id' => $tosItem->learning_outcome_id,
                'question' => $questionData['question_text'],
                'options' => $questionData['options'],
                'correct_answer' => $questionData['correct_answer_letter'],
                'explanation' => $questionData['explanation'],
                'cognitive_level' => $questionData['cognitive_level'],
                'difficulty_b' => $difficultyB,
                'time_estimate_seconds' => $questionData['time_estimate_seconds'],
                'created_at' => now(),
            ]);

            $persistedCount++;
        }
        
        $subtopicQuestionCounts[$subtopicId] = ($subtopicQuestionCounts[$subtopicId] ?? 0) + $persistedCount;

        Log::info("Generated questions for ToS item", [
            'tos_item_id' => $tosItem->id,
            'subtopic_id' => $subtopicId,
            'questions_generated' => $persistedCount,
            'subtopic_total_questions' => $subtopicQuestionCounts[$subtopicId],
        ]);
    }

    /**
     * Fallback generator to ensure at least one usable question per ToS item
     */
    protected function generateFallbackQuestions(TosItem $tosItem, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $subtopicName = $tosItem->subtopic->name ?? 'this topic';
        $cognitiveLevel = $tosItem->cognitive_level ?? 'remember';

        $indicators = collect($this->normalizeSampleIndicators($tosItem->sample_indicators));
        if ($indicators->isEmpty()) {
            $indicators = collect(["Key concept of {$subtopicName}"]);
        }

        $distractorPool = [
            "A statement that only loosely relates to {$subtopicName}.",
            "An idea that misinterprets {$subtopicName}.",
            "A detail belonging to a different subtopic.",
            "An overgeneralization that does not apply to {$subtopicName}.",
            "A misconception often associated with {$subtopicName}.",
        ];

        return collect(range(1, $count))
            ->map(function (int $index) use ($indicators, $distractorPool, $subtopicName, $cognitiveLevel) {
                $indicatorValue = $indicators->get(($index - 1) % $indicators->count());
                if (is_array($indicatorValue)) {
                    $indicatorValue = implode(' ', array_filter($indicatorValue, fn ($value) => is_string($value)));
                }

                $indicatorText = trim((string) ($indicatorValue ?: "a key concept in {$subtopicName}"));

                $questionText = "Which statement best aligns with {$indicatorText} in {$subtopicName}?";
                $correctExplanation = "This option directly reflects {$indicatorText} within {$subtopicName}.";

                $distractors = collect($distractorPool)
                    ->shuffle()
                    ->take(3)
                    ->values();

                $options = collect([
                    [
                        'option_letter' => 'A',
                        'option_text' => $indicatorText,
                        'is_correct' => true,
                        'rationale' => $correctExplanation,
                    ],
                ]);

                $letters = ['B', 'C', 'D'];
                foreach ($distractors as $i => $distractor) {
                    $options->push([
                        'option_letter' => $letters[$i] ?? chr(ord('A') + $i + 1),
                        'option_text' => $distractor,
                        'is_correct' => false,
                        'rationale' => "This does not accurately describe {$subtopicName}.",
                    ]);
                }

                return [
                    'question_text' => $questionText,
                    'options' => $options->map(fn ($option) => [
                        'option_letter' => $option['option_letter'],
                        'option_text' => $option['option_text'],
                        'is_correct' => $option['is_correct'],
                        'rationale' => $option['rationale'],
                    ])->toArray(),
                    'correct_answer_letter' => 'A',
                    'explanation' => $correctExplanation,
                    'cognitive_level' => $cognitiveLevel,
                    'estimated_difficulty' => 0.25,
                    'time_estimate_seconds' => 60,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Normalize sample indicators into a flat array of strings
     */
    protected function normalizeSampleIndicators(mixed $sampleIndicators): array
    {
        if (is_array($sampleIndicators)) {
            return collect($sampleIndicators)
                ->map(function ($value) {
                    if (is_string($value)) {
                        return trim($value);
                    }

                    if (is_array($value)) {
                        return trim(implode(' ', array_filter($value, fn ($segment) => is_string($segment))));
                    }

                    return '';
                })
                ->filter()
                ->values()
                ->toArray();
        }

        if (is_string($sampleIndicators) && $sampleIndicators !== '') {
            $decoded = json_decode($sampleIndicators, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->normalizeSampleIndicators($decoded);
            }

            return collect(preg_split('/[\r\n;,]+/', $sampleIndicators))
                ->map(fn ($segment) => trim($segment))
                ->filter()
                ->values()
                ->toArray();
        }

        return [];
    }
}
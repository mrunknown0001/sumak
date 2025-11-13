<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\ItemBank;
use App\Models\Topic;
use App\Models\TosItem;
use App\Services\OpenAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
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

    public function handle(OpenAiService $openAiService): void
    {
        try {
            $document = Document::with([
                'topics',
                'tableOfSpecification.tosItems',
            ])->findOrFail($this->documentId);

            $topics = $document->topics;

            if ($topics->isEmpty()) {
                Log::warning('Skipping quiz generation: document has no topics', [
                    'document_id' => $document->id,
                ]);
                return;
            }

            $tos = $document->tableOfSpecification;

            if (!$tos) {
                Log::error('No ToS found for document', ['document_id' => $document->id]);
                return;
            }

            $tosItemsByTopic = $tos->tosItems->keyBy('topic_id');

            if ($tosItemsByTopic->isEmpty()) {
                Log::warning('ToS does not contain topic-aligned items', [
                    'document_id' => $document->id,
                    'tos_id' => $tos->id,
                ]);
                return;
            }

            $materialContent = $document->content_summary ?? "Content from {$document->title}";
            $topicPayload = $this->buildTopicPayload($topics);

            $response = $openAiService->generateQuizQuestions($topicPayload, $materialContent);
            $responseTopics = collect($response['topics'] ?? []);

            $this->resetExistingItems($tosItemsByTopic);

            foreach ($topics as $topic) {
                $tosItem = $tosItemsByTopic->get($topic->id);

                if (!$tosItem instanceof TosItem) {
                    Log::warning('No ToS item aligned with topic; skipping quiz generation for topic', [
                        'document_id' => $document->id,
                        'topic_id' => $topic->id,
                        'topic_name' => $topic->name,
                    ]);
                    continue;
                }

                $targetCount = (int)($topic->metadata['recommended_question_count'] ?? 4);
                $topicResponse = $this->matchTopicResponse($responseTopics, $topic->name);

                $persistedCount = $this->persistQuestionsForTopic(
                    $topic,
                    $tosItem,
                    $topicResponse['questions'] ?? [],
                    $targetCount
                );

                if ($persistedCount < $targetCount) {
                    $fallbackCount = $targetCount - $persistedCount;
                    $fallbackQuestions = $this->generateFallbackQuestions($topic, $fallbackCount);

                    if ($fallbackQuestions->isEmpty()) {
                        Log::warning('Fallback generator produced no questions', [
                            'document_id' => $document->id,
                            'topic_id' => $topic->id,
                            'topic_name' => $topic->name,
                            'fallback_requested' => $fallbackCount,
                        ]);
                        continue;
                    }

                    $this->persistSanitizedQuestions($topic, $tosItem, $fallbackQuestions);
                    $persistedCount += $fallbackQuestions->count();
                }

                Log::info('Generated topic-level quiz questions', [
                    'document_id' => $document->id,
                    'topic_id' => $topic->id,
                    'topic_name' => $topic->name,
                    'questions_generated' => $persistedCount,
                    'target_count' => $targetCount,
                ]);
            }

            Log::info('Quiz questions generated successfully', ['document_id' => $document->id]);
        } catch (\Exception $e) {
            Log::error('Failed to generate quiz questions', [
                'document_id' => $this->documentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Prepare AI payload for the provided topics.
     */
    protected function buildTopicPayload(Collection $topics): array
    {
        return $topics
            ->map(function (Topic $topic) {
                $metadata = $topic->metadata ?? [];

                return [
                    'topic' => $topic->name,
                    'description' => $topic->description,
                    'recommended_question_count' => (int)($metadata['recommended_question_count'] ?? 4),
                    'cognitive_emphasis' => $metadata['cognitive_emphasis'] ?? 'understand',
                    'key_concepts' => $metadata['key_concepts'] ?? [],
                ];
            })
            ->toArray();
    }

    /**
     * Remove previously generated items for the supplied ToS items.
     */
    protected function resetExistingItems(Collection $tosItems): void
    {
        $tosItems->each(function (TosItem $tosItem) {
            $deleted = $tosItem->items()->delete();

            if ($deleted > 0) {
                Log::debug('Deleted existing items for ToS item prior to regeneration', [
                    'tos_item_id' => $tosItem->id,
                    'deleted_count' => $deleted,
                ]);
            }
        });
    }

    /**
     * Find the AI response block that matches a given topic name.
     */
    protected function matchTopicResponse(Collection $responseTopics, string $topicName): ?array
    {
        $normalizedName = $this->normalizeLabel($topicName);

        return $responseTopics
            ->first(function (array $topicData) use ($normalizedName) {
                return $this->normalizeLabel((string)($topicData['topic'] ?? '')) === $normalizedName;
            });
    }

    /**
     * Persist AI-generated questions for a specific topic.
     */
    protected function persistQuestionsForTopic(
        Topic $topic,
        TosItem $tosItem,
        array $questionPayload,
        int $targetCount
    ): int {
        $sanitizedQuestions = collect($questionPayload)
            ->map(fn (array $rawQuestion) => $this->sanitizeQuestionData($topic, $rawQuestion))
            ->filter()
            ->take($targetCount);

        if ($sanitizedQuestions->isEmpty()) {
            return 0;
        }

        $this->persistSanitizedQuestions($topic, $tosItem, $sanitizedQuestions);

        return $sanitizedQuestions->count();
    }

    /**
     * Persist sanitized quiz questions into the item bank.
     */
    protected function persistSanitizedQuestions(Topic $topic, TosItem $tosItem, Collection $questions): void
    {
        foreach ($questions as $question) {
            $estimatedDifficulty = (float)($question['estimated_difficulty'] ?? 0.5);
            $difficultyB = $this->convertToIrtDifficulty($estimatedDifficulty);

            ItemBank::create([
                'tos_item_id' => $tosItem->id,
                'topic_id' => $topic->id,
                'learning_outcome_id' => null,
                'question' => $question['question_text'],
                'options' => $question['options'],
                'correct_answer' => $question['correct_answer_letter'],
                'explanation' => $question['explanation'],
                'cognitive_level' => $question['cognitive_level'],
                'difficulty_b' => $difficultyB,
                'time_estimate_seconds' => (int)($question['time_estimate_seconds'] ?? 60),
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Normalize question data coming either from the AI response or fallback generator.
     */
    protected function sanitizeQuestionData(Topic $topic, array $questionData): ?array
    {
        $questionText = trim((string)($questionData['question_text'] ?? ''));

        if ($questionText === '') {
            return null;
        }

        $rawOptions = $questionData['options'] ?? [];
        $options = $this->normalizeOptions($rawOptions);

        if ($options->count() !== 4) {
            return null;
        }

        $correctLetter = $this->resolveCorrectAnswer($options, $questionData['correct_answer'] ?? null);

        $cognitiveLevel = $this->normalizeCognitiveLevel($questionData['cognitive_level'] ?? null);
        $explanation = trim((string)($questionData['explanation'] ?? '')) ?: null;
        $estimatedDifficulty = $this->normalizeEstimatedDifficulty($questionData['estimated_difficulty'] ?? null);
        $timeEstimate = $this->normalizeTimeEstimate($questionData['time_estimate_seconds'] ?? null);

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
            'correct_answer_letter' => $correctLetter,
            'explanation' => $explanation,
            'cognitive_level' => $cognitiveLevel,
            'estimated_difficulty' => $estimatedDifficulty,
            'time_estimate_seconds' => $timeEstimate,
        ];
    }

    /**
     * Normalize and validate options for a question.
     */
    protected function normalizeOptions(array $options): Collection
    {
        $letters = ['A', 'B', 'C', 'D'];

        $normalized = collect($options)
            ->take(4)
            ->map(function (array $option, int $index) use ($letters) {
                $letter = strtoupper((string)($option['option_letter'] ?? $letters[$index] ?? ''));
                if (!in_array($letter, $letters, true)) {
                    $letter = $letters[$index] ?? 'D';
                }

                $text = trim((string)($option['option_text'] ?? ''));

                if ($text === '') {
                    return null;
                }

                $isCorrect = (bool)($option['is_correct'] ?? false);
                $rationale = trim((string)($option['rationale'] ?? ''));

                return [
                    'option_letter' => $letter,
                    'option_text' => $text,
                    'is_correct' => $isCorrect,
                    'rationale' => $rationale !== '' ? $rationale : null,
                ];
            })
            ->filter()
            ->values();

        if ($normalized->count() < 4) {
            return collect();
        }

        return $normalized;
    }

    /**
     * Resolve the correct answer letter ensuring exactly one correct option.
     */
    protected function resolveCorrectAnswer(Collection $options, ?string $explicit): string
    {
        $explicit = strtoupper(trim((string)$explicit));

        if ($explicit !== '' && $options->firstWhere('option_letter', $explicit)) {
            $options->transform(function (array $option) use ($explicit) {
                $option['is_correct'] = $option['option_letter'] === $explicit;
                return $option;
            });

            return $explicit;
        }

        $firstMarked = $options->firstWhere('is_correct', true);

        if ($firstMarked) {
            $correctLetter = $firstMarked['option_letter'];

            $options->transform(function (array $option) use ($correctLetter) {
                $option['is_correct'] = $option['option_letter'] === $correctLetter;
                return $option;
            });

            return $correctLetter;
        }

        $options->transform(function (array $option, int $index) {
            $option['is_correct'] = $index === 0;
            return $option;
        });

        return $options->first()['option_letter'];
    }

    /**
     * Generate deterministic fallback questions using topic metadata.
     */
    protected function generateFallbackQuestions(Topic $topic, int $count): Collection
    {
        if ($count <= 0) {
            return collect();
        }

        $metadata = $topic->metadata ?? [];
        $keyConcepts = collect($metadata['key_concepts'] ?? [])
            ->filter(fn ($concept) => is_string($concept) && trim($concept) !== '')
            ->map(fn ($concept) => trim($concept))
            ->values();

        if ($keyConcepts->isEmpty()) {
            $keyConcepts = collect([
                "{$topic->name} fundamentals",
                "{$topic->name} terminology",
                "{$topic->name} examples",
            ]);
        }

        return collect(range(1, $count))->map(function (int $index) use ($topic, $keyConcepts) {
            $concept = $keyConcepts->get(($index - 1) % $keyConcepts->count(), $topic->name);
            $concept = trim($concept);

            $questionText = "Which statement best aligns with \"{$concept}\" in the context of {$topic->name}?";
            $correctOption = [
                'option_letter' => 'A',
                'option_text' => "It accurately reflects {$concept} as presented in {$topic->name}.",
                'is_correct' => true,
                'rationale' => "This option directly captures {$concept} as explained within {$topic->name}.",
            ];

            $distractors = collect([
                "It describes content unrelated to {$topic->name}.",
                "It contradicts the core ideas discussed in {$topic->name}.",
                "It references an example that is not part of {$topic->name}.",
            ])->map(fn (string $text, int $dIndex) => [
                'option_letter' => chr(ord('B') + $dIndex),
                'option_text' => $text,
                'is_correct' => false,
                'rationale' => "This does not correctly represent {$concept} within {$topic->name}.",
            ])->take(3);

            $options = collect([$correctOption])->concat($distractors)->take(4)->map(function (array $option, int $idx) {
                $option['option_letter'] = ['A', 'B', 'C', 'D'][$idx] ?? 'A';
                return $option;
            });

            return [
                'question_text' => $questionText,
                'options' => $options->toArray(),
                'correct_answer_letter' => 'A',
                'explanation' => "Option A aligns with {$concept} as highlighted in {$topic->name}.",
                'cognitive_level' => $this->normalizeCognitiveLevel($topic->metadata['cognitive_emphasis'] ?? 'understand'),
                'estimated_difficulty' => 0.35,
                'time_estimate_seconds' => 60,
            ];
        });
    }

    /**
     * Convert AI-provided difficulty (0-1 range) to IRT difficulty scale (-3 to 3).
     */
    protected function convertToIrtDifficulty(float $difficulty): float
    {
        $difficulty = max(0.1, min(0.9, $difficulty));

        return ($difficulty - 0.5) * 6; // Map 0-1 to roughly -3 to +3
    }

    protected function normalizeCognitiveLevel(mixed $value): string
    {
        $level = strtolower(trim((string)$value));

        return match ($level) {
            'remember', 'recall' => 'remember',
            'apply', 'application' => 'apply',
            default => 'understand',
        };
    }

    protected function normalizeEstimatedDifficulty(mixed $value): float
    {
        if (is_numeric($value)) {
            $float = (float)$value;

            return $float <= 0 ? 0.35 : ($float > 1 ? 0.75 : $float);
        }

        return 0.5;
    }

    protected function normalizeTimeEstimate(mixed $value): int
    {
        $int = is_numeric($value) ? (int)$value : 60;

        return max(30, min(180, $int));
    }

    protected function normalizeLabel(?string $value): string
    {
        return strtolower(trim((string)$value));
    }
}
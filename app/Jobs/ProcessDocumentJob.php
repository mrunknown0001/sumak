<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\LearningOutcome;
use App\Models\TableOfSpecification;
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
use Illuminate\Support\Str;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes

    protected int $documentId;
    protected array $options;

    public function __construct(int $documentId, array $options = [])
    {
        $this->documentId = $documentId;
        $this->options = $options;
    }

    public function handle(OpenAiService $openAiService): void
    {
        try {
            $document = Document::findOrFail($this->documentId);

            $document->update([
                'processing_status' => Document::PROCESSING_IN_PROGRESS,
                'processed_at' => null,
                'processing_error' => null,
            ]);

            $content = $this->extractContent($document);
            $analysis = $openAiService->analyzeContent($content, $this->options['obtl_context'] ?? null);

            Log::debug('Document analysis result', [
                'document_id' => $document->id,
                'topic_count' => count($analysis['topics'] ?? []),
            ]);

            $this->updateDocumentSummary($document, $analysis['content_summary'] ?? null);

            $topics = $this->syncTopicsFromAnalysis($document, $analysis['topics'] ?? []);

            $this->syncTableOfSpecificationFromTopics($document, $topics);

            GenerateQuizQuestionsJob::dispatch($document->id);

            $document->update([
                'processing_status' => Document::PROCESSING_COMPLETED,
                'processed_at' => now(),
                'processing_error' => null,
            ]);

            Log::info('Document processed successfully', ['document_id' => $document->id]);
        } catch (\Exception $e) {
            $document = Document::find($this->documentId);

            if ($document) {
                $document->update([
                    'processing_status' => Document::PROCESSING_FAILED,
                    'processing_error' => $e->getMessage(),
                ]);
            }

            Log::error('Failed to process document', [
                'document_id' => $this->documentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function extractContent(Document $document): string
    {
        if ($document->file_type === 'pdf') {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($document->file_path);
            $content = $pdf->getText();
        } else {
            $content = file_get_contents($document->file_path);
        }

        return $content;
    }

    protected function updateDocumentSummary(Document $document, ?string $summary): void
    {
        $summary = is_string($summary) ? trim($summary) : null;

        if ($summary && $summary !== $document->content_summary) {
            $document->update(['content_summary' => $summary]);
        }
    }

    protected function syncTopicsFromAnalysis(Document $document, array $topicsData): Collection
    {
        $document->topics()->delete();

        return collect($topicsData)
            ->filter(fn ($topic) => is_array($topic))
            ->values()
            ->map(function (array $topicData, int $index) use ($document) {
                $name = trim((string)($topicData['topic'] ?? ''));

                if ($name === '') {
                    $name = 'Topic ' . ($index + 1);
                }

                $description = trim((string)($topicData['description'] ?? '')) ?: null;

                $recommended = $this->normalizeRecommendedQuestionCount($topicData['recommended_question_count'] ?? null);
                $cognitiveEmphasis = $this->normalizeCognitiveLevel($topicData['cognitive_emphasis'] ?? null);
                $keyConcepts = $this->normalizeKeyConcepts($topicData['key_concepts'] ?? []);
                $supportingNotes = $this->normalizeSupportingNotes($topicData['supporting_notes'] ?? null);

                return Topic::create([
                    'document_id' => $document->id,
                    'name' => $name,
                    'description' => $description,
                    'metadata' => [
                        'recommended_question_count' => $recommended,
                        'cognitive_emphasis' => $cognitiveEmphasis,
                        'key_concepts' => $keyConcepts,
                        'supporting_notes' => $supportingNotes,
                    ],
                    'order_index' => $index,
                ]);
            });
    }

    protected function syncTableOfSpecificationFromTopics(Document $document, Collection $topics): ?TableOfSpecification
    {
        $existingTos = $document->tableOfSpecification;

        if ($existingTos) {
            $existingTos->tosItems()->delete();
            $existingTos->delete();
        }

        if ($topics->isEmpty()) {
            Log::warning('Skipping ToS generation due to empty topic set', [
                'document_id' => $document->id,
            ]);

            return null;
        }

        $document->loadMissing('course.obtlDocument.learningOutcomes');

        $learningOutcomes = optional(optional($document->course)->obtlDocument)->learningOutcomes ?? collect();

        if ($learningOutcomes->isEmpty()) {
            Log::warning('Aborting ToS generation due to missing learning outcomes', [
                'document_id' => $document->id,
                'course_id' => $document->course_id,
            ]);

            throw new RuntimeException("Learning outcomes are required before generating a table of specification for document {$document->id}.");
        }

        $totalItems = (int) $topics->sum(function (Topic $topic) {
            return (int) ($topic->metadata['recommended_question_count'] ?? 0);
        });

        if ($totalItems <= 0) {
            $totalItems = max(1, $topics->count() * 4);
        }

        // Collect entries first: resolve learning outcome per topic and determine cognitive level (from LO when available)
        $tosEntries = [];
        $counts = [
            'remember' => 0,
            'understand' => 0,
            'apply' => 0,
            'analyze' => 0,
            'evaluate' => 0,
            'create' => 0,
        ];

        foreach ($topics as $topic) {
            $metadata = $topic->metadata ?? [];
            $recommended = (int) ($metadata['recommended_question_count'] ?? 4);
            $keyConcepts = $metadata['key_concepts'] ?? [];

            $learningOutcome = $this->resolveLearningOutcomeForTopic($topic, $learningOutcomes);

            if (! $learningOutcome) {
                Log::warning('Failed to resolve learning outcome for topic; using first available outcome', [
                    'document_id' => $document->id,
                    'topic_id' => $topic->id,
                ]);

                $learningOutcome = $learningOutcomes->first();
            }

            // Prefer learning outcome's bloom_level if available; otherwise use topic metadata or default to 'understand'
            $rawCognitiveSource = $learningOutcome->bloom_level ?? ($metadata['cognitive_emphasis'] ?? null);
            $cognitive = $this->normalizeCognitiveLevel($rawCognitiveSource ?? 'understand');

            // Accumulate counts for distribution
            if (! array_key_exists($cognitive, $counts)) {
                $cognitive = 'understand';
            }
            $counts[$cognitive] += $recommended;

            $tosEntries[] = [
                'topic' => $topic,
                'learningOutcome' => $learningOutcome,
                'cognitive' => $cognitive,
                'keyConcepts' => $keyConcepts,
                'recommended' => $recommended,
            ];
        }

        // Compute percentages distribution
        $distribution = [];
        foreach ($counts as $level => $count) {
            $distribution[$level] = $totalItems > 0 ? round(($count / $totalItems) * 100, 2) : 0;
        }

        $tos = TableOfSpecification::create([
            'document_id' => $document->id,
            'total_items' => $totalItems,
            'lots_percentage' => 100,
            'cognitive_level_distribution' => $distribution,
            'assessment_focus' => 'LOTS and HOTS Focus',
            'generated_at' => now(),
        ]);

        // Persist TosItems using resolved cognitive levels and bloom categories
        foreach ($tosEntries as $entry) {
            $topic = $entry['topic'];
            $learningOutcome = $entry['learningOutcome'];
            $cognitive = $entry['cognitive'];
            $recommended = $entry['recommended'];
            $keyConcepts = $entry['keyConcepts'];

            TosItem::create([
                'tos_id' => $tos->id,
                'topic_id' => $topic->id,
                'learning_outcome_id' => $learningOutcome?->id,
                'cognitive_level' => $cognitive,
                'bloom_category' => $this->mapCognitiveToBloom($cognitive),
                'num_items' => $recommended,
                'weight_percentage' => $totalItems > 0 ? round(($recommended / $totalItems) * 100, 2) : 0,
                'sample_indicators' => $keyConcepts,
            ]);
        }

        Log::info('Created topic-level Table of Specification', [
            'document_id' => $document->id,
            'tos_id' => $tos->id,
            'total_items' => $totalItems,
            'topic_count' => $topics->count(),
            'distribution' => $distribution,
        ]);

        return $tos;
    }

    protected function normalizeRecommendedQuestionCount(mixed $value): int
    {
        $intValue = is_numeric($value) ? (int)$value : 0;
        $intValue = $intValue < 4 ? 4 : $intValue;
        $intValue = $intValue > 6 ? 6 : $intValue;

        return $intValue;
    }

    protected function normalizeCognitiveLevel(mixed $value): string
    {
        $level = is_string($value) ? strtolower(trim($value)) : '';

        // Normalize known variations and synonyms to the canonical six levels
        return match ($level) {
            'remember', 'recall', 'knowledge' => 'remember',
            'understand', 'comprehension', 'comprehend' => 'understand',
            'apply', 'application' => 'apply',
            'analyze', 'analysis', 'analyse' => 'analyze',
            'evaluate', 'evaluation', 'assess', 'assessment' => 'evaluate',
            'create', 'synthesis', 'synthesise', 'design', 'develop' => 'create',
            default => 'understand',
        };
    }

    protected function normalizeKeyConcepts(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            } else {
                $value = preg_split('/[\r\n,;]/', $value);
            }
        }

        if (!is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn ($item) => is_string($item))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->take(10)
            ->toArray();
    }

    protected function normalizeSupportingNotes(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    protected function resolveLearningOutcomeForTopic(Topic $topic, Collection $learningOutcomes): ?LearningOutcome
    {
        if ($learningOutcomes->isEmpty()) {
            return null;
        }

        $keywords = $this->extractTopicKeywords($topic);

        if ($keywords->isEmpty()) {
            return $learningOutcomes->first();
        }

        $bestMatch = $learningOutcomes
            ->map(function (LearningOutcome $outcome) use ($keywords) {
                $outcomeText = Str::lower(trim(($outcome->description ?? '') . ' ' . ($outcome->outcome_code ?? '')));

                $score = 0;

                foreach ($keywords as $keyword) {
                    if ($keyword === '') {
                        continue;
                    }

                    if (Str::contains($outcomeText, $keyword)) {
                        $score += strlen($keyword);
                    }
                }

                return [
                    'outcome' => $outcome,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->first();

        if (! $bestMatch || $bestMatch['score'] <= 0) {
            return $learningOutcomes->first();
        }

        return $bestMatch['outcome'];
    }

    protected function extractTopicKeywords(Topic $topic): Collection
    {
        $segments = collect([$topic->name, $topic->description]);

        $metadata = $topic->metadata ?? [];

        if (isset($metadata['key_concepts']) && is_array($metadata['key_concepts'])) {
            $segments = $segments->merge($metadata['key_concepts']);
        }

        if (isset($metadata['supporting_notes']) && is_string($metadata['supporting_notes'])) {
            $segments->push($metadata['supporting_notes']);
        }

        return $segments
            ->filter(fn ($value) => is_string($value))
            ->flatMap(function (string $value) {
                $tokens = preg_split('/[\s,;:.()\-\_]+/', Str::lower($value));

                return collect($tokens ?: []);
            })
            ->map(fn ($token) => trim($token))
            ->filter(fn ($token) => strlen($token) >= 3)
            ->unique()
            ->values();
    }

    // NOTE: buildCognitiveDistribution is no longer used for the primary computation,
    // but we keep it available (and corrected) if other parts of the code rely on it.
    protected function buildCognitiveDistribution(Collection $topics, int $totalItems): array
    {
        $counts = [
            'remember' => 0,
            'understand' => 0,
            'apply' => 0,
            'analyze' => 0,
            'evaluate' => 0,
            'create' => 0,
        ];

        $cognitive_level = ['remember', 'understand', 'apply', 'analyze','evaluate','create'];

        foreach ($topics as $topic) {
            $metadata = $topic->metadata ?? [];
            $cognitive = $metadata['cognitive_emphasis'] ?? 'understand';

            if (!in_array($cognitive, $cognitive_level, true)) {
                // pick a random valid cognitive level if invalid value found
                $cognitive = $cognitive_level[array_rand($cognitive_level)];
            }

            $counts[$cognitive] += (int)($metadata['recommended_question_count'] ?? 4);
        }

        if ($totalItems <= 0) {
            return $counts;
        }

        return collect($counts)
            ->map(fn ($count) => round(($count / $totalItems) * 100, 2))
            ->toArray();
    }

    protected function mapCognitiveToBloom(string $cognitiveLevel): string
    {
        return match ($cognitiveLevel) {
            'remember' => 'knowledge',
            'understand' => 'comprehension',
            'apply' => 'application',
            'analyze' => 'analysis',
            'evaluate' => 'evaluation',
            'create' => 'synthesis',
            default => 'comprehension',
        };
    }
}

<?php

namespace App\Jobs;

use App\Models\Document;
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

        $totalItems = (int)$topics->sum(function (Topic $topic) {
            return (int)($topic->metadata['recommended_question_count'] ?? 0);
        });

        if ($totalItems <= 0) {
            $totalItems = max(1, $topics->count() * 4);
        }

        $distribution = $this->buildCognitiveDistribution($topics, $totalItems);

        $tos = TableOfSpecification::create([
            'document_id' => $document->id,
            'total_items' => $totalItems,
            'lots_percentage' => 100,
            'cognitive_level_distribution' => $distribution,
            'assessment_focus' => 'Topic-aligned LOTS assessment',
            'generated_at' => now(),
        ]);

        foreach ($topics as $topic) {
            $metadata = $topic->metadata ?? [];
            $recommended = (int)($metadata['recommended_question_count'] ?? 4);
            $cognitive = $metadata['cognitive_emphasis'] ?? 'understand';
            $keyConcepts = $metadata['key_concepts'] ?? [];

            TosItem::create([
                'tos_id' => $tos->id,
                'topic_id' => $topic->id,
                'learning_outcome_id' => null,
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

        return match ($level) {
            'remember', 'recall' => 'remember',
            'apply', 'application' => 'apply',
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

    protected function buildCognitiveDistribution(Collection $topics, int $totalItems): array
    {
        $counts = [
            'remember' => 0,
            'understand' => 0,
            'apply' => 0,
        ];

        foreach ($topics as $topic) {
            $metadata = $topic->metadata ?? [];
            $cognitive = $metadata['cognitive_emphasis'] ?? 'understand';

            if (!array_key_exists($cognitive, $counts)) {
                $cognitive = 'understand';
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
            'apply' => 'application',
            default => 'comprehension',
        };
    }
}
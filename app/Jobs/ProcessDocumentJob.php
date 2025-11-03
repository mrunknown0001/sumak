<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\Topic;
use App\Models\Subtopic;
use App\Models\TableOfSpecification;
use App\Models\TosItem;
use App\Services\OpenAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            
            // Step 1: Extract content from PDF
            $content = $this->extractContent($document);
            
            // Step 2: Analyze content with AI
            $analysis = $openAiService->analyzeContent($content);

            // log analysis result for debugging
            Log::debug('Document analysis result', ['analysis' => $analysis]);
            
            // Step 3: Create topics and subtopics
            $this->createTopicsAndSubtopics($document, $analysis);
            
            // Step 4: Generate Table of Specification
            $this->generateTableOfSpecification($document, $openAiService);
            
            // Step 5: Generate quiz questions
            GenerateQuizQuestionsJob::dispatch($document->id);

            $document->update([
                'processing_status' => Document::PROCESSING_COMPLETED,
                'processed_at' => now(),
                'processing_error' => null,
            ]);

            Log::info("Document processed successfully", ['document_id' => $document->id]);
            
        } catch (\Exception $e) {
            $document = Document::find($this->documentId);

            if ($document) {
                $document->update([
                    'processing_status' => Document::PROCESSING_FAILED,
                    'processing_error' => $e->getMessage(),
                ]);
            }

            Log::error("Failed to process document", [
                'document_id' => $this->documentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    protected function extractContent(Document $document): string
    {
        // $filePath = Storage::disk('public')->path($document->file_path);
        
        if ($document->file_type === 'pdf') {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($document->file_path);
            $content = $pdf->getText();
        } else {
            // For DOCX files, use a library like PHPWord
            $content = file_get_contents($document->file_path);
        }
        
        return $content;
    }

    protected function createTopicsAndSubtopics(Document $document, array $analysis): void
    {
        $topics = $analysis['main_topics'] ?? [];

        $topicCount = 0;
        $subtopicCount = 0;

        Log::debug('Creating topics and subtopics from analysis payload', [
            'document_id' => $document->id,
            'main_topic_count' => count($topics),
        ]);

        foreach ($topics as $index => $topicData) {
            $topicName = trim((string) ($topicData['topic'] ?? ''));

            if ($topicName === '') {
                $topicName = 'Topic ' . ($index + 1);
            }

            $topic = Topic::create([
                'document_id' => $document->id,
                'name' => $topicName,
                'order_index' => $index,
            ]);
            $topicCount++;

            $subtopics = $topicData['subtopics'] ?? [];

            foreach ($subtopics as $subIndex => $subtopicName) {
                $subtopicName = trim((string) $subtopicName);

                if ($subtopicName === '') {
                    Log::warning('Skipping empty subtopic name from analysis payload', [
                        'document_id' => $document->id,
                        'topic_id' => $topic->id,
                        'topic_name' => $topicName,
                        'subtopic_index' => $subIndex,
                    ]);
                    continue;
                }

                $subtopic = Subtopic::firstOrCreate(
                    [
                        'topic_id' => $topic->id,
                        'name' => $subtopicName,
                    ],
                    [
                        'order_index' => $subIndex,
                    ]
                );

                if (! $subtopic->wasRecentlyCreated && $subtopic->order_index !== $subIndex) {
                    $subtopic->update(['order_index' => $subIndex]);
                }

                $subtopicCount++;
            }
        }

        Log::info('Topic extraction summary', [
            'document_id' => $document->id,
            'topics_created' => $topicCount,
            'subtopics_created' => $subtopicCount,
        ]);
    }

    protected function generateTableOfSpecification(Document $document, OpenAiService $openAiService): void
    {
        // Get learning outcomes from OBTL if available
        $learningOutcomeRecords = collect();
        $learningOutcomes = [];

        if ($this->options['has_obtl'] ?? false) {
            $obtl = $document->course->obtlDocument;
            if ($obtl) {
                $learningOutcomeRecords = $obtl->learningOutcomes()
                    ->with('subOutcomes')
                    ->get();

                $learningOutcomes = $learningOutcomeRecords
                    ->map(fn($lo) => [
                        'outcome_code' => $lo->outcome_code,
                        'outcome' => $lo->description,
                        'bloom_level' => $lo->bloom_level,
                    ])
                    ->toArray();
            }
        }
        
        // Generate ToS using AI
        $materialSummary = $document->content_summary ?? "Lecture content from {$document->title}";
        $totalItems = rand(20, 40); // Fixed per requirement // TODO: Statically Assigned Values

        $tosData = $openAiService->generateToS($learningOutcomes, $materialSummary, $totalItems);
        $tosItems = $tosData['table_of_specification'] ?? [];

        $availableSubtopics = Subtopic::whereHas('topic', function ($q) use ($document) {
            $q->where('document_id', $document->id);
        })->get(['id', 'topic_id', 'name', 'order_index']);

        $topics = $document->topics()->get(['id', 'name', 'order_index']);

        $topicOrderCounter = $topics->max('order_index');
        if ($topicOrderCounter === null) {
            $topicOrderCounter = $topics->count() ? $topics->count() - 1 : -1;
        }

        $subtopicLookup = $availableSubtopics
            ->groupBy(fn ($subtopic) => $this->normalizeLabel($subtopic->name))
            ->map(fn ($group) => $group instanceof Collection ? $group : collect($group));

        Log::debug('Received ToS payload from OpenAI', [
            'document_id' => $document->id,
            'requested_total_items' => $totalItems,
            'tos_item_count' => count($tosItems),
            'available_subtopics_count' => $availableSubtopics->count(),
            'topic_count' => $topics->count(),
            'topic_order_counter' => $topicOrderCounter,
        ]);

        if (empty($tosItems)) {
            Log::warning('AI ToS payload contained zero items', [
                'document_id' => $document->id,
                'openai_response_keys' => is_array($tosData) ? array_keys($tosData) : null,
            ]);
        }

        // Create Table of Specification
        $tos = TableOfSpecification::create([
            'document_id' => $document->id,
            'total_items' => $totalItems,
            'lots_percentage' => 100, // LOTS focused
            'cognitive_level_distribution' => $tosData['cognitive_distribution'] ?? [],
            'assessment_focus' => $tosData['assessment_focus'] ?? 'LOTS-based assessment',
            'generated_at' => now(),
        ]);

        $createdCount = 0;
        $skippedCount = 0;

        // Create ToS items
        foreach ($tosItems as $index => $tosItemData) {
            $rawSubtopicLabel = trim((string) ($tosItemData['subtopic'] ?? ''));

            if ($rawSubtopicLabel === '') {
                Log::warning('Skipping ToS item due to missing subtopic name', [
                    'document_id' => $document->id,
                    'tos_id' => $tos->id,
                    'tos_item_index' => $index,
                ]);
                $skippedCount++;
                continue;
            }

            $matchContext = $this->buildSubtopicMatchContext($rawSubtopicLabel);

            $subtopicMatch = $this->matchSubtopicCandidates(
                $document,
                $matchContext['subtopic_candidates'],
                $subtopicLookup,
                $availableSubtopics
            );

            $subtopic = $subtopicMatch['subtopic'];

            if ($subtopic) {
                Log::info('Matched ToS item to existing subtopic', [
                    'document_id' => $document->id,
                    'tos_id' => $tos->id,
                    'tos_item_index' => $index,
                    'tos_subtopic' => $rawSubtopicLabel,
                    'matched_subtopic_id' => $subtopic->id,
                    'matched_subtopic_name' => $subtopic->name,
                    'match_strategy' => $subtopicMatch['strategy'],
                    'matched_candidate' => $subtopicMatch['candidate'],
                ]);
            }

            if (! $subtopic) {
                $topicMatch = $this->matchTopicCandidates(
                    $topics,
                    $matchContext['topic_candidates'],
                    $matchContext['subtopic_candidates']
                );

                $topic = $topicMatch['topic'];

                if ($topic && $topicMatch['strategy']) {
                    Log::info('Matched ToS item to existing topic', [
                        'document_id' => $document->id,
                        'tos_id' => $tos->id,
                        'tos_item_index' => $index,
                        'tos_subtopic' => $rawSubtopicLabel,
                        'topic_id' => $topic->id,
                        'topic_name' => $topic->name,
                        'match_strategy' => $topicMatch['strategy'],
                        'matched_candidate' => $topicMatch['candidate'],
                    ]);
                }

                if (! $topic) {
                    $topicOrderCounter++;
                    $topicName = $this->deriveTopicNameFromLabel(
                        $rawSubtopicLabel,
                        $matchContext['topic_candidates'],
                        $matchContext['subtopic_candidates']
                    );

                    $topic = Topic::create([
                        'document_id' => $document->id,
                        'name' => $topicName,
                        'order_index' => $topicOrderCounter,
                    ]);

                    $topics->push($topic);

                    Log::info('Created fallback topic for ToS item', [
                        'document_id' => $document->id,
                        'tos_id' => $tos->id,
                        'tos_item_index' => $index,
                        'tos_subtopic' => $rawSubtopicLabel,
                        'topic_id' => $topic->id,
                        'topic_name' => $topic->name,
                    ]);
                }

                $subtopicNameForCreation = $matchContext['preferred_subtopic_name'] ?: $rawSubtopicLabel;
                $nextOrderIndex = (Subtopic::where('topic_id', $topic->id)->max('order_index') ?? -1) + 1;

                $subtopic = Subtopic::firstOrCreate(
                    [
                        'topic_id' => $topic->id,
                        'name' => $subtopicNameForCreation,
                    ],
                    [
                        'order_index' => $nextOrderIndex,
                    ]
                );

                if (! $subtopic->wasRecentlyCreated && $subtopic->order_index !== $nextOrderIndex) {
                    $subtopic->update(['order_index' => $nextOrderIndex]);
                }

                $availableSubtopics->push($subtopic);

                $normalized = $this->normalizeLabel($subtopic->name);
                if ($normalized !== '') {
                    if (! $subtopicLookup->has($normalized)) {
                        $subtopicLookup->put($normalized, collect());
                    }

                    $subtopicLookup->get($normalized)->push($subtopic);
                }

                Log::info('Created fallback subtopic for ToS item', [
                    'document_id' => $document->id,
                    'tos_id' => $tos->id,
                    'tos_item_index' => $index,
                    'tos_subtopic' => $rawSubtopicLabel,
                    'subtopic_id' => $subtopic->id,
                    'subtopic_name' => $subtopic->name,
                    'preferred_subtopic_name' => $matchContext['preferred_subtopic_name'],
                    'topic_id' => $subtopic->topic_id,
                ]);
            }

            if (! $subtopic) {
                Log::warning('Unable to align ToS item with any subtopic', [
                    'document_id' => $document->id,
                    'tos_id' => $tos->id,
                    'tos_item_index' => $index,
                    'tos_subtopic' => $rawSubtopicLabel,
                    'available_subtopics_sample' => $availableSubtopics->take(5)->pluck('name')->all(),
                ]);
                $skippedCount++;
                continue;
            }

            $learningOutcomeId = $this->matchLearningOutcomeId(
                $tosItemData['learning_outcome'] ?? null,
                $learningOutcomeRecords
            );

            if (! $learningOutcomeId && isset($tosItemData['learning_outcome'])) {
                Log::debug('Unable to map learning outcome to ToS item', [
                    'document_id' => $document->id,
                    'tos_id' => $tos->id,
                    'tos_item_index' => $index,
                    'learning_outcome_label' => $tosItemData['learning_outcome'],
                ]);
            }

            TosItem::create([
                'tos_id' => $tos->id,
                'subtopic_id' => $subtopic->id,
                'learning_outcome_id' => $learningOutcomeId,
                'cognitive_level' => $tosItemData['cognitive_level'],
                'bloom_category' => $tosItemData['bloom_category'],
                'num_items' => $tosItemData['num_items'],
                'weight_percentage' => $tosItemData['weight_percentage'],
                'sample_indicators' => $tosItemData['sample_indicators'] ?? [],
            ]);
            $createdCount++;
        }

        Log::info('ToS item persistence summary', [
            'document_id' => $document->id,
            'tos_id' => $tos->id,
            'tos_items_expected' => count($tosItems),
            'tos_items_created' => $createdCount,
            'tos_items_skipped' => $skippedCount,
        ]);
    }

    protected function normalizeLabel(?string $value): string
    {
        return (string) Str::of($value ?? '')
            ->lower()
            ->squish()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim();
    }

    protected function findClosestSubtopicMatch(Collection $subtopics, string $candidate): ?Subtopic
    {
        $normalizedCandidate = $this->normalizeLabel($candidate);

        if ($normalizedCandidate === '') {
            return null;
        }

        $bestMatch = null;
        $bestDistance = null;

        foreach ($subtopics as $subtopic) {
            $distance = levenshtein(
                $normalizedCandidate,
                $this->normalizeLabel($subtopic->name)
            );

            if ($bestDistance === null || $distance < $bestDistance) {
                $bestDistance = $distance;
                $bestMatch = $subtopic;
            }
        }

        if ($bestDistance === null) {
            return null;
        }

        $maxAllowedDistance = max(2, (int) round(strlen($normalizedCandidate) * 0.3));
        
        return $bestDistance <= $maxAllowedDistance ? $bestMatch : null;
    }

    protected function findClosestTopicMatch(Collection $topics, string $candidate): ?Topic
    {
        if ($topics->isEmpty()) {
            return null;
        }

        $normalizedCandidate = $this->normalizeLabel($candidate);

        if ($normalizedCandidate === '') {
            return null;
        }

        $bestTopic = null;
        $bestDistance = null;

        foreach ($topics as $topic) {
            $distance = levenshtein(
                $normalizedCandidate,
                $this->normalizeLabel($topic->name)
            );

            if ($bestDistance === null || $distance < $bestDistance) {
                $bestDistance = $distance;
                $bestTopic = $topic;
            }
        }

        if ($bestDistance === null) {
            return null;
        }

        $maxAllowedDistance = max(3, (int) round(strlen($normalizedCandidate) * 0.4));

        return $bestDistance <= $maxAllowedDistance ? $bestTopic : null;
    }

    protected function buildSubtopicMatchContext(string $label): array
    {
        $label = trim($label);

        $subtopicCandidates = [];
        $topicCandidates = [];
        $preferred = null;

        if ($label !== '') {
            $subtopicCandidates[] = $label;
            $preferred = $label;
        }

        if (Str::contains($label, ':')) {
            [$topicPart, $detailPart] = array_map('trim', explode(':', $label, 2));
            if ($topicPart !== '') {
                $topicCandidates[] = $topicPart;
            }
            if ($detailPart !== '') {
                $subtopicCandidates[] = $detailPart;
                $preferred = $detailPart;
            }
        }

        foreach ([';', '|', '/', ' - ', ' – ', ' — '] as $delimiter) {
            if (Str::contains($label, $delimiter)) {
                foreach (explode($delimiter, $label) as $segment) {
                    $segment = trim($segment);
                    if ($segment !== '') {
                        $subtopicCandidates[] = $segment;
                    }
                }
            }
        }

        $subtopicCandidates = array_values(array_unique(array_filter($subtopicCandidates, fn ($value) => $value !== '')));
        $topicCandidates = array_values(array_unique(array_filter($topicCandidates, fn ($value) => $value !== '')));

        if (! $preferred || $preferred === '') {
            $preferred = $subtopicCandidates[0] ?? $label;
        }

        return [
            'raw_label' => $label,
            'subtopic_candidates' => $subtopicCandidates,
            'topic_candidates' => $topicCandidates,
            'preferred_subtopic_name' => $preferred,
        ];
    }

    protected function matchSubtopicCandidates(
        Document $document,
        array $candidates,
        Collection $subtopicLookup,
        Collection $availableSubtopics
    ): array {
        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);

            if ($candidate === '') {
                continue;
            }

            $normalized = $this->normalizeLabel($candidate);
            if ($normalized !== '' && $subtopicLookup->has($normalized) && $subtopicLookup->get($normalized)->isNotEmpty()) {
                return [
                    'subtopic' => $subtopicLookup->get($normalized)->first(),
                    'strategy' => 'normalized_lookup',
                    'candidate' => $candidate,
                ];
            }

            $exactMatch = $availableSubtopics->first(function ($subtopic) use ($normalized) {
                return $normalized !== '' && $this->normalizeLabel($subtopic->name) === $normalized;
            });

            if ($exactMatch) {
                return [
                    'subtopic' => $exactMatch,
                    'strategy' => 'collection_exact',
                    'candidate' => $candidate,
                ];
            }

            $containsMatch = $availableSubtopics->first(function ($subtopic) use ($candidate) {
                return stripos($subtopic->name, $candidate) !== false;
            });

            if ($containsMatch) {
                return [
                    'subtopic' => $containsMatch,
                    'strategy' => 'collection_contains',
                    'candidate' => $candidate,
                ];
            }
        }

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);

            if ($candidate === '') {
                continue;
            }

            $subtopic = Subtopic::whereHas('topic', function ($q) use ($document) {
                    $q->where('document_id', $document->id);
                })
                ->where('name', 'like', '%' . $candidate . '%')
                ->orderBy('order_index')
                ->first();

            if ($subtopic) {
                return [
                    'subtopic' => $subtopic,
                    'strategy' => 'database_like',
                    'candidate' => $candidate,
                ];
            }
        }

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);

            if ($candidate === '') {
                continue;
            }

            $fuzzy = $this->findClosestSubtopicMatch($availableSubtopics, $candidate);

            if ($fuzzy) {
                return [
                    'subtopic' => $fuzzy,
                    'strategy' => 'fuzzy',
                    'candidate' => $candidate,
                ];
            }
        }

        return [
            'subtopic' => null,
            'strategy' => null,
            'candidate' => null,
        ];
    }

    protected function matchTopicCandidates(
        Collection $topics,
        array $topicCandidates,
        array $subtopicCandidates
    ): array {
        $candidates = array_values(array_unique(array_merge($topicCandidates, $subtopicCandidates)));

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);

            if ($candidate === '') {
                continue;
            }

            $normalized = $this->normalizeLabel($candidate);

            $exact = $topics->first(function ($topic) use ($normalized) {
                return $normalized !== '' && $this->normalizeLabel($topic->name) === $normalized;
            });

            if ($exact) {
                return [
                    'topic' => $exact,
                    'strategy' => 'exact',
                    'candidate' => $candidate,
                ];
            }

            $contains = $topics->first(function ($topic) use ($candidate) {
                return stripos($topic->name, $candidate) !== false;
            });

            if ($contains) {
                return [
                    'topic' => $contains,
                    'strategy' => 'contains',
                    'candidate' => $candidate,
                ];
            }
        }

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);

            if ($candidate === '') {
                continue;
            }

            $fuzzy = $this->findClosestTopicMatch($topics, $candidate);

            if ($fuzzy) {
                return [
                    'topic' => $fuzzy,
                    'strategy' => 'fuzzy',
                    'candidate' => $candidate,
                ];
            }
        }

        return [
            'topic' => null,
            'strategy' => null,
            'candidate' => null,
        ];
    }

    protected function matchLearningOutcomeId(?string $label, Collection $learningOutcomeRecords): ?int
    {
        if (! is_string($label) || trim($label) === '' || $learningOutcomeRecords->isEmpty()) {
            return null;
        }

        $normalizedLabel = $this->normalizeLabel($label);

        if ($normalizedLabel === '') {
            return null;
        }

        $exact = $learningOutcomeRecords->first(function ($outcome) use ($normalizedLabel) {
            $code = $this->normalizeLabel($outcome->outcome_code ?? '');
            $description = $this->normalizeLabel($outcome->description ?? '');

            return $code === $normalizedLabel || $description === $normalizedLabel;
        });

        if ($exact) {
            return $exact->id;
        }

        $labelLower = mb_strtolower($label);

        $contains = $learningOutcomeRecords->first(function ($outcome) use ($labelLower) {
            $codeLower = $outcome->outcome_code ? mb_strtolower($outcome->outcome_code) : null;
            $descriptionLower = $outcome->description ? mb_strtolower($outcome->description) : null;

            return ($codeLower && (str_contains($labelLower, $codeLower) || str_contains($codeLower, $labelLower)))
                || ($descriptionLower && (str_contains($labelLower, $descriptionLower) || str_contains($descriptionLower, $labelLower)));
        });

        if ($contains) {
            return $contains->id;
        }

        return $this->findClosestLearningOutcomeMatch($learningOutcomeRecords, $normalizedLabel);
    }

    protected function findClosestLearningOutcomeMatch(Collection $learningOutcomeRecords, string $normalizedLabel): ?int
    {
        if ($normalizedLabel === '') {
            return null;
        }

        $bestOutcome = null;
        $bestDistance = null;

        foreach ($learningOutcomeRecords as $outcome) {
            $candidates = array_filter([
                $this->normalizeLabel($outcome->description ?? ''),
                $this->normalizeLabel($outcome->outcome_code ?? ''),
            ]);

            foreach ($candidates as $candidate) {
                $distance = levenshtein($normalizedLabel, $candidate);

                if ($bestDistance === null || $distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestOutcome = $outcome;
                }
            }
        }

        if ($bestOutcome === null) {
            return null;
        }

        $maxAllowedDistance = max(2, (int) round(strlen($normalizedLabel) * 0.3));

        return $bestDistance <= $maxAllowedDistance ? $bestOutcome->id : null;
    }

    protected function deriveTopicNameFromLabel(string $rawLabel, array $topicCandidates, array $subtopicCandidates): string
    {
        $label = trim($rawLabel);

        if (! empty($topicCandidates)) {
            return $topicCandidates[0];
        }

        if (! empty($subtopicCandidates)) {
            return $subtopicCandidates[0];
        }

        return $label !== '' ? $label : 'Generated Topic';
    }
}
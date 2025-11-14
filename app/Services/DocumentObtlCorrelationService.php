<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

class DocumentObtlCorrelationService
{
    public function __construct(
        protected float $defaultThreshold = 60.0,
        protected ?PdfParser $pdfParser = null,
    ) {
        $this->pdfParser = $this->pdfParser ?: new PdfParser();
    }

    public function getDefaultThreshold(): float
    {
        return $this->defaultThreshold;
    }

    /**
     * Evaluate the correlation between the uploaded material and the course OBTL document.
     *
     * @param  string  $materialAbsolutePath
     * @param  string  $fileExtension
     * @param  Course  $course
     * @param  float|null  $thresholdOverride
     * @return array{score: float, threshold: float, metadata: array}
     */
    public function evaluate(string $materialAbsolutePath, string $fileExtension, Course $course, ?float $thresholdOverride = null): array
    {
        $threshold = $thresholdOverride ?? $this->defaultThreshold;

        $course->loadMissing('obtlDocument.learningOutcomes');

        $obtlDocument = $course->obtlDocument;

        if (! $obtlDocument) {
            throw new RuntimeException('No OBTL document has been uploaded for this course.');
        }

        $learningOutcomes = $obtlDocument->learningOutcomes;

        if ($learningOutcomes->isEmpty()) {
            throw new RuntimeException('The OBTL document does not contain any learning outcomes to correlate.');
        }

        $materialText = $this->extractMaterialText($materialAbsolutePath, $fileExtension);
        $obtlText = $this->composeObtlReferenceText($learningOutcomes);

        $materialTokens = $this->tokenize($materialText);
        $obtlTokens = $this->tokenize($obtlText);

        if ($materialTokens->isEmpty()) {
            return [
                'score' => 0.0,
                'threshold' => $threshold,
                'metadata' => [
                    'obtl_token_count' => $obtlTokens->unique()->count(),
                    'material_token_count' => 0,
                    'overlap_count' => 0,
                    'sample_overlap_tokens' => [],
                    'notes' => 'Material text could not be extracted or contained no evaluable content.',
                ],
            ];
        }

        $obtlUnique = $obtlTokens->unique()->values();
        $materialUnique = $materialTokens->unique()->values();

        $overlap = $obtlUnique->intersect($materialUnique)->values();

        $coverage = $obtlUnique->count() > 0
            ? ($overlap->count() / $obtlUnique->count()) * 100
            : 0.0;

        $score = round($coverage, 2);

        return [
            'score' => $score,
            'threshold' => $threshold,
            'metadata' => [
                'obtl_token_count' => $obtlUnique->count(),
                'material_token_count' => $materialUnique->count(),
                'overlap_count' => $overlap->count(),
                'sample_overlap_tokens' => $overlap->take(25)->values()->all(),
                'overlap_ratio' => $score,
            ],
        ];
    }

    protected function extractMaterialText(string $absolutePath, string $fileExtension): string
    {
        $extension = strtolower($fileExtension);

        try {
            return match ($extension) {
                'pdf' => $this->extractPdfText($absolutePath),
                'docx' => $this->extractDocxText($absolutePath),
                'txt', 'md', 'text', 'csv' => file_get_contents($absolutePath) ?: '',
                default => file_get_contents($absolutePath) ?: '',
            };
        } catch (\Throwable $throwable) {
            Log::warning('Failed to extract material text for correlation evaluation', [
                'path' => $absolutePath,
                'extension' => $extension,
                'error' => $throwable->getMessage(),
            ]);

            return '';
        }
    }

    protected function extractPdfText(string $absolutePath): string
    {
        $pdf = $this->pdfParser->parseFile($absolutePath);

        return $pdf->getText() ?? '';
    }

    protected function extractDocxText(string $absolutePath): string
    {
        $zip = new ZipArchive();

        if ($zip->open($absolutePath) !== true) {
            return '';
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! $xmlContent) {
            return '';
        }

        $xmlContent = str_replace(['</w:p>', '</w:tr>'], ["\n", "\n"], $xmlContent);

        return strip_tags($xmlContent);
    }

    /**
     * @param  Collection<int, \App\Models\LearningOutcome>  $learningOutcomes
     */
    protected function composeObtlReferenceText(Collection $learningOutcomes): string
    {
        return $learningOutcomes
            ->map(function ($outcome) {
                $components = [
                    $outcome->outcome_code,
                    $outcome->description,
                    $outcome->bloom_level,
                    optional($outcome->subOutcomes)->pluck('description')->implode(' ') ?? '',
                ];

                return implode(' ', array_filter($components));
            })
            ->implode("\n");
    }

    protected function tokenize(string $text): Collection
    {
        $normalized = Str::lower($text);
        $normalized = preg_replace('/[^a-z0-9\s]/u', ' ', $normalized ?? '');
        $tokens = preg_split('/\s+/', $normalized ?? '', -1, PREG_SPLIT_NO_EMPTY);

        $stopWords = $this->stopWords();

        return collect($tokens)
            ->map(fn ($token) => trim($token))
            ->filter(fn ($token) => $token !== '' && ! in_array($token, $stopWords, true) && Str::length($token) > 2);
    }

    protected function stopWords(): array
    {
        return [
            'and',
            'the',
            'for',
            'with',
            'this',
            'that',
            'from',
            'into',
            'your',
            'have',
            'has',
            'will',
            'would',
            'could',
            'should',
            'about',
            'after',
            'before',
            'between',
            'because',
            'while',
            'where',
            'which',
            'their',
            'there',
            'them',
            'then',
            'than',
            'also',
            'such',
            'when',
            'what',
            'each',
            'every',
            'very',
            'over',
            'under',
            'through',
            'upon',
            'within',
            'without',
            'using',
            'make',
            'made',
            'many',
            'some',
            'more',
            'most',
            'much',
            'being',
        ];
    }
}
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
            
            // Step 1: Extract content from PDF
            $content = $this->extractContent($document);
            
            // Step 2: Analyze content with AI
            $analysis = $openAiService->analyzeContent($content);
            
            // Step 3: Create topics and subtopics
            $this->createTopicsAndSubtopics($document, $analysis);
            
            // Step 4: Generate Table of Specification
            $this->generateTableOfSpecification($document, $openAiService);
            
            // Step 5: Generate quiz questions
            GenerateQuizQuestionsJob::dispatch($document->id);
            
            Log::info("Document processed successfully", ['document_id' => $document->id]);
            
        } catch (\Exception $e) {
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
        $filePath = Storage::disk('private')->path($document->file_path);
        
        if ($document->file_type === 'pdf') {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            $content = $pdf->getText();
        } else {
            // For DOCX files, use a library like PHPWord
            $content = file_get_contents($filePath);
        }
        
        return $content;
    }

    protected function createTopicsAndSubtopics(Document $document, array $analysis): void
    {
        $topics = $analysis['topics'] ?? [];
        
        foreach ($topics as $index => $topicData) {
            $topic = Topic::create([
                'document_id' => $document->id,
                'name' => $topicData['name'],
                'order_index' => $index,
            ]);
            
            $subtopics = $topicData['subtopics'] ?? [];
            
            foreach ($subtopics as $subIndex => $subtopicName) {
                Subtopic::create([
                    'topic_id' => $topic->id,
                    'name' => $subtopicName,
                    'order_index' => $subIndex,
                ]);
            }
        }
    }

    protected function generateTableOfSpecification(Document $document, OpenAiService $openAiService): void
    {
        // Get learning outcomes from OBTL if available
        $learningOutcomes = [];
        if ($this->options['has_obtl'] ?? false) {
            $obtl = $document->course->obtlDocument;
            if ($obtl) {
                $learningOutcomes = $obtl->learningOutcomes()
                    ->with('subOutcomes')
                    ->get()
                    ->map(fn($lo) => [
                        'outcome' => $lo->description,
                        'bloom_level' => $lo->bloom_level,
                    ])
                    ->toArray();
            }
        }
        
        // Generate ToS using AI
        $materialSummary = $document->content_summary ?? "Lecture content from {$document->title}";
        $totalItems = 20; // Fixed per requirement
        
        $tosData = $openAiService->generateToS($learningOutcomes, $materialSummary, $totalItems);
        
        // Create Table of Specification
        $tos = TableOfSpecification::create([
            'document_id' => $document->id,
            'total_items' => $totalItems,
            'lots_percentage' => 100, // LOTS focused
            'cognitive_level_distribution' => $tosData['cognitive_distribution'] ?? [],
            'assessment_focus' => $tosData['assessment_focus'] ?? 'LOTS-based assessment',
            'generated_at' => now(),
        ]);
        
        // Create ToS items
        foreach ($tosData['table_of_specification'] as $tosItemData) {
            // Find matching subtopic
            $subtopic = Subtopic::whereHas('topic', function($q) use ($document) {
                $q->where('document_id', $document->id);
            })
            ->where('name', 'like', '%' . $tosItemData['subtopic'] . '%')
            ->first();
            
            if ($subtopic) {
                TosItem::create([
                    'tos_id' => $tos->id,
                    'subtopic_id' => $subtopic->id,
                    'cognitive_level' => $tosItemData['cognitive_level'],
                    'bloom_category' => $tosItemData['bloom_category'],
                    'num_items' => $tosItemData['num_items'],
                    'weight_percentage' => $tosItemData['weight_percentage'],
                    'sample_indicators' => $tosItemData['sample_indicators'] ?? [],
                ]);
            }
        }
    }
}
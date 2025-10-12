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
                $this->generateQuestionsForTosItem($tosItem, $materialContent, $openAiService, $irtService);
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
        IrtService $irtService
    ): void {
        // Check if questions already exist
        $existingCount = $tosItem->items()->count();

        Log::debug('Evaluating ToS item for question generation', [
            'tos_item_id' => $tosItem->id,
            'existing_questions' => $existingCount,
            'required_questions' => $tosItem->num_items,
        ]);
        
        if ($existingCount >= $tosItem->num_items) {
            Log::info("Questions already exist for ToS item", ['tos_item_id' => $tosItem->id]);
            return;
        }
        
        $questionsNeeded = $tosItem->num_items - $existingCount;
        
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
            $questionsNeeded
        );

        $generatedQuestions = $questionsData['questions'] ?? [];

        if (empty($generatedQuestions)) {
            Log::warning('AI returned no questions for ToS item', [
                'tos_item_id' => $tosItem->id,
                'questions_requested' => $questionsNeeded,
                'openai_response_keys' => is_array($questionsData) ? array_keys($questionsData) : null,
            ]);
            return;
        }
        
        // Save questions to item bank
        foreach ($generatedQuestions as $questionData) {
            // Estimate initial difficulty (will be refined based on responses)
            $estimatedDifficulty = $questionData['estimated_difficulty'] ?? 0;
            
            // Convert difficulty from 0-1 scale to IRT scale (-3 to 3)
            $difficultyB = ($estimatedDifficulty - 0.5) * 4;
            
            ItemBank::create([
                'tos_item_id' => $tosItem->id,
                'subtopic_id' => $tosItem->subtopic_id,
                'learning_outcome_id' => $tosItem->learning_outcome_id,
                'question' => $questionData['question_text'],
                'options' => $questionData['options'],
                'correct_answer' => collect($questionData['options'])
                    ->firstWhere('is_correct', true)['option_letter'],
                'explanation' => $questionData['explanation'],
                'cognitive_level' => $questionData['cognitive_level'],
                'difficulty_b' => $difficultyB,
                'time_estimate_seconds' => $questionData['time_estimate_seconds'] ?? 60,
                'created_at' => now(),
            ]);
        }
        
        Log::info("Generated questions for ToS item", [
            'tos_item_id' => $tosItem->id,
            'questions_generated' => count($questionsData['questions'])
        ]);
    }
}
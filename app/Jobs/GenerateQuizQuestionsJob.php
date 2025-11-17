<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\ItemBank;
use App\Models\TosItem;
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
            $document = Document::with(['tableOfSpecification.tosItems.topic'])
                ->findOrFail($this->documentId);

            $tos = $document->tableOfSpecification;

            if (!$tos || $tos->tosItems->isEmpty()) {
                Log::warning("Document has no ToS or ToS Items — skipping AI generation", [
                    'document_id' => $document->id
                ]);
                return;
            }

            // Use full content if possible — summaries are unreliable for strict extraction.
            $materialContent = $document->content ?? $document->content_summary ?? $document->title;

            Log::info("Starting STRICT AI question generation", [
                'document_id' => $document->id,
                'tos_items' => $tos->tosItems->count(),
            ]);

            foreach ($tos->tosItems as $tosItem) {
                $this->processTosItem($tosItem, $materialContent, $openAiService);
            }

            Log::info("STRICT AI question generation completed successfully", [
                'document_id' => $document->id,
            ]);

        } catch (\Exception $e) {
            Log::error("GenerateQuizQuestionsJob failed", [
                'document_id' => $this->documentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Handle each ToS item cleanly and safely.
     */
    protected function processTosItem(
        TosItem $tosItem,
        string $materialContent,
        OpenAiService $openAiService
    ): void {

        $topic = $tosItem->topic;
        $topicName = $topic->name;
        $questionsNeeded = max(1, (int) $tosItem->num_items);

        Log::info("Generating AI questions for ToS item", [
            'tos_item_id' => $tosItem->id,
            'topic' => $topicName,
            'required_questions' => $questionsNeeded,
        ]);

        // Build the proper expected topic format for OpenAiService
        $topics = [
            [
                "name" => $topicName,
                "num_items" => $questionsNeeded
            ]
        ];

        // Call strict AI generator
        $result = $openAiService->generateQuizQuestions(
            topics: $topics,
            materialContent: $materialContent,
            options: [
                "model" => "gpt-4.1",
                "max_attempts" => 3,
                "temperature" => 0.0,
                "top_p" => 0.0,
            ]
        );

        $questions = $result['questions'] ?? [];

        if (empty($questions)) {
            Log::error("AI returned no valid questions after strict validation", [
                'tos_item_id' => $tosItem->id,
                'topic' => $topicName,
                'metadata' => $result['metadata'] ?? null
            ]);
            return;
        }

        // Save each validated question
        $this->storeGeneratedQuestions($tosItem, $questions);

        Log::info("Saved STRICT AI questions", [
            'tos_item_id' => $tosItem->id,
            'topic' => $topicName,
            'count' => count($questions)
        ]);
    }

    /**
     * Store validated AI-generated questions.
     */
    protected function storeGeneratedQuestions(TosItem $tosItem, array $questions): void
    {
        foreach ($questions as $q) {

            $correctOption = collect($q['options'])->firstWhere('is_correct', true);

            ItemBank::create([
                'tos_item_id'        => $tosItem->id,
                'topic_id'           => $tosItem->topic_id,
                'learning_outcome_id'=> $tosItem->learning_outcome_id,

                // AI-validated content
                'question'       => $q['question_text'],
                'options'        => $q['options'],
                'correct_answer' => $correctOption['option_letter'] ?? null,
                'explanation'    => $q['explanation'],
                'source_excerpt' => $q['source_excerpt'],

                'cognitive_level'=> $q['cognitive_level'] ?? 'remember',
                'difficulty'     => $q['difficulty'] ?? 'easy',

                // Convert difficulty (optionally used by IRT)
                'difficulty_b' => $this->mapDifficultyToIRT($q['difficulty'] ?? 'easy'),

                // Estimate solving time
                'time_estimate_seconds' => 60,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Map AI difficulty ("easy/medium/hard") to numeric IRT.
     */
    protected function mapDifficultyToIRT(string $difficulty): float
    {
        return match ($difficulty) {
            'easy'   => -1.0,
            'medium' => 0.0,
            'hard'   => 1.0,
            default  => 0.0,
        };
    }
}

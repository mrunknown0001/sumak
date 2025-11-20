<?php

namespace App\Jobs;

use App\Models\ObtlDocument;
use App\Services\OpenAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;
use Illuminate\Support\Facades\DB;

class ExtractObtlDocumentJob implements ShouldQueue
{
   use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes

    protected int $ObtlDocumentId;
    /**
     * Create a new job instance.
     */
    public function __construct(int $ObtlDocumentId)
    {
        $this->ObtlDocumentId = $ObtlDocumentId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $obtlDocument = ObtlDocument::find($this->ObtlDocumentId);
        if (! $obtlDocument) {
            Log::error("OBTL Document not found: {$this->ObtlDocumentId}");
            return;
        }

        $initialStatus = $obtlDocument->processing_status;

        Log::info('ExtractObtlDocumentJob started', [
            'obtl_document_id' => $this->ObtlDocumentId,
            'initial_status' => $initialStatus,
            'has_processed_at' => ! is_null($obtlDocument->processed_at),
        ]);

        $obtlDocument->update([
            'processing_status' => ObtlDocument::PROCESSING_IN_PROGRESS,
            'processed_at' => null,
            'error_message' => null,
        ]);

        // Extract title from the document
        $parser = new PdfParser();
        $pdf = $parser->parseFile($obtlDocument->file_path);
        $text = $pdf->getText();
        // Log::info($text);
        
        // // Use OpenAI service to extract title
        $openAiService = new OpenAiService();
        DB::beginTransaction();
        try {
            // Extract learning outcomes, topics, subtopics, and assessment methods
            $extraction = $openAiService->parseObtlDocument($text);
            // Log::debug('OBTL Document extraction result', ['extraction' => $extraction]);
            $obtlDocument->title = $extraction['title_info']['title'] ?? 'Untitled OBTL Document';
            $obtlDocument->save();

            // check if extraction has learning outcomes
            if (isset($extraction['learning_outcomes'])) {
                foreach ($extraction['learning_outcomes'] as $lo) {
                    $obtlDocument->learningOutcomes()->create([
                        'outcome_code' => $lo['outcome_code'] ?? 'n/a',
                        'description' => $lo['outcome_statement'] ?? 'n/a',
                        'bloom_level' => $lo['cognitive_level'] ?? 'n/a',
                    ]);
                }
            }

            DB::commit();

            $obtlDocument->update([
                'processing_status' => ObtlDocument::PROCESSING_COMPLETED,
                'processed_at' => now(),
                'error_message' => null,
            ]);

            $obtlDocument->refresh();

            // Log::info('ExtractObtlDocumentJob finished', [
            //     'obtl_document_id' => $this->ObtlDocumentId,
            //     'status_before' => $initialStatus,
            //     'status_after' => $obtlDocument->processing_status,
            //     'processed_at' => optional($obtlDocument->processed_at)?->toDateTimeString(),
            //     'title' => $obtlDocument->title,
            //     'learning_outcomes_created' => $obtlDocument->learningOutcomes()->count(),
            // ]);
        } catch (\Exception $e) {
            DB::rollBack();

            $obtlDocument->update([
                'processing_status' => ObtlDocument::PROCESSING_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            Log::error("Failed to extract OBTL Document", [
                'obtl_document_id' => $this->ObtlDocumentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

}

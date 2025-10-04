<?php

namespace App\Jobs;

use App\Models\Material;
use App\Models\AiAnalysis;
use App\Models\TableOfSpecification;
use App\Models\Quiz;
use App\Services\OpenAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessMaterialJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes
    public $backoff = [60, 120, 300]; // Retry delays

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $materialId,
        public ?int $obtlDocumentId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OpenAiService $openAiService): void
    {
        try {
            DB::beginTransaction();

            $material = Material::findOrFail($this->materialId);
            
            // Update status
            $material->update(['processing_status' => 'processing']);

            // Step 1: Analyze content
            Log::info("Starting content analysis for material {$this->materialId}");
            $analysis = $openAiService->analyzeContent(
                $material->content,
                $material->obtlDocument?->parsed_content
            );

            // Store analysis results
            $aiAnalysis = AiAnalysis::create([
                'material_id' => $material->id,
                'extracted_content' => json_encode($analysis),
                'key_concepts' => json_encode($analysis['key_concepts']),
                'difficulty_assessment' => $analysis['content_summary'],
                'api_version' => config('services.openai.model'),
            ]);

            Log::info("Content analysis completed for material {$this->materialId}");

            // Step 2: Generate Table of Specification
            Log::info("Generating ToS for material {$this->materialId}");
            $tosData = $openAiService->generateToS(
                $analysis['suggested_learning_outcomes'],
                $analysis['content_summary'],
                20
            );

            $tos = TableOfSpecification::create([
                'material_id' => $material->id,
                'cognitive_level_distribution' => json_encode($tosData['cognitive_distribution']),
                'total_items' => $tosData['total_items'],
                'lots_percentage' => $this->calculateLotsPercentage($tosData['cognitive_distribution']),
            ]);

            // Store ToS items
            foreach ($tosData['table_of_specification'] as $item) {
                $tos->tosItems()->create([
                    'subtopic' => $item['subtopic'],
                    'cognitive_level' => $item['cognitive_level'],
                    'bloom_category' => $item['bloom_category'],
                    'num_items' => $item['num_items'],
                    'weight_percentage' => $item['weight_percentage'],
                    'sample_indicators' => json_encode($item['sample_indicators'] ?? []),
                ]);
            }

            Log::info("ToS generated for material {$this->materialId}");

            // Step 3: Dispatch quiz generation job
            GenerateQuizJob::dispatch($material->id, $tos->id);

            // Update material status
            $material->update([
                'processing_status' => 'analyzed',
                'processed_at' => now(),
            ]);

            DB::commit();

            Log::info("Material {$this->materialId} processed successfully");

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Failed to process material {$this->materialId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update material status to failed
            Material::where('id', $this->materialId)
                ->update([
                    'processing_status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);

            // Re-throw exception to trigger retry
            throw $e;
        }
    }

    /**
     * Calculate percentage of LOTS items
     */
    private function calculateLotsPercentage(array $distribution): float
    {
        $lotsLevels = ['remember', 'understand', 'apply'];
        $lotsPercentage = 0;

        foreach ($lotsLevels as $level) {
            if (isset($distribution[$level])) {
                $lotsPercentage += $distribution[$level];
            }
        }

        return $lotsPercentage;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessMaterialJob failed permanently for material {$this->materialId}", [
            'error' => $exception->getMessage()
        ]);

        Material::where('id', $this->materialId)
            ->update([
                'processing_status' => 'failed',
                'error_message' => 'Processing failed after multiple attempts: ' . $exception->getMessage()
            ]);
    }
}
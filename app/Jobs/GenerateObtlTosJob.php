<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\ObtlDocument;
use App\Models\TableOfSpecification;
use App\Models\TosItem;
use App\Services\OpenAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateObtlTosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes

    protected int $courseId;
    protected int $midTermItems;
    protected int $finalTermItems;

    /**
     * Create a new job instance.
     */
    public function __construct(int $courseId, int $midTermItems, int $finalTermItems)
    {
        $this->courseId = $courseId;
        $this->midTermItems = $midTermItems;
        $this->finalTermItems = $finalTermItems;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $course = Course::find($this->courseId);
        if (!$course) {
            Log::error("Course not found: {$this->courseId}");
            return;
        }

        $openAiService = new OpenAiService();

        DB::beginTransaction();
        try {
            // Get learning outcomes and topics
            $learningOutcomes = $course->obtlDocument->learningOutcomes->toArray();
            $topics = $course->topics->toArray();

            // Generate midterm ToS
            $midtermTos = $openAiService->generateObtlTos($learningOutcomes, $topics, $this->midTermItems, 'midterm');
            $this->createTos($course, $midtermTos, 'midterm');

            // Generate final ToS
            $finalTos = $openAiService->generateObtlTos($learningOutcomes, $topics, $this->finalTermItems, 'final');
            $this->createTos($course, $finalTos, 'final');

            DB::commit();

            // Update course workflow stage
            $course->update([
                'workflow_stage' => Course::WORKFLOW_STAGE_TOS_GENERATED,
            ]);

            // update OBTL Document
            $course->obtlDocument->update([
                'processing_status' => ObtlDocument::PROCESSING_COMPLETED,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to generate OBTL ToS for course {$this->courseId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create ToS and TosItems
     */
    private function createTos(Course $course, array $tosData, string $term): void
    {
        if (!isset($tosData['table_of_specification'])) {
            return;
        }

        $tos = TableOfSpecification::create([
            'course_id' => $course->id,
            'term' => $term,
            'total_items' => $tosData['total_items'] ?? 0,
            'cognitive_level_distribution' => $tosData['cognitive_distribution'] ?? [],
            'assessment_focus' => $tosData['assessment_focus'] ?? '',
            'generated_at' => now(),
        ]);

        foreach ($tosData['table_of_specification'] as $itemData) {
            // Find topic by name
            $topic = $course->topics()->where('name', $itemData['topic'])->first();

            // Find learning outcome by description
            $learningOutcome = $course->obtlDocument->learningOutcomes()
                ->where('description', $itemData['learning_outcome'])
                ->first();

            if ($topic && $learningOutcome) {
                TosItem::create([
                    'tos_id' => $tos->id,
                    'topic_id' => $topic->id,
                    'learning_outcome_id' => $learningOutcome->id,
                    'cognitive_level' => $itemData['cognitive_level'] ?? 'remember',
                    'bloom_category' => $itemData['cognitive_level'] ?? 'remember',
                    'num_items' => $itemData['num_items'] ?? 0,
                    'weight_percentage' => $itemData['weight_percentage'] ?? 0,
                    'sample_indicators' => $itemData['sample_indicators'] ?? [],
                ]);
            }
        }
    }
}
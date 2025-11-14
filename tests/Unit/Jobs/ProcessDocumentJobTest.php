<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessDocumentJob;
use App\Models\Course;
use App\Models\Document;
use App\Models\LearningOutcome;
use App\Models\ObtlDocument;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProcessDocumentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_table_of_specification_assigns_learning_outcomes(): void
    {
        $user = User::factory()->create();

        $course = Course::create([
            'user_id' => $user->id,
            'course_code' => 'PHYS101',
            'course_title' => 'Physics 101',
            'description' => 'Introductory physics course',
            'workflow_stage' => Course::WORKFLOW_STAGE_OBTL_UPLOADED,
        ]);

        $obtlDocument = ObtlDocument::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'title' => 'Course OBTL',
            'file_path' => '/tmp/obtl.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'uploaded_at' => now(),
            'processing_status' => ObtlDocument::PROCESSING_COMPLETED,
            'processed_at' => now(),
        ]);

        $mechanicsOutcome = LearningOutcome::create([
            'obtl_document_id' => $obtlDocument->id,
            'outcome_code' => 'LO1',
            'description' => 'Explain Newtonian mechanics principles and force relationships',
            'bloom_level' => 'understand',
        ]);

        $respirationOutcome = LearningOutcome::create([
            'obtl_document_id' => $obtlDocument->id,
            'outcome_code' => 'LO2',
            'description' => 'Describe cellular respiration pathways and energy conversion',
            'bloom_level' => 'remember',
        ]);

        $document = Document::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'title' => 'Physics Material',
            'file_path' => '/tmp/physics.pdf',
            'file_type' => 'pdf',
            'file_size' => 2048,
            'content_summary' => 'Summary of various science topics',
            'uploaded_at' => now(),
            'processing_status' => Document::PROCESSING_PENDING,
        ]);

        $mechanicsTopic = Topic::create([
            'document_id' => $document->id,
            'name' => 'Newtonian Mechanics',
            'description' => 'Explore Newton laws and force dynamics',
            'metadata' => [
                'recommended_question_count' => 4,
                'cognitive_emphasis' => 'apply',
                'key_concepts' => ['Newtonian mechanics', 'force dynamics'],
                'supporting_notes' => 'Focus on Newton first and second laws',
            ],
            'order_index' => 0,
        ]);

        $biologyTopic = Topic::create([
            'document_id' => $document->id,
            'name' => 'Cellular Respiration',
            'description' => 'Understand stages of respiration',
            'metadata' => [
                'recommended_question_count' => 5,
                'cognitive_emphasis' => 'understand',
                'key_concepts' => ['cellular respiration', 'glycolysis'],
                'supporting_notes' => 'Highlight aerobic respiration pathways',
            ],
            'order_index' => 1,
        ]);

        $job = new ProcessDocumentJob($document->id);

        $reflection = new \ReflectionClass(ProcessDocumentJob::class);
        $method = $reflection->getMethod('syncTableOfSpecificationFromTopics');
        $method->setAccessible(true);

        $tos = $method->invoke($job, $document->fresh(), collect([$mechanicsTopic, $biologyTopic]));

        $this->assertNotNull($tos);
        $this->assertEquals(2, $tos->tosItems()->count());

        $mechanicsTosItem = $tos->tosItems()->where('topic_id', $mechanicsTopic->id)->first();
        $this->assertNotNull($mechanicsTosItem);
        $this->assertEquals($mechanicsOutcome->id, $mechanicsTosItem->learning_outcome_id);

        $biologyTosItem = $tos->tosItems()->where('topic_id', $biologyTopic->id)->first();
        $this->assertNotNull($biologyTosItem);
        $this->assertEquals($respirationOutcome->id, $biologyTosItem->learning_outcome_id);

        $this->assertSame(
            $tos->tosItems()->count(),
            $tos->tosItems()->whereNotNull('learning_outcome_id')->count()
        );
    }

    public function test_sync_table_of_specification_requires_learning_outcomes(): void
    {
        $this->expectException(RuntimeException::class);

        $user = User::factory()->create();

        $course = Course::create([
            'user_id' => $user->id,
            'course_code' => 'GEN101',
            'course_title' => 'General Studies',
            'description' => 'General course without defined outcomes',
            'workflow_stage' => Course::WORKFLOW_STAGE_OBTL_UPLOADED,
        ]);

        ObtlDocument::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'title' => 'OBTL without outcomes',
            'file_path' => '/tmp/generic.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'uploaded_at' => now(),
            'processing_status' => ObtlDocument::PROCESSING_COMPLETED,
            'processed_at' => now(),
        ]);

        $document = Document::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'title' => 'Generic Material',
            'file_path' => '/tmp/generic-doc.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'content_summary' => 'Generic content',
            'uploaded_at' => now(),
            'processing_status' => Document::PROCESSING_PENDING,
        ]);

        $topic = Topic::create([
            'document_id' => $document->id,
            'name' => 'Generic Topic',
            'metadata' => [
                'recommended_question_count' => 4,
                'cognitive_emphasis' => 'remember',
                'key_concepts' => ['generic'],
            ],
            'order_index' => 0,
        ]);

        $job = new ProcessDocumentJob($document->id);

        $reflection = new \ReflectionClass(ProcessDocumentJob::class);
        $method = $reflection->getMethod('syncTableOfSpecificationFromTopics');
        $method->setAccessible(true);

        $method->invoke($job, $document->fresh(), collect([$topic]));
    }
}
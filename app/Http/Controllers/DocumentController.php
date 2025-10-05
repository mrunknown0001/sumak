<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Document;
use App\Jobs\ProcessDocumentJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    /**
     * Upload and process a lecture document
     */
    public function store(Request $request, Course $course): JsonResponse
    {
        $this->authorize('update', $course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'lecture_file' => 'required|file|mimes:pdf,docx|max:20480', // 20MB max
            'lecture_number' => 'nullable|string|max:50',
            'hours_taught' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $file = $request->file('lecture_file');
            $path = $file->store('documents', 'private');

            $document = Document::create([
                'course_id' => $course->id,
                'user_id' => auth()->id(),
                'title' => $validated['title'],
                'file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'uploaded_at' => now(),
            ]);

            // Dispatch job to process document (extract content, generate ToS, create questions)
            ProcessDocumentJob::dispatch($document->id, [
                'lecture_number' => $validated['lecture_number'] ?? null,
                'hours_taught' => $validated['hours_taught'] ?? null,
                'has_obtl' => $course->hasObtl(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Document uploaded successfully and processing started',
                'data' => [
                    'document_id' => $document->id,
                    'title' => $document->title,
                    'status' => 'processing',
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($path)) {
                Storage::disk('private')->delete($path);
            }

            return response()->json([
                'message' => 'Failed to upload document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get document details
     */
    public function show(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $document->load([
            'topics.subtopics',
            'tableOfSpecification.tosItems.subtopic',
            'course'
        ]);

        return response()->json([
            'data' => [
                'id' => $document->id,
                'title' => $document->title,
                'file_size' => $document->formatted_file_size,
                'uploaded_at' => $document->uploaded_at,
                'has_tos' => $document->hasTos(),
                'course' => [
                    'id' => $document->course->id,
                    'code' => $document->course->course_code,
                    'title' => $document->course->course_title,
                ],
                'topics' => $document->topics->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'subtopics' => $t->subtopics->map(fn($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                        'items_count' => $s->items()->count(),
                    ]),
                ]),
                'table_of_specification' => $document->tableOfSpecification ? [
                    'id' => $document->tableOfSpecification->id,
                    'total_items' => $document->tableOfSpecification->total_items,
                    'cognitive_distribution' => $document->tableOfSpecification->cognitive_distribution_summary,
                    'items' => $document->tableOfSpecification->tosItems->map(fn($i) => [
                        'subtopic' => $i->subtopic->name,
                        'cognitive_level' => $i->cognitive_level,
                        'num_items' => $i->num_items,
                        'weight_percentage' => $i->weight_percentage,
                        'is_complete' => $i->isComplete(),
                    ]),
                ] : null,
            ],
        ]);
    }

    /**
     * Get document processing status
     */
    public function status(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $hasTopics = $document->topics()->exists();
        $hasTos = $document->hasTos();
        
        $itemsGenerated = 0;
        $totalItemsNeeded = 0;

        if ($hasTos) {
            $tosItems = $document->tableOfSpecification->tosItems;
            $totalItemsNeeded = $tosItems->sum('num_items');
            
            foreach ($tosItems as $tosItem) {
                $itemsGenerated += $tosItem->items()->count();
            }
        }

        $status = 'pending';
        if (!$hasTopics) {
            $status = 'analyzing_content';
        } elseif (!$hasTos) {
            $status = 'generating_tos';
        } elseif ($itemsGenerated < $totalItemsNeeded) {
            $status = 'generating_questions';
        } else {
            $status = 'completed';
        }

        return response()->json([
            'data' => [
                'document_id' => $document->id,
                'status' => $status,
                'progress' => [
                    'content_analyzed' => $hasTopics,
                    'tos_generated' => $hasTos,
                    'questions_generated' => $totalItemsNeeded > 0 
                        ? round(($itemsGenerated / $totalItemsNeeded) * 100, 2) 
                        : 0,
                    'items_generated' => $itemsGenerated,
                    'total_items_needed' => $totalItemsNeeded,
                ],
            ],
        ]);
    }

    /**
     * Delete document
     */
    public function destroy(Document $document): JsonResponse
    {
        $this->authorize('delete', $document);

        // Delete file from storage
        Storage::disk('private')->delete($document->file_path);

        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully',
        ]);
    }

    /**
     * Download document
     */
    public function download(Document $document)
    {
        $this->authorize('view', $document);

        if (!Storage::disk('private')->exists($document->file_path)) {
            return response()->json([
                'message' => 'File not found',
            ], 404);
        }

        return Storage::disk('private')->download(
            $document->file_path,
            $document->title . '.' . $document->file_type
        );
    }
}
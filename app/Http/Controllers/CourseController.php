<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\ObtlDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * Get all courses for authenticated user
     */
    public function index(): JsonResponse
    {
        $courses = Course::where('user_id', auth()->id())
            ->with(['obtlDocument', 'documents'])
            ->withCount('documents')
            ->latest()
            ->get();

        return response()->json([
            'data' => $courses->map(fn($course) => [
                'id' => $course->id,
                'course_code' => $course->course_code,
                'course_title' => $course->course_title,
                'description' => $course->description,
                'has_obtl' => $course->hasObtl(),
                'documents_count' => $course->documents_count,
                'created_at' => $course->created_at,
            ]),
        ]);
    }

    /**
     * Store a new course
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_code' => 'required|string|max:50',
            'course_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'obtl_file' => 'nullable|file|mimes:pdf|max:10240', // 10MB max
        ]);

        DB::beginTransaction();
        
        try {
            $course = Course::create([
                'user_id' => auth()->id(),
                'course_code' => $validated['course_code'],
                'course_title' => $validated['course_title'],
                'description' => $validated['description'] ?? null,
            ]);

            // Handle OBTL file upload if provided
            if ($request->hasFile('obtl_file')) {
                $file = $request->file('obtl_file');
                $path = $file->store('obtl_documents', 'private');

                ObtlDocument::create([
                    'course_id' => $course->id,
                    'user_id' => auth()->id(),
                    'title' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                    'uploaded_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Course created successfully',
                'data' => $course->load('obtlDocument'),
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to create course',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get course details
     */
    public function show(Course $course): JsonResponse
    {
        $this->authorize('view', $course);

        $course->load([
            'obtlDocument.learningOutcomes.subOutcomes',
            'documents.topics',
            'documents.tableOfSpecification.tosItems',
        ]);

        return response()->json([
            'data' => [
                'id' => $course->id,
                'course_code' => $course->course_code,
                'course_title' => $course->course_title,
                'description' => $course->description,
                'obtl_document' => $course->obtlDocument,
                'documents' => $course->documents,
                'total_topics' => $course->documents->sum('total_topics'),
                'total_quiz_attempts' => $course->total_quiz_attempts,
                'created_at' => $course->created_at,
            ],
        ]);
    }

    /**
     * Update course
     */
    public function update(Request $request, Course $course): JsonResponse
    {
        $this->authorize('update', $course);

        $validated = $request->validate([
            'course_code' => 'sometimes|string|max:50',
            'course_title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $course->update($validated);

        return response()->json([
            'message' => 'Course updated successfully',
            'data' => $course,
        ]);
    }

    /**
     * Delete course
     */
    public function destroy(Course $course): JsonResponse
    {
        $this->authorize('delete', $course);

        // Delete associated files
        if ($course->obtlDocument) {
            Storage::disk('private')->delete($course->obtlDocument->file_path);
        }

        foreach ($course->documents as $document) {
            Storage::disk('private')->delete($document->file_path);
        }

        $course->delete();

        return response()->json([
            'message' => 'Course deleted successfully',
        ]);
    }
}
<?php

namespace App\Http\Controllers\OpenAi;

use App\Http\Controllers\Controller;
use App\Services\OpenAiService;
use App\Models\Material;
use App\Models\AiAnalysis;
use App\Jobs\ProcessMaterialJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContentAnalysisController extends Controller
{
    public function __construct(private OpenAiService $openAiService) {}

    /**
     * Analyze uploaded content
     */
    public function analyze(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'obtl_context' => 'nullable|string|max:10000',
        ]);

        $material = Material::findOrFail($validated['material_id']);

        // Check if user has access to this material
        $this->authorize('view', $material);

        // Dispatch job for async processing
        ProcessMaterialJob::dispatch($material->id);

        return response()->json([
            'message' => 'Content analysis started',
            'material_id' => $material->id,
            'status' => 'processing',
        ], 202);
    }

    /**
     * Get analysis results
     */
    public function show(Material $material): JsonResponse
    {
        $this->authorize('view', $material);

        $analysis = $material->aiAnalysis;

        if (!$analysis) {
            return response()->json([
                'message' => 'Analysis not found or still processing',
                'status' => $material->processing_status,
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $analysis->id,
                'material_id' => $material->id,
                'key_concepts' => json_decode($analysis->key_concepts),
                'extracted_content' => json_decode($analysis->extracted_content),
                'difficulty_assessment' => $analysis->difficulty_assessment,
                'created_at' => $analysis->created_at,
            ],
        ]);
    }
}
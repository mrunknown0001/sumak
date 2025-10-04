<?php

namespace App\Http\Controllers\OpenAi;

use App\Http\Controllers\Controller;
use App\Models\TableOfSpecification;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TosGenerationController extends Controller
{
    /**
     * Generate Table of Specification
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'total_items' => 'nullable|integer|min:10|max:50',
        ]);

        $material = Material::findOrFail($validated['material_id']);
        $this->authorize('view', $material);

        // Check if ToS already exists
        if ($material->tableOfSpecification) {
            return response()->json([
                'message' => 'Table of Specification already exists for this material',
                'data' => $material->tableOfSpecification,
            ], 200);
        }

        // Processing handled by ProcessMaterialJob
        return response()->json([
            'message' => 'ToS generation in progress',
            'material_id' => $material->id,
        ], 202);
    }

    /**
     * Get ToS by ID
     */
    public function show(TableOfSpecification $tos): JsonResponse
    {
        $this->authorize('view', $tos->material);

        return response()->json([
            'data' => [
                'id' => $tos->id,
                'material_id' => $tos->material_id,
                'total_items' => $tos->total_items,
                'lots_percentage' => $tos->lots_percentage,
                'cognitive_distribution' => json_decode($tos->cognitive_level_distribution),
                'tos_items' => $tos->tosItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'subtopic' => $item->subtopic,
                        'cognitive_level' => $item->cognitive_level,
                        'bloom_category' => $item->bloom_category,
                        'num_items' => $item->num_items,
                        'weight_percentage' => $item->weight_percentage,
                    ];
                }),
                'created_at' => $tos->created_at,
            ],
        ]);
    }

    /**
     * Get ToS by material
     */
    public function getByMaterial(Material $material): JsonResponse
    {
        $this->authorize('view', $material);

        $tos = $material->tableOfSpecification;

        if (!$tos) {
            return response()->json([
                'message' => 'ToS not found for this material',
            ], 404);
        }

        return $this->show($tos);
    }
}
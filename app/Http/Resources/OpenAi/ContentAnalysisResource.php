<?php

namespace App\Http\Resources\OpenAi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentAnalysisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'material_id' => $this->material_id,
            'material' => [
                'id' => $this->material->id,
                'title' => $this->material->title,
                'file_type' => $this->material->file_type,
            ],
            'analysis' => [
                'key_concepts' => json_decode($this->key_concepts),
                'extracted_content' => json_decode($this->extracted_content),
                'difficulty_assessment' => $this->difficulty_assessment,
            ],
            'metadata' => [
                'api_version' => $this->api_version,
                'processing_time' => $this->processing_time,
            ],
            'timestamps' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }
}
<?php

namespace App\Http\Resources\OpenAi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'material_id' => $this->material_id,
            'specifications' => [
                'total_items' => $this->total_items,
                'lots_percentage' => round($this->lots_percentage, 2),
                'cognitive_distribution' => json_decode($this->cognitive_level_distribution),
            ],
            'items' => TosItemResource::collection($this->whenLoaded('tosItems')),
            'timestamps' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }
}
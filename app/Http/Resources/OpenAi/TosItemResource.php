<?php

namespace App\Http\Resources\OpenAi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TosItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subtopic' => $this->subtopic,
            'cognitive_level' => $this->cognitive_level,
            'bloom_category' => $this->bloom_category,
            'allocation' => [
                'num_items' => $this->num_items,
                'weight_percentage' => round($this->weight_percentage, 2),
            ],
            'sample_indicators' => json_decode($this->sample_indicators),
            'learning_outcome' => [
                'id' => $this->learningOutcome?->id,
                'statement' => $this->learningOutcome?->outcome_statement,
            ],
        ];
    }
}
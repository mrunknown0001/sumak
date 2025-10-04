<?php

namespace App\Http\Resources\OpenAi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_text' => $this->question_text,
            'order' => $this->order,
            'metadata' => [
                'cognitive_level' => $this->cognitive_level,
                'subtopic' => $this->subtopic,
                'difficulty' => round($this->difficulty, 2),
            ],
            'options' => QuestionOptionResource::collection($this->whenLoaded('options')),
            'explanation' => $this->when(
                $request->user()?->can('view-explanations'),
                $this->explanation
            ),
            'regeneration_info' => [
                'is_regenerated' => $this->is_regenerated ?? false,
                'can_regenerate' => $this->canRegenerate(),
                'regeneration_count' => $this->regenerations_count ?? 0,
                'remaining_regenerations' => max(0, 3 - ($this->regenerations_count ?? 0)),
            ],
        ];
    }

    /**
     * Check if question can be regenerated
     */
    private function canRegenerate(): bool
    {
        return ($this->regenerations_count ?? 0) < 3;
    }
}
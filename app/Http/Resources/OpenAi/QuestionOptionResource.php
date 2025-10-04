<?php

namespace App\Http\Resources\OpenAi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Hide correct answer unless user has permission or quiz is completed
        $showCorrectAnswer = $request->user()?->can('view-answers') || 
                            $this->resource->quizQuestion?->quiz?->isCompleted();

        return [
            'id' => $this->id,
            'option_letter' => $this->option_letter,
            'option_text' => $this->option_text,
            'is_correct' => $this->when($showCorrectAnswer, $this->is_correct),
            'rationale' => $this->when($showCorrectAnswer, $this->rationale),
        ];
    }
}
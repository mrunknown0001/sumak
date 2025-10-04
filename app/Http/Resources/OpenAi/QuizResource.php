<?php

namespace App\Http\Resources\OpenAi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'material_id' => $this->material_id,
            'course_id' => $this->course_id,
            'settings' => [
                'total_questions' => $this->total_questions,
                'time_per_question' => $this->time_per_question,
                'total_time' => $this->total_questions * $this->time_per_question,
                'difficulty_level' => $this->difficulty_level,
            ],
            'status' => $this->status,
            'questions' => QuizQuestionResource::collection($this->whenLoaded('questions')),
            'statistics' => [
                'total_attempts' => $this->attempts_count ?? 0,
                'average_score' => $this->average_score ?? null,
            ],
            'timestamps' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
                'published_at' => $this->published_at?->toIso8601String(),
            ],
        ];
    }
}
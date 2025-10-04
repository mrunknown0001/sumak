<?php

namespace App\Http\Resources\OpenAi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_attempt_id' => $this->quiz_attempt_id,
            'feedback' => [
                'overall' => $this->feedback_text,
                'motivational_message' => $this->motivational_message,
            ],
            'analysis' => [
                'strengths' => json_decode($this->strengths),
                'areas_for_improvement' => json_decode($this->weaknesses),
            ],
            'guidance' => [
                'recommendations' => json_decode($this->recommendations),
                'next_steps' => json_decode($this->next_steps),
            ],
            'quiz_attempt' => [
                'score' => $this->quizAttempt->score,
                'total_questions' => $this->quizAttempt->quiz->total_questions,
                'percentage' => round(
                    ($this->quizAttempt->score / $this->quizAttempt->quiz->total_questions) * 100, 
                    2
                ),
                'completed_at' => $this->quizAttempt->end_time?->toIso8601String(),
            ],
            'timestamps' => [
                'generated_at' => $this->generated_at?->toIso8601String(),
            ],
        ];
    }
}
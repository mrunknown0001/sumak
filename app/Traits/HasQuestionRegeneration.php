<?php

namespace App\Traits;

use App\Models\QuestionRegeneration;

trait HasQuestionRegeneration
{
    /**
     * Check if question can be regenerated
     */
    public function canRegenerate(): bool
    {
        return $this->getRegenerationCount() < 3;
    }

    /**
     * Get regeneration count
     */
    public function getRegenerationCount(): int
    {
        return QuestionRegeneration::where('original_question_id', $this->id)
            ->count();
    }

    /**
     * Get remaining regenerations
     */
    public function getRemainingRegenerations(): int
    {
        return max(0, 3 - $this->getRegenerationCount());
    }

    /**
     * Get regeneration history
     */
    public function getRegenerationHistory()
    {
        return QuestionRegeneration::where('original_question_id', $this->id)
            ->with('regeneratedQuestion')
            ->orderBy('regeneration_count')
            ->get();
    }

    /**
     * Check if this is a regenerated question
     */
    public function isRegenerated(): bool
    {
        return QuestionRegeneration::where('regenerated_question_id', $this->id)
            ->exists();
    }

    /**
     * Get original question if this is a regeneration
     */
    public function getOriginalQuestion()
    {
        $regeneration = QuestionRegeneration::where('regenerated_question_id', $this->id)
            ->with('originalQuestion')
            ->first();

        return $regeneration?->originalQuestion;
    }
}
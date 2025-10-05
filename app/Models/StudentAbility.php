<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAbility extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'subtopic_id',
        'theta',
        'attempts_count',
        'last_updated',
    ];

    protected $casts = [
        'theta' => 'float',
        'last_updated' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subtopic
     */
    public function subtopic(): BelongsTo
    {
        return $this->belongsTo(Subtopic::class);
    }

    /**
     * Get proficiency level as text
     */
    public function getProficiencyLevelAttribute(): string
    {
        if ($this->theta < -1) {
            return 'Beginner';
        } elseif ($this->theta < 0) {
            return 'Developing';
        } elseif ($this->theta < 1) {
            return 'Competent';
        } elseif ($this->theta < 2) {
            return 'Proficient';
        } else {
            return 'Advanced';
        }
    }

    /**
     * Update theta based on IRT (1PL model)
     */
    public function updateTheta(float $newTheta): void
    {
        $this->update([
            'theta' => $newTheta,
            'attempts_count' => $this->attempts_count + 1,
            'last_updated' => now(),
        ]);
    }
}

// Feedback Model
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'quiz_attempt_id',
        'subtopic_id',
        'user_id',
        'feedback_text',
        'strengths',
        'weaknesses',
        'recommendations',
        'next_steps',
        'motivational_message',
        'generated_at',
    ];

    protected $casts = [
        'strengths' => 'array',
        'weaknesses' => 'array',
        'recommendations' => 'array',
        'next_steps' => 'array',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the quiz attempt
     */
    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    /**
     * Get the subtopic
     */
    public function subtopic(): BelongsTo
    {
        return $this->belongsTo(Subtopic::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted feedback summary
     */
    public function getSummaryAttribute(): array
    {
        return [
            'overall' => $this->feedback_text,
            'strengths_count' => count($this->strengths ?? []),
            'areas_for_improvement_count' => count($this->weaknesses ?? []),
            'recommendations_count' => count($this->recommendations ?? []),
            'next_steps_count' => count($this->next_steps ?? []),
        ];
    }
}
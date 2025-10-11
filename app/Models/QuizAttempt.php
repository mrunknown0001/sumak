<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subtopic_id',
        'attempt_number',
        'is_adaptive',
        'total_questions',
        'correct_answers',
        'score_percentage',
        'started_at',
        'completed_at',
        'time_spent_seconds',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
     * Get all responses
     */
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    /**
     * Get feedback
     */
    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }

    /**
     * Check if completed
     */
    public function isCompleted(): bool
    {
        return !is_null($this->completed_at);
    }

    /**
     * Get pass status (70% threshold)
     */
    public function isPassed(): bool
    {
        return $this->score_percentage >= 70;
    }

    /**
     * Get time spent in minutes
     */
    public function getTimeSpentMinutesAttribute(): float
    {
        return round($this->time_spent_seconds / 60, 2);
    }

    /**
     * Get average time per question
     */
    public function getAverageTimePerQuestionAttribute(): float
    {
        if ($this->total_questions === 0) {
            return 0;
        }
        
        return round($this->time_spent_seconds / $this->total_questions, 2);
    }

    /**
     * Complete the quiz attempt
     */
    public function complete(): void
    {
        $this->update([
            'completed_at' => now(),
            'time_spent_seconds' => now()->diffInSeconds($this->started_at),
        ]);
    }
}
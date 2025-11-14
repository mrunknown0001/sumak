<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    public $timestamps = false;

    protected $fillable = [
        'quiz_attempt_id',
        'topic_id',
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

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function subtopic(): BelongsTo
    {
        return $this->belongsTo(Subtopic::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
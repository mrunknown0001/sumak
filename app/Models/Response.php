<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Response extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'quiz_attempt_id',
        'item_id',
        'user_id',
        'user_answer',
        'is_correct',
        'time_taken_seconds',
        'response_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'response_at' => 'datetime',
    ];

    /**
     * Get the quiz attempt
     */
    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    /**
     * Get the item
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemBank::class, 'item_id');
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if response was fast (< 10 seconds)
     */
    public function isFastResponse(): bool
    {
        return $this->time_taken_seconds < 10;
    }

    /**
     * Check if response was slow (> 50 seconds)
     */
    public function isSlowResponse(): bool
    {
        return $this->time_taken_seconds > 50;
    }

    /**
     * Get user answer text
     */
    public function getUserAnswerTextAttribute(): ?string
    {
        $options = $this->item->options;
        
        foreach ($options as $option) {
            if ($option['option_letter'] === $this->user_answer) {
                return $option['option_text'];
            }
        }
        
        return null;
    }
}
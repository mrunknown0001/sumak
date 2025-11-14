<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemBank extends Model
{
    use HasFactory;

    protected $table = 'item_bank';
    
    public $timestamps = false;

    protected $fillable = [
        'tos_item_id',
        'topic_id',
        'learning_outcome_id',
        'question',
        'options',
        'correct_answer',
        'explanation',
        'cognitive_level',
        'difficulty_b',
        'time_estimate_seconds',
        'created_at',
    ];

    protected $casts = [
        'options' => 'array',
        'difficulty_b' => 'float',
        'created_at' => 'datetime',
    ];

    /**
     * Get the ToS item
     */
    public function tosItem(): BelongsTo
    {
        return $this->belongsTo(TosItem::class);
    }

    /**
     * Get the topic
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get the learning outcome
     */
    public function learningOutcome(): BelongsTo
    {
        return $this->belongsTo(LearningOutcome::class);
    }

    /**
     * Get all responses
     */
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class, 'item_id');
    }

    /**
     * Get all regenerations where this is the original
     */
    public function regenerationsAsOriginal(): HasMany
    {
        return $this->hasMany(QuizRegeneration::class, 'original_item_id');
    }

    /**
     * Get all regenerations where this is regenerated
     */
    public function regenerationsAsRegenerated(): HasMany
    {
        return $this->hasMany(QuizRegeneration::class, 'regenerated_item_id');
    }

    /**
     * Check if item can be regenerated
     */
    public function canRegenerate(): bool
    {
        return $this->regenerationsAsOriginal()->count() < 3;
    }

    /**
     * Get regeneration count
     */
    public function getRegenerationCountAttribute(): int
    {
        return $this->regenerationsAsOriginal()->count();
    }

    /**
     * Get difficulty level as text
     */
    public function getDifficultyLevelAttribute(): string
    {
        if ($this->difficulty_b < -1) {
            return 'Very Easy';
        } elseif ($this->difficulty_b < 0) {
            return 'Easy';
        } elseif ($this->difficulty_b < 1) {
            return 'Medium';
        } elseif ($this->difficulty_b < 2) {
            return 'Hard';
        } else {
            return 'Very Hard';
        }
    }

    /**
     * Get correct option text
     */
    public function getCorrectOptionTextAttribute(): ?string
    {
        $options = $this->options;
        
        foreach ($options as $option) {
            if ($option['option_letter'] === $this->correct_answer) {
                return $option['option_text'];
            }
        }
        
        return null;
    }

    /**
     * Check if item is LOTS
     */
    public function isLots(): bool
    {
        return in_array(strtolower($this->cognitive_level), ['remember', 'understand']);
    }

    /**
     * Get accuracy rate
     */
    public function getAccuracyRateAttribute(): float
    {
        $totalResponses = $this->responses()->count();
        
        if ($totalResponses === 0) {
            return 0;
        }
        
        $correctResponses = $this->responses()->where('is_correct', true)->count();
        
        return ($correctResponses / $totalResponses) * 100;
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\TosItem;

class Subtopic extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'name',
        'order_index',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the topic that owns the subtopic
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get all ToS items for this subtopic
     */
    public function tosItems(): HasMany
    {
        return $this->hasMany(TosItem::class);
    }

    /**
     * Get all items in the item bank
     */
    public function items(): HasMany
    {
        return $this->hasMany(ItemBank::class);
    }

    /**
     * Get all quiz attempts
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get all student abilities
     */
    public function studentAbilities(): HasMany
    {
        return $this->hasMany(StudentAbility::class);
    }

    /**
     * Get all feedback
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * Get all quiz regenerations
     */
    public function regenerations(): HasMany
    {
        return $this->hasMany(QuizRegeneration::class);
    }

    /**
     * Get student ability for a specific user
     */
    public function getStudentAbility(int $userId): ?StudentAbility
    {
        return $this->studentAbilities()
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get average theta for all students
     */
    public function getAverageThetaAttribute(): float
    {
        return $this->studentAbilities()->avg('theta') ?? 0;
    }

    /**
     * Check if user has completed all initial quizzes for this subtopic
     */
    public function hasCompletedAllInitialQuizzes(int $userId): bool
    {
        $completedCount = QuizAttempt::where('user_id', $userId)
            ->where('subtopic_id', $this->id)
            ->where('is_adaptive', false)
            ->whereNotNull('completed_at')
            ->count();
        
        return $completedCount > 0;
    }
}
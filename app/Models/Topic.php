<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'name',
        'description',
        'metadata',
        'order_index',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the document that owns the topic
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get all quiz attempts
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemBank::class);
    }

    /**
     * Get all ToS items for this topic
     */
    public function tosItems(): HasMany
    {
        return $this->hasMany(TosItem::class);
    }


    public function hasCompletedAllInitialQuizzes(int $userId): bool
    {
        $completedCount = QuizAttempt::where('user_id', $userId)
            ->where('topic_id', $this->id)
            ->where('is_adaptive', false)
            ->whereNotNull('completed_at')
            ->count();
        
        return $completedCount > 0;
    }
}
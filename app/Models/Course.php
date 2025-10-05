<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'course_code',
        'course_title',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the course
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the OBTL document for the course
     */
    public function obtlDocument(): HasOne
    {
        return $this->hasOne(ObtlDocument::class);
    }

    /**
     * Get all documents for the course
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Check if course has OBTL document
     */
    public function hasObtl(): bool
    {
        return $this->obtlDocument()->exists();
    }

    /**
     * Get total quiz attempts for this course
     */
    public function getTotalQuizAttemptsAttribute(): int
    {
        return $this->documents()
            ->with('topics.subtopics.quizAttempts')
            ->get()
            ->flatMap(fn($doc) => $doc->topics)
            ->flatMap(fn($topic) => $topic->subtopics)
            ->flatMap(fn($subtopic) => $subtopic->quizAttempts)
            ->count();
    }
}
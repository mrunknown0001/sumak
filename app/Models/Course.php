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

    public const WORKFLOW_STAGE_DRAFT = 'draft';
    public const WORKFLOW_STAGE_OBTL_UPLOADED = 'obtl_uploaded';
    public const WORKFLOW_STAGE_MATERIALS_UPLOADED = 'materials_uploaded';

    protected $fillable = [
        'user_id',
        'course_code',
        'course_title',
        'description',
        'workflow_stage',
        'obtl_uploaded_at',
        'materials_uploaded_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'obtl_uploaded_at' => 'datetime',
        'materials_uploaded_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            // Ensure a user_id is set, default to the authenticated user if available
            if (empty($course->user_id) && auth()->check()) {
                $course->user_id = auth()->id();
            }
        });

        static::deleting(function ($course) {
            // Delete related OBTL document
            $course->obtlDocument()->delete();

            // Delete related documents (which will cascade to topics, subtopics, and quiz attempts)
            foreach ($course->documents as $document) {
                $document->delete();
            }
        });
    }



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
     * Get all enrollments for this course
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    /**
     * Get enrolled students
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'course_enrollments')
            ->withTimestamps()
            ->withPivot('enrolled_at');
    }

    /**
     * Check if course has OBTL document
     */
    public function hasObtl(): bool
    {
        return $this->obtlDocument()->exists();
    }

    /**
     * Check if user is enrolled in this course
     */
    public function isEnrolledBy(int $userId): bool
    {
        return $this->enrollments()->where('user_id', $userId)->exists();
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
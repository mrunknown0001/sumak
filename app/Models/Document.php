<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    use HasFactory;

    public const PROCESSING_PENDING = 'pending';
    public const PROCESSING_IN_PROGRESS = 'processing';
    public const PROCESSING_COMPLETED = 'completed';
    public const PROCESSING_FAILED = 'failed';

    protected $fillable = [
        'course_id',
        'user_id',
        'title',
        'file_path',
        'file_type',
        'file_size',
        'content_summary',
        'uploaded_at',
        'processing_status',
        'processed_at',
        'processing_error',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the course that owns the document
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the user that uploaded the document
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all topics
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('order_index');
    }

    /**
     * Get the table of specification
     */
    public function tableOfSpecification(): HasOne
    {
        return $this->hasOne(TableOfSpecification::class);
    }

    /**
     * Check if ToS has been generated
     */
    public function hasTos(): bool
    {
        return $this->tableOfSpecification()->exists();
    }

    /**
     * Get total number of subtopics
     */
    public function getTotalSubtopicsAttribute(): int
    {
        return $this->topics()
            ->with('subtopics')
            ->get()
            ->flatMap(fn($topic) => $topic->subtopics)
            ->count();
    }

    /**
     * Get file size in human readable format
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
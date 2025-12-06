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
        'topic_id',
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
        'correlation_score',
        'correlation_threshold',
        'correlation_metadata',
        'correlation_evaluated_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'correlation_score' => 'float',
        'correlation_threshold' => 'float',
        'correlation_metadata' => 'array',
        'correlation_evaluated_at' => 'datetime',
    ];

    /**
     * Get the topic that owns the document
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get all topics for this document
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

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
     * Get the table of specification for this document
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
        return $this->topic && $this->topic->tableOfSpecification()->exists();
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
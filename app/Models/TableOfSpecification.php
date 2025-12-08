<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TableOfSpecification extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'topic_id',
        'document_id',
        'term',
        'total_items',
        'lots_percentage',
        'cognitive_level_distribution',
        'assessment_focus',
        'generated_at',
    ];

    protected $casts = [
        'cognitive_level_distribution' => 'array',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the course that owns the ToS
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the topic that owns the ToS
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Get the document that owns the ToS
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get all ToS items
     */
    public function tosItems(): HasMany
    {
        return $this->hasMany(TosItem::class, 'tos_id');
    }

    /**
     * Get cognitive distribution summary
     */
    public function getCognitiveDistributionSummaryAttribute(): array
    {
        $distribution = $this->cognitive_level_distribution ?? [];
        
        return [
            'remember' => $distribution['remember'] ?? 0,
            'understand' => $distribution['understand'] ?? 0,
            'apply' => $distribution['apply'] ?? 0,
        ];
    }
}

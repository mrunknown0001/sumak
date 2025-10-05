<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TosItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tos_id',
        'subtopic_id',
        'learning_outcome_id',
        'cognitive_level',
        'bloom_category',
        'num_items',
        'weight_percentage',
        'sample_indicators',
    ];

    protected $casts = [
        'sample_indicators' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the ToS that owns the item
     */
    public function tableOfSpecification(): BelongsTo
    {
        return $this->belongsTo(TableOfSpecification::class, 'tos_id');
    }

    /**
     * Get the subtopic
     */
    public function subtopic(): BelongsTo
    {
        return $this->belongsTo(Subtopic::class);
    }

    /**
     * Get the learning outcome
     */
    public function learningOutcome(): BelongsTo
    {
        return $this->belongsTo(LearningOutcome::class);
    }

    /**
     * Get all items in the item bank
     */
    public function items(): HasMany
    {
        return $this->hasMany(ItemBank::class);
    }

    /**
     * Check if this ToS item is LOTS
     */
    public function isLots(): bool
    {
        return in_array(strtolower($this->cognitive_level), ['remember', 'understand']);
    }

    /**
     * Get completion status
     */
    public function isComplete(): bool
    {
        return $this->items()->count() >= $this->num_items;
    }

    /**
     * Get remaining items to generate
     */
    public function getRemainingItemsAttribute(): int
    {
        return max(0, $this->num_items - $this->items()->count());
    }
}
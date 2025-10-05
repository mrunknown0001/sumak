<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningOutcome extends Model
{
    use HasFactory;

    protected $fillable = [
        'obtl_document_id',
        'outcome_code',
        'description',
        'bloom_level',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the OBTL document that owns the learning outcome
     */
    public function obtlDocument(): BelongsTo
    {
        return $this->belongsTo(ObtlDocument::class);
    }

    /**
     * Get all sub-outcomes
     */
    public function subOutcomes(): HasMany
    {
        return $this->hasMany(SubOutcome::class);
    }

    /**
     * Get all ToS items
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
     * Check if outcome is LOTS
     */
    public function isLots(): bool
    {
        return in_array(strtolower($this->bloom_level), ['remember', 'understand']);
    }
}
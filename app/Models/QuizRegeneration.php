<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizRegeneration extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'original_item_id',
        'regenerated_item_id',
        'topic_id',
        'regeneration_count',
        'maintains_equivalence',
        'regenerated_at',
    ];

    protected $casts = [
        'maintains_equivalence' => 'boolean',
        'regenerated_at' => 'datetime',
    ];

    /**
     * Get the original item
     */
    public function originalItem(): BelongsTo
    {
        return $this->belongsTo(ItemBank::class, 'original_item_id');
    }

    /**
     * Get the regenerated item
     */
    public function regeneratedItem(): BelongsTo
    {
        return $this->belongsTo(ItemBank::class, 'regenerated_item_id');
    }

    /**
     * Get the topic
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
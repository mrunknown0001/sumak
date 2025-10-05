<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubOutcome extends Model
{
    use HasFactory;

    protected $fillable = [
        'learning_outcome_id',
        'description',
        'bloom_level',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the learning outcome that owns the sub-outcome
     */
    public function learningOutcome(): BelongsTo
    {
        return $this->belongsTo(LearningOutcome::class);
    }

    /**
     * Check if sub-outcome is LOTS
     */
    public function isLots(): bool
    {
        return in_array(strtolower($this->bloom_level), ['remember', 'understand']);
    }
}
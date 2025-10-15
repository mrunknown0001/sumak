<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAbility extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'subtopic_id',
        'theta',
        'attempts_count',
        'last_updated',
    ];

    protected $casts = [
        'theta' => 'float',
        'last_updated' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (StudentAbility $studentAbility): void {
            if (!$studentAbility->last_updated) {
                $studentAbility->last_updated = now();
            }
        });

        static::updating(function (StudentAbility $studentAbility): void {
            $studentAbility->last_updated = now();
        });
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subtopic
     */
    public function subtopic(): BelongsTo
    {
        return $this->belongsTo(Subtopic::class);
    }

    /**
     * Get proficiency level as text
     */
    public function getProficiencyLevelAttribute(): string
    {
        if ($this->theta < -1) {
            return 'Beginner';
        } elseif ($this->theta < 0) {
            return 'Developing';
        } elseif ($this->theta < 1) {
            return 'Competent';
        } elseif ($this->theta < 2) {
            return 'Proficient';
        } else {
            return 'Advanced';
        }
    }

    /**
     * Update theta based on IRT (1PL model)
     */
    public function updateTheta(float $newTheta): void
    {
        $this->update([
            'theta' => $newTheta,
            'attempts_count' => $this->attempts_count + 1,
        ]);
    }
}

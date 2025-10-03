<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatGptApiLog extends Model
{
    /**
     * Disable updated_at timestamp as we only need created_at
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'request_type',
        'model',
        'total_tokens',
        'prompt_tokens',
        'completion_tokens',
        'response_time_ms',
        'estimated_cost',
        'success',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_tokens' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'response_time_ms' => 'float',
        'estimated_cost' => 'decimal:6',
        'success' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user that made the API request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope to get failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope to get requests by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('request_type', $type);
    }

    /**
     * Scope to get requests within date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get formatted cost
     */
    public function getFormattedCostAttribute(): string
    {
        return '$' . number_format($this->estimated_cost, 4);
    }

    /**
     * Get formatted response time
     */
    public function getFormattedResponseTimeAttribute(): string
    {
        if ($this->response_time_ms < 1000) {
            return round($this->response_time_ms) . ' ms';
        }
        return round($this->response_time_ms / 1000, 2) . ' s';
    }
}
<?php

namespace App\Http\Resources\OpenAi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiLogCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'summary' => [
                'total_requests' => $this->collection->count(),
                'successful_requests' => $this->collection->where('success', true)->count(),
                'failed_requests' => $this->collection->where('success', false)->count(),
                'total_tokens' => $this->collection->sum('total_tokens'),
                'total_cost' => round($this->collection->sum('estimated_cost'), 4),
                'average_response_time' => round($this->collection->avg('response_time_ms'), 2),
            ],
        ];
    }
}
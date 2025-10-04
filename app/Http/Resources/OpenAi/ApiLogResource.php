<?php

namespace App\Http\Resources\OpenAi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_info' => [
                'type' => $this->request_type,
                'model' => $this->model,
                'user_id' => $this->user_id,
                'user_name' => $this->user?->name,
            ],
            'usage' => [
                'total_tokens' => $this->total_tokens,
                'prompt_tokens' => $this->prompt_tokens,
                'completion_tokens' => $this->completion_tokens,
            ],
            'performance' => [
                'response_time_ms' => round($this->response_time_ms, 2),
                'response_time_formatted' => $this->formatted_response_time,
            ],
            'cost' => [
                'estimated' => round($this->estimated_cost, 6),
                'formatted' => $this->formatted_cost,
            ],
            'status' => [
                'success' => $this->success,
                'error_message' => $this->when(!$this->success, $this->error_message),
            ],
            'timestamp' => $this->created_at?->toIso8601String(),
        ];
    }
}
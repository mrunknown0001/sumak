<?php

namespace App\Http\Requests\OpenAi;

use Illuminate\Foundation\Http\FormRequest;

class ApiUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Users can see their own stats, admins can see all
        $requestedUserId = $this->input('user_id');
        
        if ($requestedUserId && $requestedUserId != $this->user()->id) {
            return $this->user()->hasRole('admin');
        }
        
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'request_type' => 'nullable|in:content_analysis,tos_generation,quiz_generation,question_reword,feedback_generation,obtl_parsing',
            'per_page' => 'nullable|integer|min:5|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'End date must be after or equal to start date.',
            'per_page.max' => 'Maximum :max records per page.',
        ];
    }
}
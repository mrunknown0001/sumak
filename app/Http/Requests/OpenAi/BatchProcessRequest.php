<?php

namespace App\Http\Requests\OpenAi;

use Illuminate\Foundation\Http\FormRequest;

class BatchProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('batch-process-materials');
    }

    public function rules(): array
    {
        return [
            'material_ids' => 'required|array|min:1|max:10',
            'material_ids.*' => 'required|exists:materials,id',
            'priority' => 'nullable|in:low,normal,high',
            'notify_on_completion' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'material_ids.max' => 'Maximum 10 materials can be processed at once.',
            'material_ids.*.exists' => 'One or more materials do not exist.',
        ];
    }

    /**
     * Get validated data with defaults
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        return array_merge([
            'priority' => 'normal',
            'notify_on_completion' => true,
        ], $validated);
    }
}
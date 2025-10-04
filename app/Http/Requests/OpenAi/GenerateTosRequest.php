<?php

namespace App\Http\Requests\OpenAi;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('use-openai');
    }

    public function rules(): array
    {
        return [
            'material_id' => 'required|exists:materials,id',
            'learning_outcomes' => 'required|array|min:1',
            'learning_outcomes.*.outcome' => 'required|string',
            'learning_outcomes.*.bloom_level' => 'required|in:remember,understand,apply,analyze,evaluate,create',
            'learning_outcomes.*.category' => 'required|string',
            'total_items' => 'nullable|integer|min:10|max:50',
            'lots_focus' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'learning_outcomes.required' => 'At least one learning outcome is required.',
            'learning_outcomes.*.bloom_level.in' => 'Invalid Bloom taxonomy level.',
            'total_items.min' => 'Minimum :min questions required.',
            'total_items.max' => 'Maximum :max questions allowed.',
        ];
    }
}

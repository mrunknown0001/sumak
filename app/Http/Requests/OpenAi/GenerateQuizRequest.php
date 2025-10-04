<?php

namespace App\Http\Requests\OpenAi;

use Illuminate\Foundation\Http\FormRequest;

class GenerateQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('use-openai');
    }

    public function rules(): array
    {
        return [
            'material_id' => 'required|exists:materials,id',
            'tos_id' => 'required|exists:table_of_specifications,id',
            'question_count' => 'nullable|integer|min:10|max:20',
            'time_per_question' => 'nullable|integer|min:30|max:300',
            'difficulty_level' => 'nullable|in:easy,medium,hard',
            'include_explanations' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'question_count.max' => 'Maximum 20 questions per quiz.',
            'time_per_question.min' => 'Minimum 30 seconds per question.',
            'time_per_question.max' => 'Maximum 5 minutes per question.',
        ];
    }

    /**
     * Get validated data with defaults
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        return array_merge([
            'question_count' => 20,
            'time_per_question' => 60,
            'include_explanations' => true,
        ], $validated);
    }
}
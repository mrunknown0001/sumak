<?php

namespace App\Http\Requests\OpenAi;

use Illuminate\Foundation\Http\FormRequest;

class GenerateFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attempt = $this->route('quizAttempt');
        return $this->user()->id === $attempt->user_id;
    }

    public function rules(): array
    {
        return [
            'quiz_attempt_id' => 'required|exists:quiz_attempts,id',
            'include_recommendations' => 'nullable|boolean',
            'include_next_steps' => 'nullable|boolean',
            'tone' => 'nullable|in:formal,casual,encouraging',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        return array_merge([
            'include_recommendations' => true,
            'include_next_steps' => true,
            'tone' => 'encouraging',
        ], $validated);
    }
}
<?php

namespace App\Http\Requests\OpenAi;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\QuizQuestion;
use App\Models\QuestionRegeneration;

class RegenerateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $question = $this->route('question');
        
        // Check if user owns the quiz
        return $this->user()->id === $question->quiz->material->course->instructor_id;
    }

    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:500',
            'preserve_difficulty' => 'nullable|boolean',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $question = $this->route('question');
            
            // Check regeneration limit
            $count = QuestionRegeneration::where('original_question_id', $question->id)
                ->count();
            
            if ($count >= 3) {
                $validator->errors()->add(
                    'regeneration_limit',
                    'Maximum regeneration limit (3) has been reached for this question.'
                );
            }
        });
    }
}
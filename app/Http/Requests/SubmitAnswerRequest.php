<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attempt = $this->route('attempt');
        return $this->user()->id === $attempt->user_id;
    }

    public function rules(): array
    {
        return [
            'item_id' => 'required|exists:item_bank,id',
            'answer' => 'required|string|size:1|in:A,B,C,D',
            'time_taken' => 'required|integer|min:0|max:120',
        ];
    }

    public function messages(): array
    {
        return [
            'answer.in' => 'Answer must be A, B, C, or D',
            'time_taken.max' => 'Time taken cannot exceed 120 seconds',
        ];
    }
}
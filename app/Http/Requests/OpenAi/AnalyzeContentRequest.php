<?php

namespace App\Http\Requests\OpenAi;

use Illuminate\Foundation\Http\FormRequest;

class AnalyzeContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('use-openai');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'material_id' => 'required|exists:materials,id',
            'content' => 'required|string|max:' . config('services.openai.max_content_size', 50000),
            'obtl_context' => 'nullable|string|max:10000',
            'force_reanalysis' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.max' => 'Content exceeds maximum size of :max characters. Please split into smaller chunks.',
            'material_id.exists' => 'The specified material does not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'material_id' => 'material',
            'obtl_context' => 'OBTL context',
        ];
    }
}
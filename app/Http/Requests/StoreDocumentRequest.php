<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'lecture_file' => 'required|file|mimes:pdf,docx|max:20480', // 20MB
            'lecture_number' => 'nullable|string|max:50',
            'hours_taught' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Document title is required',
            'lecture_file.required' => 'Lecture file is required',
            'lecture_file.mimes' => 'Lecture file must be PDF or DOCX',
            'lecture_file.max' => 'Lecture file must not exceed 20MB',
            'hours_taught.numeric' => 'Hours taught must be a number',
        ];
    }
}
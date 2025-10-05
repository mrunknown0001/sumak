<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_code' => 'required|string|max:50',
            'course_title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'obtl_file' => 'nullable|file|mimes:pdf|max:10240', // 10MB
        ];
    }

    public function messages(): array
    {
        return [
            'course_code.required' => 'Course code is required',
            'course_title.required' => 'Course title is required',
            'obtl_file.mimes' => 'OBTL document must be a PDF file',
            'obtl_file.max' => 'OBTL document must not exceed 10MB',
        ];
    }
}
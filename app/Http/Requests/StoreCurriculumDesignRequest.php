<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCurriculumDesignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\CurriculumDesign::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxSize = config('curriculum_ai.pdf.max_file_size', 50 * 1024 * 1024); // 50MB default

        return [
            'title' => ['required', 'string', 'max:255'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'class_level' => ['nullable', 'string', 'max:50'],
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:' . ($maxSize / 1024), // Convert to KB
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please upload a PDF file.',
            'file.mimes' => 'The file must be a PDF.',
            'file.max' => 'The file size must not exceed ' . (config('curriculum_ai.pdf.max_file_size', 50 * 1024 * 1024) / 1024 / 1024) . 'MB.',
        ];
    }
}

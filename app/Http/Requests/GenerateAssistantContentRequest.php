<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAssistantContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('use-assistant', \App\Models\CurriculumDesign::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'curriculum_design_id' => ['required', 'exists:curriculum_designs,id'],
            'type' => ['required', 'in:scheme,lesson_plan,assessment,report_card'],
            'query' => ['required', 'string', 'max:2000'],
            'context' => ['sometimes', 'array'],
            'context.subject_id' => ['sometimes', 'nullable', 'exists:subjects,id'],
            'context.class_level' => ['sometimes', 'nullable', 'string'],
            'context.weeks' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:52'],
            'context.strand_id' => ['sometimes', 'nullable', 'exists:cbc_strands,id'],
            'context.substrand_id' => ['sometimes', 'nullable', 'exists:cbc_substrands,id'],
        ];
    }
}

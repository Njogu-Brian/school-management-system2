<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'enrollment_date' => 'required|date',
            'completion_date' => 'nullable|date|after_or_equal:enrollment_date',
            'promotion_status' => 'nullable|in:promoted,retained,demoted,transferred,graduated',
            'final_grade' => 'nullable|numeric|min:0|max:100',
            'class_position' => 'nullable|integer|min:1',
            'stream_position' => 'nullable|integer|min:1',
            'remarks' => 'nullable|string',
            'teacher_comments' => 'nullable|string',
            'is_current' => 'nullable|boolean',
        ];
    }
}

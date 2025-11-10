<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'record_type' => 'required|in:vaccination,checkup,medication,incident,certificate,other',
            'record_date' => 'required|date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'doctor_name' => 'nullable|string|max:255',
            'clinic_hospital' => 'nullable|string|max:255',
            'medication_name' => 'nullable|string|max:255',
            'medication_dosage' => 'nullable|string',
            'medication_start_date' => 'nullable|date',
            'medication_end_date' => 'nullable|date|after_or_equal:medication_start_date',
            'vaccination_name' => 'nullable|string|max:255',
            'vaccination_date' => 'nullable|date',
            'next_due_date' => 'nullable|date',
            'certificate_type' => 'nullable|string|max:255',
            'certificate_file_path' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}

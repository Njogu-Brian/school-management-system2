<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisciplinaryRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'incident_date' => 'required|date',
            'incident_time' => 'nullable|date_format:H:i',
            'incident_type' => 'required|string|max:255',
            'severity' => 'required|in:minor,moderate,major,severe',
            'description' => 'required|string',
            'witnesses' => 'nullable|string',
            'action_taken' => 'nullable|in:warning,verbal_warning,written_warning,detention,suspension,expulsion,parent_meeting,counseling,other',
            'action_details' => 'nullable|string',
            'action_date' => 'nullable|date',
            'improvement_plan' => 'nullable|string',
            'parent_notified' => 'nullable|boolean',
            'parent_notification_date' => 'nullable|date',
            'follow_up_notes' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
            'resolved' => 'nullable|boolean',
            'resolved_date' => 'nullable|date',
            'action_taken_by' => 'nullable|exists:users,id',
        ];
    }
}

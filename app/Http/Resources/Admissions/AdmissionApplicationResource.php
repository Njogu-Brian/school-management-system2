<?php

namespace App\Http\Resources\Admissions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_no' => $this->application_no,
            'draft_token' => $this->when($request->route('token') || $request->is('api/website/admissions/start'), $this->draft_token),
            'parent_name' => $this->parent_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'child_name' => $this->child_name,
            'dob' => $this->dob?->toDateString(),
            'gender' => $this->gender,
            'age' => $this->age,
            'desired_class' => $this->desired_class,
            'previous_school' => $this->previous_school,
            'medical_notes' => $this->when($request->user(), $this->medical_notes),
            'special_needs' => $this->when($request->user(), $this->special_needs),
            'status' => $this->status,
            'source' => $this->source,
            'current_step' => $this->current_step,
            'assessment_date' => $this->assessment_date?->toIso8601String(),
            'admission_notes' => $this->when($request->user(), $this->admission_notes),
            'student_id' => $this->student_id,
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($d) => [
                'type' => $d->document_type,
                'url' => $d->url(),
                'verified' => $d->verified,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

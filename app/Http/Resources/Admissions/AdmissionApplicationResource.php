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
            'preferred_classroom_id' => $this->preferred_classroom_id,
            'preferred_classroom' => $this->whenLoaded('preferredClassroom', fn () => $this->preferredClassroom?->name),
            'enrollment_year' => $this->enrollment_year,
            'enrollment_term' => $this->enrollment_term,
            'previous_school' => $this->previous_school,
            'medical_notes' => $this->when($request->user(), $this->medical_notes),
            'special_needs' => $this->when($request->user(), $this->special_needs),
            'status' => $this->pipelineStatus(),
            'source' => $this->source,
            'current_step' => $this->current_step,
            'assessment_date' => $this->assessment_date?->toIso8601String(),
            'admission_notes' => $this->when($request->user(), $this->admission_notes),
            'student_id' => $this->student_id,
            'online_admission_id' => $this->online_admission_id,
            'pipeline' => $this->when(
                $this->relationLoaded('onlineAdmission') || $this->online_admission_id,
                fn () => $this->pipelinePayload()
            ),
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($d) => [
                'type' => $d->document_type,
                'url' => $d->url(),
                'verified' => $d->verified,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    protected function pipelineStatus(): string
    {
        $online = $this->onlineAdmission ?? null;
        if ($online && $online->application_status) {
            return $online->application_status;
        }

        return (string) $this->status;
    }

    protected function pipelinePayload(): ?array
    {
        $online = $this->onlineAdmission;
        if (! $online) {
            return null;
        }

        return [
            'application_status' => $online->application_status,
            'pipeline_stage' => $online->pipeline_stage,
            'interview_required' => $online->interview_required,
            'interview_outcome' => $online->interview_outcome,
            'interview_date' => $online->interview_date?->toDateString(),
            'interview_fee_paid' => (bool) $online->interview_fee_paid,
            'stages' => [
                ['key' => 'prospectus', 'label' => 'Prospectus received', 'done' => true],
                ['key' => 'contacted', 'label' => 'Contacted', 'done' => in_array($online->application_status, ['contacted', 'under_review', 'interviewed', 'ready_to_enroll', 'enrolled'], true)],
                ['key' => 'interview', 'label' => 'Interview', 'done' => in_array($online->application_status, ['interviewed', 'ready_to_enroll', 'enrolled'], true) || $online->interview_required === 'not_applicable'],
                ['key' => 'admission_form', 'label' => 'Admission form', 'done' => in_array($online->application_status, ['ready_to_enroll', 'enrolled'], true) || filled($online->signed_admission_form)],
                ['key' => 'enrolled', 'label' => 'Enrolled', 'done' => (bool) $online->enrolled || $online->application_status === 'enrolled'],
            ],
        ];
    }
}

<?php

namespace App\Services\Admissions;

use App\Models\Academics\Classroom;
use App\Models\Admissions\AdmissionApplication;
use App\Models\OnlineAdmission;
use Illuminate\Support\Facades\DB;

/**
 * Maps website short Apply (prospectus) into the shared OnlineAdmission pipeline.
 */
class ProspectusToOnlineAdmissionSync
{
    public function __construct(
        private AdmissionInterviewPolicy $interviewPolicy,
    ) {
    }

    public function syncFromApplication(AdmissionApplication $application): OnlineAdmission
    {
        return DB::transaction(function () use ($application) {
            $application->refresh();
            $parts = $application->childNameParts();
            $classroom = $application->preferred_classroom_id
                ? Classroom::withoutGlobalScope('offered')->find($application->preferred_classroom_id)
                : null;

            $policy = $this->interviewPolicy->forClassroom($classroom);

            $parentName = $application->parent_name;
            $payload = [
                'admission_application_id' => $application->id,
                'application_no' => $application->application_no,
                'first_name' => $parts['first_name'],
                'middle_name' => $parts['middle_name'],
                'last_name' => $parts['last_name'],
                'dob' => $application->dob,
                'gender' => $this->normalizeGender($application->gender),
                'preferred_classroom_id' => $application->preferred_classroom_id,
                'father_name' => $parentName,
                'father_phone' => $application->phone,
                'father_email' => $application->email,
                'prospectus_parent_name' => $parentName,
                'prospectus_phone' => $application->phone,
                'prospectus_email' => $application->email,
                'application_source' => $application->source ?: 'website_prospectus',
                'application_date' => $application->submitted_at?->toDateString() ?? now()->toDateString(),
                'application_notes' => $application->admission_notes,
                'previous_school' => $application->previous_school,
                'interview_required' => $policy,
                'pipeline_stage' => 'prospectus',
                'form_status' => 'Not Submitted',
            ];

            if ($application->online_admission_id) {
                $admission = OnlineAdmission::find($application->online_admission_id);
                if ($admission && ! $admission->enrolled) {
                    $admission->update($payload);

                    return $admission->fresh();
                }
            }

            $existing = OnlineAdmission::query()
                ->where('admission_application_id', $application->id)
                ->orWhere(function ($q) use ($application) {
                    if ($application->application_no) {
                        $q->where('application_no', $application->application_no);
                    }
                })
                ->first();

            if ($existing && ! $existing->enrolled) {
                $existing->update($payload);
                $application->update(['online_admission_id' => $existing->id]);

                return $existing->fresh();
            }

            $admission = OnlineAdmission::create(array_merge($payload, [
                'application_status' => 'pending',
                'payment_status' => 'Unpaid',
                'enrolled' => false,
            ]));

            $application->update(['online_admission_id' => $admission->id]);

            return $admission->fresh();
        });
    }

    protected function normalizeGender(?string $gender): ?string
    {
        if ($gender === null || $gender === '') {
            return null;
        }

        $g = strtolower(trim($gender));
        if (in_array($g, ['m', 'male', 'boy'], true)) {
            return 'Male';
        }
        if (in_array($g, ['f', 'female', 'girl'], true)) {
            return 'Female';
        }

        return null;
    }
}

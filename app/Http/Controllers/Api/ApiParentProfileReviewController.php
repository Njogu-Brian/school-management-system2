<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentInfo;
use App\Models\Student;
use App\Support\KemisProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Post-claim profile review for parents (DATA ONLY — no document uploads).
 *
 * Ports the field set from FamilyUpdateController::submit (parent + student data),
 * excluding all file fields. Used to force a one-time review right after a parent
 * claims their account (users.parent_profile_review_required = true).
 */
class ApiParentProfileReviewController extends Controller
{
    /**
     * GET /parent/profile-review
     * Returns the parent_info record + accessible students for review.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        if (!$user->parent_id) {
            return response()->json([
                'success' => false,
                'message' => 'This account is not linked to a parent record.',
            ], 403);
        }

        $parent = ParentInfo::find($user->parent_id);
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Parent record not found.',
            ], 404);
        }

        $studentIds = $user->accessibleStudentIds();
        $students = Student::whereIn('id', $studentIds)
            ->where('archive', 0)
            ->with('classroom')
            ->get()
            ->map(fn (Student $s) => $this->formatStudent($s))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'review_required' => (bool) ($user->parent_profile_review_required ?? false),
                'parent' => $this->formatParent($parent),
                'students' => $students,
            ],
        ]);
    }

    /**
     * PUT /parent/profile-review
     * Updates parent + student data (no files).
     */
    public function update(Request $request)
    {
        $user = $request->user();
        if (!$user->parent_id) {
            return response()->json([
                'success' => false,
                'message' => 'This account is not linked to a parent record.',
            ], 403);
        }

        $parent = ParentInfo::find($user->parent_id);
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Parent record not found.',
            ], 404);
        }

        $accessibleIds = $user->accessibleStudentIds();

        $validated = KemisProfile::validateRequest($request, array_merge(
            KemisProfile::sharedContactValidationRules(),
            KemisProfile::parentKemisValidationRules(),
            [
            'students' => 'sometimes|array',
            'students.*.id' => ['required', 'integer', 'in:' . (empty($accessibleIds) ? '0' : implode(',', $accessibleIds))],
            'students.*.first_name' => 'required|string|max:255',
            'students.*.middle_name' => 'nullable|string|max:255',
            'students.*.last_name' => 'required|string|max:255',
            'students.*.gender' => 'nullable|in:Male,Female,male,female',
            'students.*.dob' => 'nullable|date',
            'students.*.has_allergies' => 'nullable|boolean',
            'students.*.allergies_notes' => 'nullable|string',
            'students.*.is_fully_immunized' => 'nullable|boolean',
            'father_phone' => ['nullable', 'string', 'max:50'],
            'mother_phone' => ['nullable', 'string', 'max:50'],
            'guardian_phone' => ['nullable', 'string', 'max:50'],
            'father_whatsapp' => ['nullable', 'string', 'max:50'],
            'mother_whatsapp' => ['nullable', 'string', 'max:50'],
            'guardian_email' => 'nullable|email|max:255',
            'marital_status' => 'nullable|in:married,single_parent,co_parenting',
        ],
            KemisProfile::studentKemisValidationRules('students.*')
        ));

        DB::transaction(function () use ($validated, $parent, $accessibleIds) {
            $parentData = [];
            foreach ([
                'father_phone', 'father_email', 'father_whatsapp',
                'mother_phone', 'mother_email', 'mother_whatsapp',
                'guardian_phone', 'guardian_relationship', 'marital_status', 'guardian_email',
            ] as $field) {
                if (array_key_exists($field, $validated)) {
                    $parentData[$field] = $validated[$field] ?: null;
                }
            }
            foreach (['father', 'mother', 'guardian'] as $slot) {
                $parentData = array_merge($parentData, KemisProfile::parentIdentityAttributesFromInput($validated, $slot));
            }
            if (!empty($parentData)) {
                $parent->fill($parentData);
                $parent->save();
            }

            if (!empty($validated['students'])) {
                foreach ($validated['students'] as $stuData) {
                    if (!in_array((int) $stuData['id'], array_map('intval', $accessibleIds), true)) {
                        continue;
                    }
                    $student = Student::where('id', $stuData['id'])->where('archive', 0)->first();
                    if (!$student) {
                        continue;
                    }

                    $updateData = array_merge([
                        'first_name' => $stuData['first_name'],
                        'last_name' => $stuData['last_name'],
                    ], KemisProfile::studentKemisAttributesFromInput($stuData));
                    if (array_key_exists('middle_name', $stuData)) {
                        $updateData['middle_name'] = $stuData['middle_name'] ?: null;
                    }
                    if (!empty($stuData['gender'])) {
                        $updateData['gender'] = strtolower(trim($stuData['gender']));
                    }
                    if (array_key_exists('dob', $stuData)) {
                        $updateData['dob'] = $stuData['dob'] ?: null;
                    }
                    if (array_key_exists('has_allergies', $stuData)) {
                        $updateData['has_allergies'] = (bool) $stuData['has_allergies'];
                    }
                    if (array_key_exists('allergies_notes', $stuData)) {
                        $updateData['allergies_notes'] = $stuData['allergies_notes'] ?: null;
                    }
                    if (array_key_exists('is_fully_immunized', $stuData)) {
                        $updateData['is_fully_immunized'] = (bool) $stuData['is_fully_immunized'];
                    }
                    // Shared per-student fields carried at the top level of the payload.
                    foreach (['residential_area', 'preferred_hospital', 'emergency_contact_name', 'emergency_contact_phone'] as $shared) {
                        if (array_key_exists($shared, $validated)) {
                            $updateData[$shared] = $validated[$shared] ?: null;
                        }
                    }

                    $student->fill($updateData);
                    $student->save();
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Details saved.',
        ]);
    }

    /**
     * POST /parent/profile-review/complete
     * Clears the review-required flag.
     */
    public function complete(Request $request)
    {
        $user = $request->user();
        if (!$user->parent_id) {
            return response()->json([
                'success' => false,
                'message' => 'This account is not linked to a parent record.',
            ], 403);
        }

        if (Schema::hasColumn('users', 'parent_profile_review_required')) {
            $user->parent_profile_review_required = false;
        }
        if (Schema::hasColumn('users', 'profile_completed_at')) {
            $user->profile_completed_at = now();
        }
        $user->save();

        \App\Models\ParentForcedAction::query()
            ->where('parent_info_id', $user->parent_id)
            ->whereIn('type', [
                \App\Models\ParentForcedAction::TYPE_PROFILE_REVIEW,
                \App\Models\ParentForcedAction::TYPE_UPLOAD_DOCUMENTS,
            ])
            ->where('status', \App\Models\ParentForcedAction::STATUS_PENDING)
            ->get()
            ->each(fn ($a) => $a->markCompleted());

        $user->load('roles', 'roles.permissions', 'staff');

        return response()->json([
            'success' => true,
            'data' => app(AuthApiController::class)->formatUserForApiPublic($user),
        ]);
    }

    private function formatParent(ParentInfo $parent): array
    {
        return [
            'id' => $parent->id,
            'father_name' => $parent->father_name,
            'father_first_name' => $parent->father_first_name,
            'father_middle_name' => $parent->father_middle_name,
            'father_last_name' => $parent->father_last_name,
            'father_id_type' => $parent->father_id_type,
            'father_id_number' => $parent->father_id_number,
            'father_country_of_residence' => $parent->father_country_of_residence,
            'father_phone' => $parent->father_phone,
            'father_whatsapp' => $parent->father_whatsapp,
            'father_email' => $parent->father_email,
            'mother_name' => $parent->mother_name,
            'mother_first_name' => $parent->mother_first_name,
            'mother_middle_name' => $parent->mother_middle_name,
            'mother_last_name' => $parent->mother_last_name,
            'mother_id_type' => $parent->mother_id_type,
            'mother_id_number' => $parent->mother_id_number,
            'mother_country_of_residence' => $parent->mother_country_of_residence,
            'mother_phone' => $parent->mother_phone,
            'mother_whatsapp' => $parent->mother_whatsapp,
            'mother_email' => $parent->mother_email,
            'guardian_name' => $parent->guardian_name,
            'guardian_first_name' => $parent->guardian_first_name,
            'guardian_middle_name' => $parent->guardian_middle_name,
            'guardian_last_name' => $parent->guardian_last_name,
            'guardian_id_type' => $parent->guardian_id_type,
            'guardian_id_number' => $parent->guardian_id_number,
            'guardian_country_of_residence' => $parent->guardian_country_of_residence,
            'guardian_phone' => $parent->guardian_phone,
            'guardian_relationship' => $parent->guardian_relationship,
            'guardian_email' => $parent->guardian_email,
            'marital_status' => $parent->marital_status,
        ];
    }

    private function formatStudent(Student $s): array
    {
        return [
            'id' => (int) $s->id,
            'admission_number' => $s->admission_number,
            'first_name' => $s->first_name,
            'middle_name' => $s->middle_name,
            'last_name' => $s->last_name,
            'gender' => $s->gender,
            'dob' => optional($s->dob)->toDateString(),
            'class_name' => $s->classroom?->name,
            'has_allergies' => (bool) $s->has_allergies,
            'allergies_notes' => $s->allergies_notes,
            'is_fully_immunized' => (bool) $s->is_fully_immunized,
            'residential_area' => $s->residential_area,
            'preferred_hospital' => $s->preferred_hospital,
            'emergency_contact_name' => $s->emergency_contact_name,
            'emergency_contact_phone' => $s->emergency_contact_phone,
            'nationality' => $s->nationality,
            'county_of_birth' => $s->county_of_birth,
            'sub_county_of_birth' => $s->sub_county_of_birth,
            'location_of_birth' => $s->location_of_birth,
            'birth_certificate_entry_no' => $s->birth_certificate_entry_no,
            'medical_condition' => $s->medical_condition,
            'religion' => $s->religion,
            'learner_interests' => $s->learner_interests,
            'orphan_status' => $s->orphan_status,
            'has_special_needs' => (bool) $s->has_special_needs,
            'disability_type' => $s->disability_type,
        ];
    }
}

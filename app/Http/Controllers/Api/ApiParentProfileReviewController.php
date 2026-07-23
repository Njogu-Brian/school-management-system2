<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentInfo;
use App\Models\Student;
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

        $validated = $request->validate([
            'residential_area' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'father_id_number' => 'nullable|string|max:255',
            'father_phone' => ['nullable', 'string', 'max:50'],
            'father_email' => 'nullable|email|max:255',
            'mother_name' => 'nullable|string|max:255',
            'mother_id_number' => 'nullable|string|max:255',
            'mother_phone' => ['nullable', 'string', 'max:50'],
            'mother_email' => 'nullable|email|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => ['nullable', 'string', 'max:50'],
            'guardian_relationship' => 'nullable|string|max:255',
            'marital_status' => 'nullable|in:married,single_parent,co_parenting',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => ['nullable', 'string', 'max:80'],
            'preferred_hospital' => 'nullable|string|max:255',
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
        ]);

        DB::transaction(function () use ($validated, $parent, $accessibleIds) {
            $parentData = [];
            foreach ([
                'father_name', 'father_id_number', 'father_phone', 'father_email',
                'mother_name', 'mother_id_number', 'mother_phone', 'mother_email',
                'guardian_name', 'guardian_phone', 'guardian_relationship', 'marital_status',
            ] as $field) {
                if (array_key_exists($field, $validated)) {
                    $parentData[$field] = $validated[$field] ?: null;
                }
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

                    $updateData = [
                        'first_name' => $stuData['first_name'],
                        'last_name' => $stuData['last_name'],
                    ];
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
            $user->save();
        }

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
            'father_id_number' => $parent->father_id_number,
            'father_phone' => $parent->father_phone,
            'father_email' => $parent->father_email,
            'mother_name' => $parent->mother_name,
            'mother_id_number' => $parent->mother_id_number,
            'mother_phone' => $parent->mother_phone,
            'mother_email' => $parent->mother_email,
            'guardian_name' => $parent->guardian_name,
            'guardian_phone' => $parent->guardian_phone,
            'guardian_relationship' => $parent->guardian_relationship,
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
        ];
    }
}

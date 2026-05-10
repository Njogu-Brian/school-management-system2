<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\DropOffPoint;
use App\Models\ParentInfo;
use App\Models\Setting;
use App\Models\Student;
use App\Models\ActivityLog;
use App\Models\StudentCategory;
use App\Services\FamilyLinkingService;
use App\Services\PhoneNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mobile API: create/update students with multipart uploads (aligned with web StudentController).
 */
class ApiStudentWriteController extends Controller
{
    protected function assertCanManageStudents(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            abort(403, 'You do not have permission to manage students.');
        }
    }

    public function categories(Request $request)
    {
        $this->assertCanManageStudents($request);

        $rows = StudentCategory::orderBy('name')->get(['id', 'name', 'description']);

        return response()->json(['success' => true, 'data' => $rows]);
    }

    /**
     * Get or create family profile update link (same URL as portal family-update/{token}).
     */
    public function profileUpdateLink(Request $request, int $id)
    {
        $this->assertCanManageStudents($request);

        $student = Student::where('archive', 0)->where('is_alumni', false)->findOrFail($id);
        $link = get_or_create_profile_update_link_for_student($student->fresh());
        if (! $link) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create a profile update link for this student.',
            ], 422);
        }

        $url = url('/family-update/'.$link->token);

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $url,
                'token' => $link->token,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->assertCanManageStudents($request);

        if ($request->input('drop_off_point_id') === 'other') {
            $request->merge(['drop_off_point_id' => null]);
        }
        $streamId = $request->input('stream_id');
        if ($streamId === '' || $streamId === null || ! is_numeric($streamId) || (int) $streamId < 1) {
            $request->merge(['stream_id' => null]);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|string',
            'dob' => 'required|date',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'category_id' => 'required|exists:student_categories,id',
            'trip_id' => 'nullable|exists:trips,id',
            'drop_off_point_id' => 'nullable|exists:drop_off_points,id',
            'drop_off_point_other' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'father_phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{4,15}$/'],
            'mother_phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{4,15}$/'],
            'guardian_phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{4,15}$/'],
            'father_whatsapp' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{4,15}$/'],
            'mother_whatsapp' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{4,15}$/'],
            'guardian_whatsapp' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{4,15}$/'],
            'father_email' => 'nullable|email',
            'mother_email' => 'nullable|email',
            'guardian_email' => 'nullable|email',
            'guardian_relationship' => 'nullable|string|max:255',
            'marital_status' => 'nullable|in:married,single_parent,co_parenting',
            'father_id_number' => 'nullable|string|max:64',
            'mother_id_number' => 'nullable|string|max:64',
            'father_phone_country_code' => 'nullable|string|max:8',
            'mother_phone_country_code' => 'nullable|string|max:8',
            'guardian_phone_country_code' => 'nullable|string|max:8',
            'father_id_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'mother_id_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'has_allergies' => 'nullable|boolean',
            'allergies_notes' => 'nullable|string',
            'is_fully_immunized' => 'nullable|boolean',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => ['nullable', 'string', 'max:80'],
            'residential_area' => 'nullable|string|max:255',
            'preferred_hospital' => 'nullable|string|max:255',
            'nemis_number' => 'nullable|string',
            'knec_assessment_number' => 'nullable|string',
            'religion' => 'nullable|string|max:255',
            'admission_date' => 'nullable|date',
        ]);

        $parentName = $request->father_name ?: $request->mother_name ?: $request->guardian_name;
        $parentPhone = $request->father_phone ?: $request->mother_phone ?: $request->guardian_phone;
        if (! $parentName || ! $parentPhone) {
            return response()->json([
                'success' => false,
                'message' => 'At least one parent/guardian name and a local phone number (digits only) is required.',
            ], 422);
        }

        $classroomId = (int) $request->classroom_id;
        $classroom = Classroom::withCount(['streams', 'primaryStreams'])->find($classroomId);
        $classroomHasStreams = $classroom && (($classroom->streams_count ?? 0) + ($classroom->primary_streams_count ?? 0)) > 0;
        if ($classroomHasStreams && ! $request->stream_id) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a stream for the chosen class.',
            ], 422);
        }

        $phone = app(PhoneNumberService::class);

        try {
            $student = DB::transaction(function () use ($request, $phone) {
                $fatherCountryCode = $phone->normalizeCountryCode($request->input('father_phone_country_code', '+254'));
                $motherCountryCode = $phone->normalizeCountryCode($request->input('mother_phone_country_code', '+254'));
                $guardianCountryCode = $phone->normalizeCountryCode($request->input('guardian_phone_country_code', '+254'));

                $parentData = [
                    'father_name' => $request->father_name,
                    'father_phone' => $phone->formatWithCountryCode($request->father_phone, $fatherCountryCode),
                    'father_whatsapp' => $phone->formatWithCountryCode($request->father_whatsapp, $fatherCountryCode),
                    'father_email' => $request->father_email,
                    'father_id_number' => $request->father_id_number,
                    'mother_name' => $request->mother_name,
                    'mother_phone' => $phone->formatWithCountryCode($request->mother_phone, $motherCountryCode),
                    'mother_whatsapp' => $phone->formatWithCountryCode($request->mother_whatsapp, $motherCountryCode),
                    'mother_email' => $request->mother_email,
                    'mother_id_number' => $request->mother_id_number,
                    'guardian_name' => $request->guardian_name,
                    'guardian_phone' => $phone->formatWithCountryCode($request->guardian_phone, $guardianCountryCode),
                    'guardian_whatsapp' => $phone->formatWithCountryCode($request->guardian_whatsapp, $guardianCountryCode),
                    'guardian_email' => $request->guardian_email,
                    'guardian_relationship' => $request->guardian_relationship,
                    'marital_status' => $request->marital_status,
                    'father_phone_country_code' => $fatherCountryCode,
                    'mother_phone_country_code' => $motherCountryCode,
                    'guardian_phone_country_code' => $guardianCountryCode,
                ];
                $linker = app(FamilyLinkingService::class);
                $matched = $linker->findMatchingParent($parentData);
                $parent = $matched ?: ParentInfo::create($parentData);

                $admissionNumber = $this->generateNextAdmissionNumber();

                $dropOffPointLabel = null;
                if ($request->filled('drop_off_point_other')) {
                    $dropOffPointLabel = $request->drop_off_point_other;
                } elseif ($request->filled('drop_off_point_id')) {
                    $dropOffPointLabel = optional(DropOffPoint::find($request->drop_off_point_id))->name;
                }

                $emergencyPhone = $phone->formatWithCountryCode(
                    $request->emergency_contact_phone,
                    $request->input('emergency_contact_country_code', '+254')
                );

                $studentData = $request->only([
                    'first_name', 'middle_name', 'last_name', 'gender', 'dob',
                    'classroom_id', 'stream_id', 'category_id',
                    'trip_id', 'drop_off_point_id', 'drop_off_point_other',
                    'has_allergies', 'allergies_notes', 'is_fully_immunized',
                    'emergency_contact_name',
                    'residential_area', 'preferred_hospital',
                    'nemis_number', 'knec_assessment_number',
                    'religion',
                    'admission_date',
                ]);
                if (isset($studentData['gender'])) {
                    $studentData['gender'] = strtolower(trim((string) $studentData['gender']));
                }
                if (isset($studentData['stream_id']) && ($studentData['stream_id'] === '' || (int) ($studentData['stream_id'] ?? 0) < 1)) {
                    $studentData['stream_id'] = null;
                }
                if (empty($studentData['admission_date'])) {
                    $studentData['admission_date'] = now()->toDateString();
                } else {
                    $studentData['admission_date'] = \Carbon\Carbon::parse($studentData['admission_date'])->toDateString();
                }

                $student = Student::create(array_merge($studentData, [
                    'admission_number' => $admissionNumber,
                    'parent_id' => $parent->id,
                    'family_id' => null,
                    'drop_off_point' => $dropOffPointLabel,
                    'emergency_contact_phone' => $emergencyPhone,
                ]));

                // Auto-link siblings when parent contact already exists.
                $linker->ensureFamilyForStudentFromParent($student, $parent);

                if ($request->hasFile('photo')) {
                    $student->photo_path = $request->file('photo')->store('students/photos', config('filesystems.public_disk', 'public'));
                    $student->save();
                }

                $this->handleParentIdUploads($parent, $request);

                try {
                    \App\Services\FeePostingService::chargeFeesForNewStudent($student);
                } catch (\Throwable $e) {
                    Log::warning('API student create: fee posting failed: '.$e->getMessage(), ['student_id' => $student->id]);
                }

                return $student->fresh(['parent', 'classroom', 'stream', 'category']);
            });
        } catch (\Throwable $e) {
            Log::error('API student create failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create student: '.$e->getMessage(),
            ], 500);
        }

        $formatted = app(ApiStudentController::class)->serializeStudent($student);

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'message' => 'Student created.',
        ], 201);
    }

    /**
     * Multipart-friendly update (use POST from mobile with FormData).
     */
    public function update(Request $request, int $id)
    {
        $this->assertCanManageStudents($request);

        $student = Student::with(['parent'])->findOrFail($id);

        if ($request->input('drop_off_point_id') === 'other') {
            $request->merge(['drop_off_point_id' => null]);
        }
        $streamId = $request->input('stream_id');
        if ($streamId === '' || $streamId === null || ! is_numeric($streamId) || (int) $streamId < 1) {
            $request->merge(['stream_id' => null]);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|string',
            'dob' => 'nullable|date',
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'category_id' => 'required|exists:student_categories,id',
            'trip_id' => 'nullable|exists:trips,id',
            'drop_off_point_id' => 'nullable|exists:drop_off_points,id',
            'drop_off_point_other' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'father_phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{4,15}$/'],
            'mother_phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{4,15}$/'],
            'guardian_phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{4,15}$/'],
            'father_whatsapp' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{4,15}$/'],
            'mother_whatsapp' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{4,15}$/'],
            'guardian_whatsapp' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]{4,15}$/'],
            'father_email' => 'nullable|email',
            'mother_email' => 'nullable|email',
            'guardian_email' => 'nullable|email',
            'guardian_relationship' => 'nullable|string|max:255',
            'marital_status' => 'nullable|in:married,single_parent,co_parenting',
            'father_id_number' => 'nullable|string|max:64',
            'mother_id_number' => 'nullable|string|max:64',
            'father_phone_country_code' => 'nullable|string|max:8',
            'mother_phone_country_code' => 'nullable|string|max:8',
            'guardian_phone_country_code' => 'nullable|string|max:8',
            'father_id_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'mother_id_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'has_allergies' => 'nullable|boolean',
            'allergies_notes' => 'nullable|string',
            'is_fully_immunized' => 'nullable|boolean',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => ['nullable', 'string', 'max:80'],
            'residential_area' => 'required|string|max:255',
            'preferred_hospital' => 'nullable|string|max:255',
            'nemis_number' => 'nullable|string',
            'knec_assessment_number' => 'nullable|string',
            'religion' => 'nullable|string|max:255',
            'admission_date' => 'required|date',
        ]);

        $parentName = $request->father_name ?: $request->mother_name ?: $request->guardian_name;
        $parentPhone = $request->father_phone ?: $request->mother_phone ?: $request->guardian_phone;
        if (! $parentName || ! $parentPhone) {
            return response()->json([
                'success' => false,
                'message' => 'At least one parent/guardian name and a local phone number (digits only) is required.',
            ], 422);
        }

        $classroomId = (int) $request->classroom_id;
        $classroom = Classroom::withCount(['streams', 'primaryStreams'])->find($classroomId);
        $classroomHasStreams = $classroom && (($classroom->streams_count ?? 0) + ($classroom->primary_streams_count ?? 0)) > 0;
        if ($classroomHasStreams && ! $request->stream_id) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a stream for the chosen class.',
            ], 422);
        }

        $phone = app(PhoneNumberService::class);

        $previousAdmissionDate = $student->admission_date?->toDateString();

        try {
            DB::transaction(function () use ($request, $student, $phone, $previousAdmissionDate) {
                $updateData = $request->only([
                    'first_name', 'middle_name', 'last_name', 'gender', 'dob',
                    'classroom_id', 'stream_id', 'category_id',
                    'trip_id', 'drop_off_point_id', 'drop_off_point_other',
                    'has_allergies', 'allergies_notes', 'is_fully_immunized',
                    'emergency_contact_name',
                    'residential_area', 'preferred_hospital',
                    'nemis_number', 'knec_assessment_number',
                    'religion',
                    'admission_date',
                ]);
                if (isset($updateData['gender'])) {
                    $updateData['gender'] = strtolower(trim((string) $updateData['gender']));
                }
                if (isset($updateData['stream_id']) && ($updateData['stream_id'] === '' || (int) ($updateData['stream_id'] ?? 0) < 1)) {
                    $updateData['stream_id'] = null;
                }
                if (isset($updateData['dob']) && $updateData['dob'] === '') {
                    $updateData['dob'] = null;
                }
                if (! empty($updateData['admission_date'])) {
                    $updateData['admission_date'] = \Carbon\Carbon::parse($updateData['admission_date'])->toDateString();
                }

                $dropOffPointLabel = null;
                if ($request->filled('drop_off_point_other')) {
                    $dropOffPointLabel = $request->drop_off_point_other;
                } elseif ($request->filled('drop_off_point_id')) {
                    $dropOffPointLabel = optional(DropOffPoint::find($request->drop_off_point_id))->name;
                }
                $updateData['drop_off_point'] = $dropOffPointLabel;

                $updateData['emergency_contact_phone'] = $phone->formatWithCountryCode(
                    $request->emergency_contact_phone,
                    $request->input('emergency_contact_country_code', '+254')
                );

                $student->update($updateData);
                $student->refresh();

                if (array_key_exists('admission_date', $updateData) && $previousAdmissionDate !== $student->admission_date?->toDateString()) {
                    ActivityLog::log(
                        'update',
                        $student,
                        "Enrolment date changed for {$student->full_name} ({$student->admission_number}): {$previousAdmissionDate} → {$student->admission_date->toDateString()} (API)",
                        ['admission_date' => $previousAdmissionDate],
                        ['admission_date' => $student->admission_date->toDateString()]
                    );
                }

                if ($request->hasFile('photo')) {
                    if ($student->photo_path) {
                        storage_public()->delete($student->photo_path);
                    }
                    $student->photo_path = $request->file('photo')->store('students/photos', config('filesystems.public_disk', 'public'));
                    $student->save();
                }

                if ($student->parent) {
                    $fatherCountryCode = $phone->normalizeCountryCode($request->input('father_phone_country_code', '+254'));
                    $motherCountryCode = $phone->normalizeCountryCode($request->input('mother_phone_country_code', '+254'));
                    $guardianCountryCode = $phone->normalizeCountryCode($request->input('guardian_phone_country_code', '+254'));

                    $student->parent->update([
                        'father_name' => $request->father_name,
                        'father_phone' => $phone->formatWithCountryCode($request->father_phone, $fatherCountryCode),
                        'father_whatsapp' => $phone->formatWithCountryCode($request->father_whatsapp, $fatherCountryCode),
                        'father_email' => $request->father_email,
                        'father_id_number' => $request->father_id_number,
                        'mother_name' => $request->mother_name,
                        'mother_phone' => $phone->formatWithCountryCode($request->mother_phone, $motherCountryCode),
                        'mother_whatsapp' => $phone->formatWithCountryCode($request->mother_whatsapp, $motherCountryCode),
                        'mother_email' => $request->mother_email,
                        'mother_id_number' => $request->mother_id_number,
                        'guardian_name' => $request->guardian_name,
                        'guardian_phone' => $phone->formatWithCountryCode($request->guardian_phone, $guardianCountryCode),
                        'guardian_whatsapp' => $phone->formatWithCountryCode($request->guardian_whatsapp, $guardianCountryCode),
                        'guardian_email' => $request->guardian_email,
                        'guardian_relationship' => $request->guardian_relationship,
                        'marital_status' => $request->marital_status,
                        'father_phone_country_code' => $fatherCountryCode,
                        'mother_phone_country_code' => $motherCountryCode,
                        'guardian_phone_country_code' => $guardianCountryCode,
                    ]);
                    $this->handleParentIdUploads($student->parent, $request);
                }
            });
        } catch (\Throwable $e) {
            Log::error('API student update failed: '.$e->getMessage(), ['student_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update student: '.$e->getMessage(),
            ], 500);
        }

        $student = $student->fresh(['parent', 'classroom', 'stream', 'category']);
        $formatted = app(ApiStudentController::class)->serializeStudent($student);

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'message' => 'Student updated.',
        ]);
    }

    protected function generateNextAdmissionNumber(): string
    {
        // One series across the whole system (no padding, e.g. RKS77, RKS729)
        $prefix = Setting::get('student_id_prefix', 'RKS');
        $start = Setting::getInt('student_id_start', 1);
        $counter = Setting::incrementValue('student_id_counter', 1, $start);

        return $prefix.(string) $counter;
    }

    protected function handleParentIdUploads(ParentInfo $parent, Request $request): void
    {
        $updates = [];

        if ($request->hasFile('father_id_document')) {
            if ($parent->father_id_document) {
                storage_private()->delete($parent->father_id_document);
            }
            $updates['father_id_document'] = $request->file('father_id_document')->store('parent_ids', config('filesystems.private_disk', 'private'));
        }

        if ($request->hasFile('mother_id_document')) {
            if ($parent->mother_id_document) {
                storage_private()->delete($parent->mother_id_document);
            }
            $updates['mother_id_document'] = $request->file('mother_id_document')->store('parent_ids', config('filesystems.private_disk', 'private'));
        }

        if (! empty($updates)) {
            $parent->update($updates);
        }
    }
}

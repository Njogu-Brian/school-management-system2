<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\DropOffPoint;
use App\Models\OnlineAdmission;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\Trip;
use App\Services\Admissions\OnlineAdmissionWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Admissions workspace APIs for Admin mobile (Sprint 5).
 *
 * Wraps `online_admissions` queue — mirrors web OnlineAdmissionController index/show.
 */
class ApiAdmissionsController extends Controller
{
    public function stats(Request $request)
    {
        $this->assertAdmissionsAccess($request);

        $statuses = ['pending', 'under_review', 'waitlisted', 'enrolled', 'rejected'];
        $counts = [];
        foreach ($statuses as $status) {
            $counts[$status] = OnlineAdmission::query()
                ->where('application_status', $status)
                ->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pending' => $counts['pending'],
                'under_review' => $counts['under_review'],
                'waitlisted' => $counts['waitlisted'],
                'enrolled' => $counts['enrolled'],
                'rejected' => $counts['rejected'],
                'total' => array_sum($counts),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $this->assertAdmissionsAccess($request);

        $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', 'in:pending,under_review,waitlisted,enrolled,rejected'],
            'search' => ['sometimes', 'string', 'max:120'],
            'waitlist_only' => ['sometimes', 'boolean'],
        ]);

        $perPage = (int) ($request->input('per_page', 25));

        $query = OnlineAdmission::query()
            ->with(['preferredClassroom', 'classroom', 'stream', 'reviewedBy'])
            ->orderByDesc('application_date');

        if ($request->filled('status')) {
            $query->where('application_status', $request->status);
        }

        if ($request->boolean('waitlist_only')) {
            $query->where('application_status', 'waitlisted')
                ->orderBy('waitlist_position');
        }

        if ($request->filled('search')) {
            $term = '%'.trim($request->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('middle_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('father_phone', 'like', $term)
                    ->orWhere('mother_phone', 'like', $term)
                    ->orWhere('guardian_phone', 'like', $term)
                    ->orWhere('father_email', 'like', $term)
                    ->orWhere('mother_email', 'like', $term);
            });
        }

        $paginator = $query->paginate($perPage);

        $rows = collect($paginator->items())
            ->map(fn (OnlineAdmission $a) => $this->serializeListItem($a))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $rows,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    public function show(Request $request, OnlineAdmission $admission)
    {
        $this->assertAdmissionsAccess($request);

        $admission->load(['preferredClassroom', 'classroom', 'stream', 'reviewedBy']);

        return response()->json([
            'success' => true,
            'data' => $this->serializeDetail($admission),
        ]);
    }

    public function updateStatus(Request $request, OnlineAdmission $admission)
    {
        $this->assertAdmissionsAccess($request);
        $this->normalizeStreamId($request);

        $data = $request->validate([
            'application_status' => 'required|in:pending,under_review,rejected,waitlisted',
            'review_notes' => 'nullable|string',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        if (! empty($data['classroom_id'])) {
            $classroom = Classroom::withCount(['streams', 'primaryStreams'])->find($data['classroom_id']);
            $classroomHasStreams = $classroom && (($classroom->streams_count ?? 0) + ($classroom->primary_streams_count ?? 0)) > 0;
            if (! $classroomHasStreams) {
                $data['stream_id'] = null;
            }
        }

        try {
            $admission = app(OnlineAdmissionWorkflowService::class)->updateStatus(
                $admission,
                $data,
                (int) $request->user()->id,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        $admission->load(['preferredClassroom', 'classroom', 'stream', 'reviewedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Application status updated.',
            'data' => $this->serializeDetail($admission),
        ]);
    }

    public function waitlist(Request $request, OnlineAdmission $admission)
    {
        $this->assertAdmissionsAccess($request);

        $data = $request->validate([
            'review_notes' => 'nullable|string',
        ]);

        try {
            $admission = app(OnlineAdmissionWorkflowService::class)->addToWaitlist(
                $admission,
                $data['review_notes'] ?? null,
                (int) $request->user()->id,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        $admission->load(['preferredClassroom', 'classroom', 'stream', 'reviewedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Application added to waitlist.',
            'data' => $this->serializeDetail($admission),
        ]);
    }

    public function reject(Request $request, OnlineAdmission $admission)
    {
        $this->assertAdmissionsAccess($request);

        try {
            $admission = app(OnlineAdmissionWorkflowService::class)->reject(
                $admission,
                (int) $request->user()->id,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        $admission->load(['preferredClassroom', 'classroom', 'stream', 'reviewedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Application rejected.',
            'data' => $this->serializeDetail($admission),
        ]);
    }

    public function enroll(Request $request, OnlineAdmission $admission)
    {
        $this->assertAdmissionsAccess($request);

        if ($request->input('drop_off_point_id') === 'other') {
            $request->merge(['drop_off_point_id' => null]);
        }
        $this->normalizeStreamId($request);

        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'category_id' => 'required|exists:student_categories,id',
            'trip_id' => 'nullable|exists:trips,id',
            'drop_off_point_id' => 'nullable|exists:drop_off_points,id',
            'drop_off_point_other' => 'nullable|string|max:255',
            'transport_fee_amount' => 'nullable|numeric|min:0',
            'has_allergies' => 'nullable|boolean',
            'allergies_notes' => 'nullable|string',
            'is_fully_immunized' => 'nullable|boolean',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => ['nullable', 'string', 'max:80', 'regex:/^[\+]?[\d\s\-\(\)]{4,25}(?:\s+[a-zA-Z\s\-\(\)\.\,]+)?$/'],
            'residential_area' => 'required|string|max:255',
            'preferred_hospital' => 'nullable|string|max:255',
            'marital_status' => 'nullable|in:married,single_parent,co_parenting',
            'enrollment_year' => 'nullable|integer|min:2020|max:2030',
            'enrollment_term' => 'nullable|integer|in:1,2,3',
            'admission_date' => 'nullable|date',
        ]);

        try {
            $student = app(OnlineAdmissionWorkflowService::class)->enroll(
                $admission,
                $validated,
                (int) $request->user()->id,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        $admission->refresh()->load(['preferredClassroom', 'classroom', 'stream', 'reviewedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Student enrolled successfully.',
            'data' => [
                'student' => $this->serializeEnrolledStudent($student),
                'application' => $this->serializeDetail($admission),
            ],
        ]);
    }

    public function downloadFile(Request $request, OnlineAdmission $admission, string $field)
    {
        $this->assertAdmissionsAccess($request);

        $allowed = ['passport_photo', 'birth_certificate', 'father_id_document', 'mother_id_document'];
        abort_unless(in_array($field, $allowed, true), 404);

        $path = $admission->{$field};
        abort_unless(filled($path), 404);

        if ($field === 'passport_photo' && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path, basename($path));
        }

        $disk = storage_private();
        if ($disk->exists($path)) {
            return $disk->download($path, basename($path));
        }

        abort(404, 'File not found.');
    }

    protected function normalizeStreamId(Request $request): void
    {
        $streamId = $request->input('stream_id');
        if ($streamId === '' || $streamId === null || ! is_numeric($streamId) || (int) $streamId < 1) {
            $request->merge(['stream_id' => null]);
        }
    }

    protected function serializeEnrolledStudent(Student $student): array
    {
        $photoUrl = null;
        if ($student->photo_path && Storage::disk('public')->exists($student->photo_path)) {
            $photoUrl = Storage::disk('public')->url($student->photo_path);
        }

        return [
            'id' => $student->id,
            'admission_number' => $student->admission_number,
            'full_name' => $student->full_name ?? trim($student->first_name.' '.$student->last_name),
            'class_name' => $student->classroom?->name,
            'stream_name' => $student->stream?->name,
            'classroom_id' => $student->classroom_id,
            'stream_id' => $student->stream_id,
            'gender' => $student->gender,
            'status' => $student->status,
            'photo_url' => $photoUrl,
        ];
    }

    protected function assertAdmissionsAccess(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            return;
        }

        if ($user->can('admissions.view') || $user->getAllPermissions()->contains('name', 'admissions.view')) {
            return;
        }

        abort(403, 'You do not have access to admissions.');
    }

    protected function serializeListItem(OnlineAdmission $a): array
    {
        return [
            'id' => $a->id,
            'first_name' => $a->first_name,
            'middle_name' => $a->middle_name,
            'last_name' => $a->last_name,
            'full_name' => trim(implode(' ', array_filter([$a->first_name, $a->middle_name, $a->last_name]))),
            'dob' => $a->dob?->toDateString(),
            'gender' => $a->gender,
            'application_status' => $a->application_status,
            'application_date' => $a->application_date?->toDateString(),
            'application_source' => $a->application_source,
            'enrolled' => (bool) $a->enrolled,
            'waitlist_position' => $a->waitlist_position,
            'preferred_classroom_id' => $a->preferred_classroom_id,
            'preferred_class_name' => $a->preferredClassroom?->name,
            'classroom_id' => $a->classroom_id,
            'class_name' => $a->classroom?->name,
            'stream_id' => $a->stream_id,
            'stream_name' => $a->stream?->name,
            'passport_photo_url' => $this->resolvePassportUrl($a),
            'reviewed_by_name' => $a->reviewedBy?->name,
            'review_date' => $a->review_date?->toDateString(),
        ];
    }

    protected function serializeDetail(OnlineAdmission $a): array
    {
        $list = $this->serializeListItem($a);

        return array_merge($list, [
            'nemis_number' => $a->nemis_number,
            'knec_assessment_number' => $a->knec_assessment_number,
            'marital_status' => $a->marital_status,
            'residential_area' => $a->residential_area,
            'preferred_hospital' => $a->preferred_hospital,
            'previous_school' => $a->previous_school,
            'transfer_reason' => $a->transfer_reason,
            'application_notes' => $a->application_notes,
            'review_notes' => $a->review_notes,
            'reviewed_by_id' => $a->reviewed_by,
            'has_allergies' => (bool) $a->has_allergies,
            'allergies_notes' => $a->allergies_notes,
            'is_fully_immunized' => (bool) $a->is_fully_immunized,
            'emergency_contact_name' => $a->emergency_contact_name,
            'emergency_contact_phone' => $a->emergency_contact_phone,
            'transport_needed' => (bool) $a->transport_needed,
            'drop_off_point_id' => $a->drop_off_point_id,
            'drop_off_point_other' => $a->drop_off_point_other,
            'trip_id' => $a->trip_id,
            'father' => $this->serializeParentSide('father', $a),
            'mother' => $this->serializeParentSide('mother', $a),
            'guardian' => $this->serializeParentSide('guardian', $a),
            'documents' => $this->serializeDocuments($a),
            'timeline' => $this->buildTimeline($a),
            'enrollment' => $this->serializeEnrollment($a),
        ]);
    }

    protected function serializeParentSide(string $side, OnlineAdmission $a): array
    {
        $prefix = $side;

        return [
            'name' => $a->{$prefix.'_name'},
            'phone' => $a->{$prefix.'_phone'},
            'phone_country_code' => $a->{$prefix.'_phone_country_code'},
            'email' => $a->{$prefix.'_email'},
            'id_number' => $a->{$prefix.'_id_number'},
            'relationship' => $side === 'guardian' ? $a->guardian_relationship : null,
        ];
    }

    protected function serializeDocuments(OnlineAdmission $a): array
    {
        $defs = [
            ['field' => 'passport_photo', 'label' => 'Passport photo', 'path' => $a->passport_photo, 'public' => true],
            ['field' => 'birth_certificate', 'label' => 'Birth certificate', 'path' => $a->birth_certificate, 'public' => false],
            ['field' => 'father_id_document', 'label' => 'Father ID document', 'path' => $a->father_id_document, 'public' => false],
            ['field' => 'mother_id_document', 'label' => 'Mother ID document', 'path' => $a->mother_id_document, 'public' => false],
        ];

        return collect($defs)->map(function (array $def) use ($a) {
            $uploaded = filled($def['path']);
            $viewUrl = null;
            if ($uploaded && $def['public']) {
                $viewUrl = $this->resolvePassportUrl($a);
            }

            return [
                'field' => $def['field'],
                'label' => $def['label'],
                'uploaded' => $uploaded,
                'view_url' => $viewUrl,
                'download_path' => $uploaded ? "/admissions/{$a->id}/files/{$def['field']}" : null,
                'is_private' => ! $def['public'],
            ];
        })->values()->all();
    }

    protected function buildTimeline(OnlineAdmission $a): array
    {
        $events = [];

        if ($a->application_date) {
            $events[] = [
                'id' => 'submitted',
                'type' => 'submitted',
                'title' => 'Application submitted',
                'description' => 'Source: '.($a->application_source ?: 'online'),
                'occurred_on' => $a->application_date->toDateString(),
                'sort_key' => $a->application_date->format('Y-m-d H:i:s'),
            ];
        }

        if ($a->review_date) {
            $reviewer = $a->reviewedBy?->name;
            $events[] = [
                'id' => 'review',
                'type' => 'review',
                'title' => 'Review recorded',
                'description' => trim(
                    'Status: '.($a->application_status ?? '—')
                    .($reviewer ? " · Reviewer: {$reviewer}" : '')
                    .($a->review_notes ? " · {$a->review_notes}" : '')
                ),
                'occurred_on' => $a->review_date->toDateString(),
                'sort_key' => $a->review_date->format('Y-m-d H:i:s'),
            ];
        }

        if ($a->application_status === 'waitlisted' && $a->waitlist_position) {
            $events[] = [
                'id' => 'waitlist',
                'type' => 'waitlisted',
                'title' => 'Added to waitlist',
                'description' => 'Position #'.$a->waitlist_position,
                'occurred_on' => $a->review_date?->toDateString() ?? $a->application_date?->toDateString(),
                'sort_key' => ($a->review_date ?? $a->application_date)?->format('Y-m-d H:i:s') ?? '',
            ];
        }

        if ($a->application_status === 'rejected') {
            $events[] = [
                'id' => 'rejected',
                'type' => 'rejected',
                'title' => 'Application rejected',
                'description' => $a->review_notes,
                'occurred_on' => $a->review_date?->toDateString() ?? $a->application_date?->toDateString(),
                'sort_key' => ($a->review_date ?? $a->application_date)?->format('Y-m-d H:i:s') ?? '',
            ];
        }

        if ($a->enrolled || $a->application_status === 'enrolled') {
            $events[] = [
                'id' => 'enrolled',
                'type' => 'enrolled',
                'title' => 'Enrolled as student',
                'description' => 'Application marked enrolled',
                'occurred_on' => $a->review_date?->toDateString() ?? $a->application_date?->toDateString(),
                'sort_key' => ($a->review_date ?? $a->application_date)?->format('Y-m-d H:i:s') ?? '',
            ];
        }

        usort($events, fn ($a, $b) => strcmp($a['sort_key'], $b['sort_key']));

        return array_map(fn ($e) => [
            'id' => $e['id'],
            'type' => $e['type'],
            'title' => $e['title'],
            'description' => $e['description'],
            'occurred_on' => $e['occurred_on'],
        ], $events);
    }

    protected function serializeEnrollment(OnlineAdmission $a): array
    {
        $currentYear = function_exists('get_current_academic_year')
            ? (get_current_academic_year() ?? (int) date('Y'))
            : (int) date('Y');
        $currentTerm = function_exists('get_current_term_number')
            ? (get_current_term_number() ?? 1)
            : 1;

        $termOptions = [
            ['year' => $currentYear, 'term' => $currentTerm, 'label' => "Term {$currentTerm} {$currentYear} (Current)"],
        ];
        for ($t = $currentTerm + 1; $t <= 3; $t++) {
            $termOptions[] = ['year' => $currentYear, 'term' => $t, 'label' => "Term {$t} {$currentYear}"];
        }
        $nextYear = $currentYear + 1;
        for ($t = 1; $t <= 3; $t++) {
            $termOptions[] = ['year' => $nextYear, 'term' => $t, 'label' => "Term {$t} {$nextYear}"];
        }

        $categories = StudentCategory::query()->orderBy('name')->get(['id', 'name'])->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
        ]);

        return [
            'enrolled' => (bool) $a->enrolled,
            'application_status' => $a->application_status,
            'can_enroll' => ! $a->enrolled && $a->application_status !== 'rejected',
            'preferred_classroom_id' => $a->preferred_classroom_id,
            'preferred_class_name' => $a->preferredClassroom?->name,
            'classroom_id' => $a->classroom_id,
            'class_name' => $a->classroom?->name,
            'stream_id' => $a->stream_id,
            'stream_name' => $a->stream?->name,
            'transport_needed' => (bool) $a->transport_needed,
            'drop_off_point_id' => $a->drop_off_point_id,
            'drop_off_point_other' => $a->drop_off_point_other,
            'trip_id' => $a->trip_id,
            'enrollment_term_options' => $termOptions,
            'student_categories' => $categories,
            'classrooms' => Classroom::query()->orderBy('name')->get(['id', 'name'])->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ]),
            'drop_off_points' => DropOffPoint::query()->orderBy('name')->get(['id', 'name'])->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ]),
            'trips' => Trip::query()->orderBy('trip_name')->get(['id', 'trip_name'])->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->trip_name,
            ]),
        ];
    }

    protected function resolvePassportUrl(OnlineAdmission $a): ?string
    {
        if (! $a->passport_photo) {
            return null;
        }

        if (Storage::disk('public')->exists($a->passport_photo)) {
            return Storage::disk('public')->url($a->passport_photo);
        }

        return null;
    }
}

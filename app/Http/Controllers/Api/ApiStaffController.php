<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\StaffProfileChange;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffCategory;
use App\Models\StaffLeaveBalance;
use App\Models\SalaryStructure;
use App\Services\Academics\StaffTeachingAssignmentReleaseService;
use App\Services\Academics\TeacherAssignmentService;
use App\Services\PhoneNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class ApiStaffController extends Controller
{
    protected function assertStaffReadAccess(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            abort(403, 'You do not have permission to view staff.');
        }
    }

    /**
     * View own HR profile, admin/secretary, or supervised staff (senior teacher).
     */
    protected function assertCanViewStaffRecord(Request $request, int $staffId): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            return;
        }
        if ($user->staff && (int) $user->staff->id === $staffId) {
            return;
        }
        if ($user->isSeniorTeacherUser() && $user->isSupervisingStaff($staffId)) {
            return;
        }
        abort(403, 'You do not have permission to view this staff profile.');
    }

    protected function assertStaffManageAccess(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            abort(403, 'You do not have permission to manage staff.');
        }
    }

    public function index(Request $request)
    {
        $this->assertStaffReadAccess($request);

        $perPage = (int) $request->input('per_page', 20);

        $query = Staff::with(['supervisor', 'category', 'department', 'jobTitle', 'user.roles'])
            ->where('status', 'active');

        if ($request->filled('search') || $request->filled('q')) {
            $search = '%' . addcslashes($request->search ?? $request->q, '%_\\') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('middle_name', 'like', $search)
                    ->orWhere('work_email', 'like', $search)
                    ->orWhere('phone_number', 'like', $search)
                    ->orWhere('staff_id', 'like', $search);
            });
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('staff_category_id')) {
            $query->where('staff_category_id', $request->staff_category_id);
        }
        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->employment_status);
        }
        if ($request->filled('gender')) {
            $gender = strtolower(trim((string) $request->gender));
            $query->whereRaw('LOWER(gender) = ?', [$gender]);
        }
        if ($request->filled('role')) {
            $roleName = trim((string) $request->role);
            $query->whereHas('user.roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }

        $paginated = $query->orderBy('first_name')->paginate($perPage);

        $data = $paginated->getCollection()->map(fn ($s) => $this->formatStaff($s))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    /**
     * Filter options for staff directory (departments, categories, roles, enums).
     */
    public function filterOptions(Request $request)
    {
        $this->assertStaffReadAccess($request);

        $departments = Department::orderBy('name')->get(['id', 'name'])->map(fn ($d) => [
            'id' => $d->id,
            'name' => $d->name,
        ])->values();

        $categories = StaffCategory::orderBy('name')->get(['id', 'name'])->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
        ])->values();

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'departments' => $departments,
                'categories' => $categories,
                'roles' => $roles,
                'employment_statuses' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'on_leave', 'label' => 'On leave'],
                    ['value' => 'suspended', 'label' => 'Suspended'],
                    ['value' => 'terminated', 'label' => 'Terminated'],
                ],
                'genders' => [
                    ['value' => 'male', 'label' => 'Male'],
                    ['value' => 'female', 'label' => 'Female'],
                    ['value' => 'other', 'label' => 'Other'],
                ],
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $this->assertCanViewStaffRecord($request, (int) $id);

        $staff = Staff::with(['supervisor', 'category', 'department', 'jobTitle', 'statutoryExemptions', 'user.roles'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->formatStaffDetail($staff)]);
    }

    /**
     * Leave balances for active academic year (Staff 360 Leave / Overview tabs).
     */
    public function leaveBalances(Request $request, $id)
    {
        $this->assertCanViewStaffRecord($request, (int) $id);

        $staff = Staff::findOrFail($id);
        $currentYear = AcademicYear::where('is_active', true)->first();

        $query = StaffLeaveBalance::with(['leaveType', 'academicYear'])
            ->where('staff_id', $staff->id);

        if ($currentYear) {
            $query->where('academic_year_id', $currentYear->id);
        }

        $balances = $query->orderBy('leave_type_id')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'staff_id' => $staff->id,
                'academic_year' => $currentYear ? [
                    'id' => $currentYear->id,
                    'name' => $currentYear->name,
                ] : null,
                'balances' => $balances->map(fn ($b) => $this->formatLeaveBalance($b))->values(),
            ],
        ]);
    }

    /**
     * Attendance history with date range, summary, and pagination (Staff 360 Attendance tab).
     */
    public function attendanceHistory(Request $request, $id)
    {
        $this->assertCanViewStaffRecord($request, (int) $id);

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth()->toDateString();
        $perPage = (int) ($validated['per_page'] ?? 30);

        $baseQuery = StaffAttendance::where('staff_id', (int) $id)
            ->whereBetween('date', [$startDate, $endDate]);

        $summary = [
            'total' => (clone $baseQuery)->count(),
            'present' => (clone $baseQuery)->where('status', 'present')->count(),
            'absent' => (clone $baseQuery)->where('status', 'absent')->count(),
            'late' => (clone $baseQuery)->where('status', 'late')->count(),
            'half_day' => (clone $baseQuery)->where('status', 'half_day')->count(),
        ];

        $paginated = (clone $baseQuery)->orderByDesc('date')->paginate($perPage);
        $staff = Staff::find((int) $id);

        return response()->json([
            'success' => true,
            'data' => [
                'staff' => $staff ? [
                    'id' => $staff->id,
                    'full_name' => $staff->full_name,
                ] : null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'summary' => $summary,
                'history' => [
                    'data' => $paginated->getCollection()
                        ->map(fn ($r) => $this->formatAttendanceRow($r))
                        ->values(),
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'from' => $paginated->firstItem(),
                    'to' => $paginated->lastItem(),
                ],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $staff = Staff::with('user')->findOrFail($id);
        $actor = $request->user();
        $isAdmin = $actor && $actor->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        $isSelf = $actor && $actor->staff && (int) $actor->staff->id === (int) $id;

        if (! $isAdmin && ! $isSelf) {
            abort(403, 'You do not have permission to update this staff profile.');
        }

        $user = $staff->user;

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Staff has no linked user account.',
            ], 422);
        }

        if ($isSelf && ! $isAdmin) {
            $request->validate([
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'personal_email' => 'nullable|email',
                'id_number' => 'sometimes|required|string|max:255',
                'phone_number' => 'sometimes|required|string|max:50',
                'residential_address' => 'nullable|string|max:500',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_relationship' => 'nullable|string|max:100',
                'emergency_contact_phone' => 'nullable|string|max:50',
                'bank_name' => 'nullable|string|max:255',
                'bank_branch' => 'nullable|string|max:255',
                'bank_account' => 'nullable|string|max:255',
                'kra_pin' => 'nullable|string|max:50',
                'nssf' => 'nullable|string|max:50',
                'nhif' => 'nullable|string|max:50',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|string|max:20',
            ]);
        } else {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'work_email' => [
                    'required', 'email',
                    Rule::unique('users', 'email')->ignore($user->id),
                    Rule::unique('staff', 'work_email')->ignore($staff->id),
                ],
                'personal_email' => 'nullable|email',
                'id_number' => ['required', 'string', 'max:255', Rule::unique('staff', 'id_number')->ignore($staff->id)],
                'phone_number' => 'required|string|max:50',
                'department_id' => 'nullable|exists:departments,id',
                'job_title_id' => 'nullable|exists:job_titles,id',
                'staff_category_id' => 'nullable|exists:staff_categories,id',
                'supervisor_id' => 'nullable|exists:staff,id',
                'residential_address' => 'nullable|string|max:500',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_relationship' => 'nullable|string|max:100',
                'emergency_contact_phone' => 'nullable|string|max:50',
                'bank_name' => 'nullable|string|max:255',
                'bank_branch' => 'nullable|string|max:255',
                'bank_account' => 'nullable|string|max:255',
                'kra_pin' => 'nullable|string|max:50',
                'nssf' => 'nullable|string|max:50',
                'nhif' => 'nullable|string|max:50',
                'basic_salary' => 'nullable|numeric|min:0',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|string|max:20',
            ]);
        }

        if ($isAdmin && $request->filled('supervisor_id') && (int) $request->supervisor_id === (int) $staff->id) {
            return response()->json([
                'success' => false,
                'message' => 'A staff member cannot be their own supervisor.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $phoneService = app(PhoneNumberService::class);

            if ($isSelf && ! $isAdmin) {
                $interesting = [
                    'personal_email', 'phone_number', 'id_number', 'residential_address',
                    'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone',
                    'kra_pin', 'nssf', 'nhif', 'bank_name', 'bank_branch', 'bank_account',
                    'date_of_birth', 'gender',
                ];
                $proposed = [];
                foreach ($interesting as $field) {
                    if (! $request->has($field)) {
                        continue;
                    }
                    $value = $request->input($field);
                    if (in_array($field, ['phone_number', 'emergency_contact_phone'], true)) {
                        $value = $phoneService->formatWithCountryCode($value, '+254');
                    }
                    if ($field === 'gender' && $value !== null) {
                        $value = strtolower(trim((string) $value));
                    }
                    $proposed[$field] = $value === '' ? null : $value;
                }

                $changes = [];
                foreach ($proposed as $field => $new) {
                    $old = $staff->{$field};
                    if ($field === 'date_of_birth') {
                        $old = $old ? ($old instanceof \Carbon\Carbon ? $old->format('Y-m-d') : $old) : null;
                    }
                    if (($old ?? null) != ($new ?? null)) {
                        $changes[$field] = ['old' => $old, 'new' => $new];
                    }
                }

                if (empty($changes)) {
                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'No changes detected.',
                        'data' => $this->formatStaffDetail($staff->load(['supervisor', 'category', 'department', 'jobTitle'])),
                    ]);
                }

                StaffProfileChange::create([
                    'staff_id' => $staff->id,
                    'submitted_by' => $actor->id,
                    'changes' => $changes,
                    'status' => 'pending',
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Your changes were submitted and are pending admin approval.',
                    'data' => $this->formatStaffDetail($staff->load(['supervisor', 'category', 'department', 'jobTitle'])),
                ]);
            }

            $user->update(['email' => $request->work_email]);

            $staffData = $request->only([
                'first_name', 'middle_name', 'last_name',
                'work_email', 'personal_email', 'id_number',
                'department_id', 'job_title_id', 'supervisor_id', 'staff_category_id',
                'residential_address',
                'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone',
                'kra_pin', 'nssf', 'nhif', 'bank_name', 'bank_branch', 'bank_account',
                'date_of_birth', 'gender',
            ]);
            $staffData['phone_number'] = $phoneService->formatWithCountryCode($request->phone_number, '+254');
            $staffData['emergency_contact_phone'] = $phoneService->formatWithCountryCode(
                $request->input('emergency_contact_phone'),
                '+254'
            );

            if ($isAdmin && $request->filled('basic_salary')) {
                $staffData['basic_salary'] = $request->basic_salary;
            }

            $staff->update($staffData);

            if ($isAdmin && $request->filled('basic_salary')) {
                SalaryStructure::updateOrCreate(
                    [
                        'staff_id' => $staff->id,
                        'is_active' => true,
                    ],
                    [
                        'basic_salary' => $request->basic_salary,
                        'housing_allowance' => 0,
                        'transport_allowance' => 0,
                        'medical_allowance' => 0,
                        'other_allowances' => 0,
                        'nssf_deduction' => 0,
                        'nhif_deduction' => 0,
                        'paye_deduction' => 0,
                        'other_deductions' => 0,
                        'effective_from' => now()->startOfMonth(),
                        'is_active' => true,
                        'created_by' => auth()->id(),
                    ]
                )->calculateTotals()->save();
            }

            DB::commit();
            $staff->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Staff updated successfully.',
                'data' => $this->formatStaffDetail($staff->load(['supervisor', 'category', 'department', 'jobTitle'])),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('API staff update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Could not update staff: '.$e->getMessage(),
            ], 422);
        }
    }

    public function uploadPhoto(Request $request, $id)
    {
        $actor = $request->user();
        $isAdmin = $actor && $actor->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        $isSelf = $actor && $actor->staff && (int) $actor->staff->id === (int) $id;
        if (! $isAdmin && ! $isSelf) {
            abort(403, 'You do not have permission to update this photo.');
        }

        $staff = Staff::findOrFail($id);

        $request->validate([
            'photo' => 'required|image|max:5120',
        ]);

        if ($staff->photo && storage_public()->exists($staff->photo)) {
            storage_public()->delete($staff->photo);
        }

        $path = $request->file('photo')->store('staff_photos', config('filesystems.public_disk', 'public'));
        $staff->update(['photo' => $path]);
        $staff->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Photo updated.',
            'data' => [
                'avatar' => $staff->photo_url,
            ],
        ]);
    }

    protected function formatStaff(Staff $s): array
    {
        $systemRole = $s->relationLoaded('user') && $s->user
            ? $s->user->roles->first()?->name
            : null;

        return [
            'id' => $s->id,
            'staff_id' => $s->staff_id ?? '',
            'employee_number' => $s->staff_id ?? '',
            'first_name' => $s->first_name ?? '',
            'last_name' => $s->last_name ?? '',
            'middle_name' => $s->middle_name,
            'full_name' => $s->full_name ?? trim(($s->first_name ?? '').' '.($s->last_name ?? '')),
            'work_email' => $s->work_email,
            'personal_email' => $s->personal_email,
            'phone' => $s->phone_number,
            'phone_number' => $s->phone_number,
            'designation' => $s->jobTitle->name ?? null,
            'job_title' => $s->jobTitle->name ?? null,
            'role' => $systemRole,
            'system_role' => $systemRole,
            'department' => $s->department->name ?? null,
            'department_id' => $s->department_id,
            'staff_category_id' => $s->staff_category_id,
            'staff_category' => $s->category?->name,
            'employment_status' => $s->employment_status,
            'gender' => $s->gender,
            'status' => $s->status ?? 'active',
            'avatar' => $s->photo_url,
            'created_at' => $s->created_at->toIso8601String(),
            'updated_at' => $s->updated_at->toIso8601String(),
        ];
    }

    protected function formatStaffDetail(Staff $s): array
    {
        $base = $this->formatStaff($s);

        return array_merge($base, [
            'id_number' => $s->id_number,
            'marital_status' => $s->marital_status,
            'residential_address' => $s->residential_address,
            'emergency_contact_name' => $s->emergency_contact_name,
            'emergency_contact_relationship' => $s->emergency_contact_relationship,
            'emergency_contact_phone' => $s->emergency_contact_phone,
            'bank_name' => $s->bank_name,
            'bank_branch' => $s->bank_branch,
            'bank_account' => $s->bank_account,
            'kra_pin' => $s->kra_pin,
            'nssf' => $s->nssf,
            'nhif' => $s->nhif,
            'statutory_exemptions' => $s->statutoryExemptionCodes(),
            'basic_salary' => $s->basic_salary !== null ? (float) $s->basic_salary : null,
            'gender' => $s->gender,
            'date_of_birth' => $s->date_of_birth?->format('Y-m-d'),
            'hire_date' => $s->hire_date?->format('Y-m-d'),
            'termination_date' => $s->termination_date?->format('Y-m-d'),
            'employment_status' => $s->employment_status,
            'employment_type' => $s->employment_type,
            'contract_start_date' => $s->contract_start_date?->format('Y-m-d'),
            'contract_end_date' => $s->contract_end_date?->format('Y-m-d'),
            'max_lessons_per_week' => $s->max_lessons_per_week !== null ? (int) $s->max_lessons_per_week : null,
            'department_id' => $s->department_id,
            'job_title_id' => $s->job_title_id,
            'staff_category_id' => $s->staff_category_id,
            'staff_category' => $s->category?->name,
            'supervisor_id' => $s->supervisor_id,
            'supervisor_name' => $s->supervisor ? $s->supervisor->full_name : null,
        ]);
    }

    protected function formatLeaveBalance(StaffLeaveBalance $balance): array
    {
        return [
            'id' => $balance->id,
            'leave_type_id' => $balance->leave_type_id,
            'leave_type_name' => $balance->leaveType?->name,
            'leave_type_code' => $balance->leaveType?->code,
            'academic_year_id' => $balance->academic_year_id,
            'entitlement_days' => (int) $balance->entitlement_days,
            'used_days' => (int) $balance->used_days,
            'remaining_days' => (int) $balance->remaining_days,
            'carried_forward' => (int) $balance->carried_forward,
        ];
    }

    protected function formatAttendanceRow(StaffAttendance $record): array
    {
        $hasClock = $record->check_in_time !== null
            || $record->check_in_latitude !== null
            || $record->check_out_time !== null;

        return [
            'id' => $record->id,
            'staff_id' => $record->staff_id,
            'date' => $record->date ? Carbon::parse($record->date)->toDateString() : null,
            'status' => $record->status,
            'check_in_time' => $record->check_in_time
                ? Carbon::parse($record->check_in_time)->format('H:i')
                : null,
            'check_out_time' => $record->check_out_time
                ? Carbon::parse($record->check_out_time)->format('H:i')
                : null,
            'check_in_distance_meters' => $record->check_in_distance_meters,
            'check_out_distance_meters' => $record->check_out_distance_meters,
            'notes' => $record->notes,
            'marked_by' => $record->marked_by,
            'source' => $hasClock ? 'clock' : 'manual',
        ];
    }

    /**
     * GET /api/staff/{id}/archive-preview
     */
    public function archivePreview(Request $request, int $id)
    {
        $this->assertStaffManageAccess($request);
        $staff = Staff::with('user.roles')->findOrFail($id);

        $releaseService = app(StaffTeachingAssignmentReleaseService::class);
        $summary = $releaseService->summarize($id);

        $replacementCandidates = Staff::with('user')
            ->where('status', 'active')
            ->where('id', '!=', $id)
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', TeacherAssignmentService::TEACHER_ROLE_NAMES))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (Staff $s) => [
                'id' => $s->id,
                'full_name' => $s->full_name,
                'staff_id' => $s->staff_id,
            ]);

        return response()->json([
            'data' => [
                'staff_id' => $staff->id,
                'staff_name' => $staff->full_name,
                'status' => $staff->status,
                'assignments' => $summary,
                'replacement_candidates' => $replacementCandidates,
            ],
        ]);
    }

    /**
     * POST /api/staff/{id}/archive
     */
    public function archive(Request $request, int $id)
    {
        $this->assertStaffManageAccess($request);
        $staff = Staff::findOrFail($id);

        if ($staff->status === 'archived') {
            return response()->json(['message' => 'Staff member is already archived.'], 422);
        }

        $request->validate([
            'assignment_action' => 'required|in:leave_blank,transfer',
            'replacement_staff_id' => 'nullable|integer|exists:staff,id|different:' . $id,
        ]);

        if ($request->assignment_action === 'transfer' && ! $request->filled('replacement_staff_id')) {
            return response()->json(['message' => 'replacement_staff_id is required when transferring assignments.'], 422);
        }

        $replacementId = $request->assignment_action === 'transfer'
            ? (int) $request->replacement_staff_id
            : null;

        $releaseService = app(StaffTeachingAssignmentReleaseService::class);
        $result = $releaseService->release($id, $replacementId);
        $staff->update(['status' => 'archived']);

        return response()->json([
            'message' => 'Staff archived successfully.',
            'data' => [
                'staff_id' => $staff->id,
                'transferred' => $result['transferred'],
                'released' => $result['summary'],
            ],
        ]);
    }
}

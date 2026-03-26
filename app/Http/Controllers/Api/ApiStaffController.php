<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\SalaryStructure;
use App\Services\PhoneNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ApiStaffController extends Controller
{
    protected function assertStaffReadAccess(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            abort(403, 'You do not have permission to view staff.');
        }
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

        $query = Staff::with(['supervisor', 'category', 'department', 'jobTitle'])
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

    public function show(Request $request, $id)
    {
        $this->assertStaffReadAccess($request);

        $staff = Staff::with(['supervisor', 'category', 'department', 'jobTitle', 'statutoryExemptions'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->formatStaffDetail($staff)]);
    }

    public function update(Request $request, $id)
    {
        $this->assertStaffManageAccess($request);

        $staff = Staff::with('user')->findOrFail($id);
        $user = $staff->user;

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Staff has no linked user account.',
            ], 422);
        }

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

        if ($request->filled('supervisor_id') && (int) $request->supervisor_id === (int) $staff->id) {
            return response()->json([
                'success' => false,
                'message' => 'A staff member cannot be their own supervisor.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $user->update(['email' => $request->work_email]);

            $phoneService = app(PhoneNumberService::class);
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

            if ($request->filled('basic_salary')) {
                $staffData['basic_salary'] = $request->basic_salary;
            }

            $staff->update($staffData);

            if ($request->filled('basic_salary')) {
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
        $this->assertStaffManageAccess($request);

        $staff = Staff::findOrFail($id);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png|max:2048',
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
            'role' => $s->jobTitle->name ?? null,
            'department' => $s->department->name ?? null,
            'job_title' => $s->jobTitle->name ?? null,
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
}

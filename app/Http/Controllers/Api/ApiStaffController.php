<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;

class ApiStaffController extends Controller
{
    public function index(Request $request)
    {
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

        $data = $paginated->getCollection()->map(fn($s) => $this->formatStaff($s))->values();

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

    public function show($id)
    {
        $staff = Staff::with(['supervisor', 'category', 'department', 'jobTitle'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $this->formatStaff($staff)]);
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
            'full_name' => $s->full_name ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
            'work_email' => $s->work_email,
            'personal_email' => $s->personal_email,
            'phone' => $s->phone_number,
            'phone_number' => $s->phone_number,
            'designation' => $s->jobTitle->name ?? null,
            'role' => $s->jobTitle->name ?? null,
            'department' => $s->department->name ?? null,
            'job_title' => $s->jobTitle->name ?? null,
            'status' => $s->status ?? 'active',
            'avatar' => method_exists($s, 'getPhotoUrlAttribute') ? $s->photo_url : null,
            'created_at' => $s->created_at->toIso8601String(),
            'updated_at' => $s->updated_at->toIso8601String(),
        ];
    }
}

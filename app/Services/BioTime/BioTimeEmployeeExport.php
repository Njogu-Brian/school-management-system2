<?php

namespace App\Services\BioTime;

use App\Models\Staff;
use App\Services\BioTime\BioTimeEmployeeMapper;

class BioTimeEmployeeExport
{
    /**
     * @return list<array<string, mixed>>
     */
    public function activeEmployees(): array
    {
        return Staff::query()
            ->where('status', 'active')
            ->where('biometric_exempt', false)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'staff_id', 'biometric_emp_code', 'first_name', 'middle_name', 'last_name', 'status', 'department_id', 'updated_at'])
            ->map(fn (Staff $staff) => $this->toPayload($staff))
            ->filter(fn (array $row) => filled($row['emp_code']))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(Staff $staff): array
    {
        $empCode = BioTimeEmployeeMapper::normalize($staff->biometric_emp_code)
            ?: BioTimeEmployeeMapper::normalize(BioTimeEmployeeMapper::suggestedEmpCode($staff->staff_id) ?? '');

        return [
            'erp_staff_id' => (int) $staff->id,
            'staff_id' => $staff->staff_id,
            'emp_code' => $empCode,
            'first_name' => $staff->first_name,
            'last_name' => trim(collect([$staff->middle_name, $staff->last_name])->filter()->implode(' ')),
            'status' => $staff->status,
            'department_id' => $staff->department_id,
            'updated_at' => optional($staff->updated_at)->toIso8601String(),
        ];
    }
}

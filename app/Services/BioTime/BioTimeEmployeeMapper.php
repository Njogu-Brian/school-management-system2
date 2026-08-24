<?php

namespace App\Services\BioTime;

use App\Models\Staff;

class BioTimeEmployeeMapper
{
    /** @var array<string, int> */
    private array $map = [];

    public function refresh(): void
    {
        $this->map = [];
        $staffRows = Staff::query()->get(['id', 'staff_id', 'biometric_emp_code']);

        foreach ($staffRows as $staff) {
            foreach ([self::normalize($staff->staff_id), self::suggestedEmpCode($staff->staff_id)] as $raw) {
                $code = self::normalize((string) $raw);
                if ($code !== '' && ! isset($this->map[$code])) {
                    $this->map[$code] = (int) $staff->id;
                }
            }
        }

        foreach ($staffRows as $staff) {
            $code = self::normalize($staff->biometric_emp_code);
            if ($code !== '') {
                $this->map[$code] = (int) $staff->id;
            }
        }
    }

    public function staffIdForEmpCode(?string $empCode): ?int
    {
        $code = self::normalize($empCode);
        if ($code === '') {
            return null;
        }
        if ($this->map === []) {
            $this->refresh();
        }

        return $this->map[$code] ?? null;
    }

    /**
     * @return list<string>
     */
    public function codesFor(Staff $staff): array
    {
        $codes = [];
        foreach ([$staff->biometric_emp_code, $staff->staff_id, self::suggestedEmpCode($staff->staff_id)] as $raw) {
            $code = self::normalize((string) $raw);
            if ($code !== '') {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }

    public static function suggestedEmpCode(?string $staffId): ?string
    {
        if ($staffId === null || trim($staffId) === '') {
            return null;
        }
        if (preg_match('/(\d+)\s*$/', $staffId, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    public static function normalize(?string $empCode): string
    {
        return trim((string) $empCode);
    }
}

<?php

namespace App\Services\Hr\PayrollExports;

use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

final class NssfExport
{
    public function export(PayrollPeriod $period, string $disk = 'local'): ExportResult
    {
        $period->loadMissing('records.staff');

        $rows = $this->buildRows($period->records);

        $exportable = new class($rows) implements FromArray, WithHeadings, WithTitle {
            public function __construct(private array $rows) {}
            public function title(): string { return 'Sheet1'; }
            public function headings(): array
            {
                return ['PAYROLL NUMBER', 'SURNAME', 'OTHER NAMES', 'ID NO', 'KRA PIN', 'NSSF NO', 'GROSS PAY', 'VOLUNTARY'];
            }
            public function array(): array { return $this->rows; }
        };

        $filename = sprintf('NSSF %s.xlsx', $period->period_name ?? $period->name);
        $path = sprintf('payroll/exports/%d/%s', $period->id, $this->safeFilename($filename));

        Storage::disk($disk)->makeDirectory(dirname($path));
        Excel::store($exportable, $path, $disk);

        $sha = ExportUtil::sha256ForDiskPath($disk, $path);

        return new ExportResult(
            disk: $disk,
            path: $path,
            filename: $filename,
            sha256: $sha,
            meta: [
                'row_count' => count($rows),
                'template' => 'NSSF MAY 2026.xlsx',
            ],
        );
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function buildRows(Collection $records): array
    {
        return $records
            ->filter(fn (PayrollRecord $r) => (float) $r->nssf_deduction > 0)
            ->map(function (PayrollRecord $r) {
                $staff = $r->staff;
                $surname = $staff?->last_name ?: null;
                $otherNames = trim((string) (($staff?->first_name ?? '') . ' ' . ($staff?->middle_name ?? '')));
                $otherNames = $otherNames !== '' ? $otherNames : ($staff?->first_name ?? null);

                return [
                    $staff?->staff_id,           // PAYROLL NUMBER
                    $surname,                    // SURNAME
                    $otherNames,                 // OTHER NAMES
                    $staff?->id_number,          // ID NO
                    $staff?->kra_pin,            // KRA PIN
                    $staff?->nssf,               // NSSF NO
                    (float) $r->gross_salary,    // GROSS PAY
                    null,                        // VOLUNTARY
                ];
            })
            ->values()
            ->all();
    }

    private function safeFilename(string $name): string
    {
        $name = preg_replace('/[\\\\\\/\\:\\*\\?\"\\<\\>\\|]+/', '-', $name) ?? $name;
        $name = preg_replace('/\\s+/', ' ', $name) ?? $name;
        return trim($name);
    }
}


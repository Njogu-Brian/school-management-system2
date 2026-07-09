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

final class ShifExport
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
                // From SHIF MAY 2026.xlsx
                return [
                    'PAYROLL NUMBER',
                    'FIRSTNAME',
                    'LASTNAME',
                    'IDENTITY TYPE',
                    'ID NO',
                    'KRA PIN',
                    'NHIF NO',
                    'CONTRIBUTION AMOUNT',
                    'PHONE',
                ];
            }
            public function array(): array { return $this->rows; }
        };

        $filename = sprintf('SHIF %s.xlsx', $period->period_name ?? $period->name);
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
                'template' => 'SHIF MAY 2026.xlsx',
            ],
        );
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function buildRows(Collection $records): array
    {
        return $records
            ->filter(fn (PayrollRecord $r) => ((float) $r->shif_deduction) > 0)
            ->map(function (PayrollRecord $r) {
                $staff = $r->staff;
                $first = $staff?->first_name ?: null;
                $last = $staff?->last_name ?: null;
                $idNo = $staff?->id_number ?: null;
                $phone = $staff?->phone_number ?: null;

                return [
                    $staff?->staff_id,          // PAYROLL NUMBER
                    $first ? strtoupper($first) : $first,
                    $last ? strtoupper($last) : $last,
                    'National ID',              // IDENTITY TYPE (matches your template rows)
                    $idNo,
                    $staff?->kra_pin,
                    $staff?->nhif,              // NHIF NO (used by SHIF portal template)
                    (float) $r->shif_deduction, // CONTRIBUTION AMOUNT
                    $phone,
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


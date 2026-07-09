<?php

namespace App\Services\Hr\PayrollExports;

use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * KRA PAYE CSV export based on the provided "KRA CSV MAY.xlsx" template.
 *
 * We generate CSV (not XLSB/XLSX) because KRA upload commonly accepts CSV.
 * Column meaning is derived from the template's positional columns A..Y.
 */
final class KraPayeCsvExport
{
    public function export(PayrollPeriod $period, string $disk = 'local'): ExportResult
    {
        $period->loadMissing('records.staff');

        $rows = $this->buildRows($period->records);

        $filename = sprintf('KRA PAYE %s.csv', $period->period_name ?? $period->name);
        $path = sprintf('payroll/exports/%d/%s', $period->id, $this->safeFilename($filename));

        Storage::disk($disk)->makeDirectory(dirname($path));

        $full = Storage::disk($disk)->path($path);
        $fh = fopen($full, 'wb');
        if (! $fh) {
            throw new \RuntimeException('Failed to open export file for writing.');
        }

        // Template file has no header row; it's raw data rows.
        foreach ($rows as $row) {
            fputcsv($fh, $row);
        }
        fclose($fh);

        $sha = ExportUtil::sha256ForDiskPath($disk, $path);

        return new ExportResult(
            disk: $disk,
            path: $path,
            filename: $filename,
            sha256: $sha,
            meta: [
                'row_count' => count($rows),
                'template' => 'KRA CSV MAY.xlsx',
            ],
        );
    }

    /**
     * Positional mapping (A..Y) from the provided template.
     *
     * @return array<int, array<int, mixed>>
     */
    private function buildRows(Collection $records): array
    {
        return $records
            ->filter(fn (PayrollRecord $r) => (float) $r->paye_deduction > 0)
            ->map(function (PayrollRecord $r) {
                $staff = $r->staff;

                // Observed from template:
                // A: KRA PIN
                // B: Name
                // C: Resident
                // D: Primary Employee
                // E: No
                // F: blank
                // G: Gross?
                // H..J: zeros
                // K: Benefit not given
                // L: blank
                // M: 0
                // N: blank
                // O: SHIF amount (template values match SHIF)
                // P: NSSF (template values match NSSF/kids fees? but aligns with NSSF deductions)
                // Q..S: zeros
                // T: Housing levy (matches 1.5% gross)
                // U: blank
                // V: Personal relief (2400)
                // W: 0
                // X: blank
                // Y: PAYE (shows 1853.13 for Dickson)
                $gross = (float) $r->gross_salary;
                $shif = (float) $r->shif_deduction;
                $nssf = (float) $r->nssf_deduction;
                $housing = (float) $r->housing_levy_deduction;
                $paye = (float) $r->paye_deduction;

                $name = trim((string) ($staff?->full_name ?? ''));
                $name = $name !== '' ? $name : (string) ($staff?->first_name ?? '');

                return [
                    $staff?->kra_pin,           // A
                    $name,                      // B
                    'Resident',                 // C
                    'Primary Employee',         // D
                    'No',                       // E
                    '',                         // F
                    $this->fmt($gross),         // G
                    '0',                        // H
                    '0',                        // I
                    '0',                        // J
                    'Benefit not given',        // K
                    '',                         // L
                    '0',                        // M
                    '',                         // N
                    $this->fmt($shif),          // O
                    $this->fmt($nssf),          // P
                    '0',                        // Q
                    '0',                        // R
                    '0',                        // S
                    $this->fmt($housing),       // T
                    '',                         // U
                    $this->fmt(2400.0),         // V (personal relief monthly)
                    '0',                        // W
                    '',                         // X
                    $this->fmt($paye),          // Y
                ];
            })
            ->values()
            ->all();
    }

    private function fmt(float $v): string
    {
        // Keep as plain number string (no thousands separators), matching typical CSV uploads.
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }

    private function safeFilename(string $name): string
    {
        $name = preg_replace('/[\\\\\\/\\:\\*\\?\"\\<\\>\\|]+/', '-', $name) ?? $name;
        $name = preg_replace('/\\s+/', ' ', $name) ?? $name;
        return trim($name);
    }
}


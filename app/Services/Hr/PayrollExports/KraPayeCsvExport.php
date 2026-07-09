<?php

namespace App\Services\Hr\PayrollExports;

use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * KRA P10 simplified CSV (columns A..Y), matching "KRA CSV MAY 2026.csv".
 *
 * A  KRA PIN
 * B  Employee name
 * C  Resident
 * D  Primary Employee
 * E  No
 * F  blank
 * G  Gross salary
 * H..J  0
 * K  Benefit not given
 * L  blank
 * M  0
 * N  blank
 * O  SHIF
 * P  NSSF
 * Q..S  0
 * T  Housing levy
 * U  blank
 * V  Personal relief (2400)
 * W  0
 * X  blank
 * Y  PAYE tax payable
 *
 * Include every payroll row for staff with a KRA PIN (even when PAYE is 0
 * because personal relief wiped the charge).
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
                'template' => 'KRA CSV MAY 2026.csv / P10_Return_Simplified',
            ],
        );
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function buildRows(Collection $records): array
    {
        $personalRelief = 2400.0;

        return $records
            ->filter(function (PayrollRecord $r) {
                $pin = trim((string) ($r->staff?->kra_pin ?? ''));

                return $pin !== '';
            })
            ->sortBy(fn (PayrollRecord $r) => strtoupper((string) ($r->staff?->full_name ?? '')))
            ->map(function (PayrollRecord $r) use ($personalRelief) {
                $staff = $r->staff;
                $name = trim((string) ($staff?->full_name ?? ''));
                if ($name === '') {
                    $name = trim((string) ($staff?->first_name ?? '').' '.(string) ($staff?->last_name ?? ''));
                }

                return [
                    strtoupper(trim((string) $staff->kra_pin)), // A
                    $name,                                      // B
                    'Resident',                                 // C
                    'Primary Employee',                         // D
                    'No',                                       // E
                    '',                                         // F
                    $this->fmt((float) $r->gross_salary),       // G
                    '0',                                        // H
                    '0',                                        // I
                    '0',                                        // J
                    'Benefit not given',                        // K
                    '',                                         // L
                    '0',                                        // M
                    '',                                         // N
                    $this->fmt((float) ($r->shif_deduction ?? 0)), // O
                    $this->fmt((float) $r->nssf_deduction),     // P
                    '0',                                        // Q
                    '0',                                        // R
                    '0',                                        // S
                    $this->fmt((float) ($r->housing_levy_deduction ?? 0)), // T
                    '',                                         // U
                    $this->fmt($personalRelief),                // V
                    '0',                                        // W
                    '',                                         // X
                    $this->fmt((float) $r->paye_deduction),     // Y
                ];
            })
            ->values()
            ->all();
    }

    private function fmt(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') ?: '0';
    }

    private function safeFilename(string $name): string
    {
        $name = preg_replace('/[\\\\\\/\\:\\*\\?\"\\<\\>\\|]+/', '-', $name) ?? $name;
        $name = preg_replace('/\\s+/', ' ', $name) ?? $name;

        return trim($name);
    }
}

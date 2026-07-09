<?php

namespace App\Services\Hr\PayrollExports;

use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use Illuminate\Support\Facades\Storage;

final class ImBankSalaryUploadExport
{
    public function export(PayrollPeriod $period, string $disk = 'local'): ExportResult
    {
        $templatePath = (string) config('payroll_exports.imbank_template_path');
        if (! $templatePath || ! is_file($templatePath)) {
            throw new \RuntimeException('Missing IM bank template. Set IMBANK_SALARY_UPLOAD_TEMPLATE_PATH in .env');
        }

        $period->loadMissing('records.staff');

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
        $sheet = $spreadsheet->getSheet(0); // Template

        $periodLabel = strtoupper((string) ($period->period_name ?? $period->name));
        $description = $this->salaryDescription($period);

        $rows = $this->buildRows($period);

        // Header area (based on observed template structure)
        $sheet->setCellValue('D2', "SALARY {$periodLabel}");
        $sheet->setCellValue('D4', optional($period->pay_date)->format('d-M-Y') ?? now()->format('d-M-Y'));
        $sheet->setCellValue('G4', number_format($rows['total_amount'], 2, '.', ','));
        $sheet->setCellValue('G5', (string) $rows['record_count']);

        // Table header is at row 8; data starts at row 9
        $startRow = 9;
        foreach ($rows['items'] as $idx => $item) {
            $r = $startRow + $idx;
            $sheet->setCellValueExplicit("A{$r}", (string) ($item['employee_number'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("B{$r}", (string) ($item['employee_name'] ?? ''));
            $sheet->setCellValueExplicit("C{$r}", (string) ($item['account_number'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("D{$r}", (string) ($item['bank_name_or_code'] ?? ''));
            $sheet->setCellValue("E{$r}", number_format((float) ($item['amount'] ?? 0), 2, '.', ','));
            $sheet->setCellValue("F{$r}", $description);
            $sheet->setCellValue("G{$r}", (string) ($item['payment_mode'] ?? 'PesaLink'));
            $sheet->setCellValue("H{$r}", (string) ($item['branch_name_or_code'] ?? ''));
        }

        $filename = sprintf('Salary Upload IMBank %s.xls', $periodLabel);
        $path = sprintf('payroll/exports/%d/%s', $period->id, $this->safeFilename($filename));

        Storage::disk($disk)->makeDirectory(dirname($path));

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
        $full = Storage::disk($disk)->path($path);
        $writer->save($full);

        $sha = ExportUtil::sha256ForDiskPath($disk, $path);

        return new ExportResult(
            disk: $disk,
            path: $path,
            filename: $filename,
            sha256: $sha,
            meta: [
                'record_count' => $rows['record_count'],
                'total_amount' => $rows['total_amount'],
                'template_source' => $templatePath,
                'payment_description' => $description,
            ],
        );
    }

    private function salaryDescription(PayrollPeriod $period): string
    {
        // IM Bank template says max 50 chars; keep it short and consistent.
        $label = strtoupper((string) ($period->period_name ?? $period->name));
        $label = preg_replace('/\\s+/', ' ', $label) ?? $label;
        $label = trim($label);
        $out = "SALARY {$label}";
        return mb_substr($out, 0, 50);
    }

    /**
     * @return array{record_count:int,total_amount:float,items:array<int,array<string,mixed>>}
     */
    private function buildRows(PayrollPeriod $period): array
    {
        $items = [];
        $total = 0.0;

        foreach ($period->records as $record) {
            if (! $record instanceof PayrollRecord) {
                continue;
            }
            $staff = $record->staff;
            if (! $staff) {
                continue;
            }

            $amount = round((float) $record->net_salary, 2);
            if ($amount <= 0) {
                continue;
            }

            $account = $staff->bank_account ?: null;
            $bank = $staff->bank_name ?: null;

            $items[] = [
                'employee_number' => $staff->staff_id ?: (string) $staff->id,
                'employee_name' => strtoupper(trim($staff->full_name)),
                'account_number' => $account,
                'bank_name_or_code' => $bank,
                'amount' => $amount,
                'payment_mode' => $this->defaultPaymentMode($bank),
                'branch_name_or_code' => $staff->bank_branch ?: null,
            ];

            $total += $amount;
        }

        return [
            'record_count' => count($items),
            'total_amount' => round($total, 2),
            'items' => $items,
        ];
    }

    private function defaultPaymentMode(?string $bankNameOrCode): string
    {
        $s = strtolower((string) $bankNameOrCode);
        if (str_contains($s, 'i & m') || str_contains($s, 'i&m') || str_contains($s, 'i and m')) {
            return 'Within I&M';
        }
        return 'PesaLink';
    }

    private function safeFilename(string $name): string
    {
        $name = preg_replace('/[\\\\\\/\\:\\*\\?\"\\<\\>\\|]+/', '-', $name) ?? $name;
        $name = preg_replace('/\\s+/', ' ', $name) ?? $name;
        return trim($name);
    }
}


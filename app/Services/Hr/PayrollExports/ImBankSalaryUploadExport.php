<?php

namespace App\Services\Hr\PayrollExports;

use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ImBankSalaryUploadExport
{
    public function export(PayrollPeriod $period, string $disk = 'local'): ExportResult
    {
        $period->loadMissing('records.staff');

        $periodLabel = strtoupper((string) ($period->period_name ?? $period->name));
        $description = $this->salaryDescription($period);
        $rows = $this->buildRows($period);

        $spreadsheet = $this->loadOrCreateSpreadsheet();
        $sheet = $spreadsheet->getSheet(0);
        $sheet->setTitle('Template');

        $this->writeHeader($sheet, $period, $periodLabel, $rows);
        $this->writeDataRows($sheet, $rows['items'], $description);

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
                'excluded_mpesa_count' => $rows['excluded_mpesa_count'],
                'excluded_mpesa_total' => $rows['excluded_mpesa_total'],
                'template_source' => (string) (config('payroll_exports.imbank_template_path') ?: 'generated'),
                'payment_description' => $description,
            ],
        );
    }

    private function loadOrCreateSpreadsheet(): Spreadsheet
    {
        $templatePath = (string) config('payroll_exports.imbank_template_path');
        if ($templatePath && is_file($templatePath)) {
            return \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'I&M Bank Salary Upload');
        $sheet->setCellValue('A2', 'Batch Title');
        $sheet->setCellValue('A4', 'Value Date');
        $sheet->setCellValue('F4', 'Total Amount');
        $sheet->setCellValue('F5', 'Record Count');

        $headers = [
            'A8' => 'Employee Number',
            'B8' => 'Employee Name',
            'C8' => 'Account Number',
            'D8' => 'Bank Name/Code',
            'E8' => 'Amount',
            'F8' => 'Payment Description',
            'G8' => 'Payment Mode',
            'H8' => 'Branch Name/Code',
        ];
        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        return $spreadsheet;
    }

    /**
     * @param  array{record_count:int,total_amount:float,items:array<int,array<string,mixed>>}  $rows
     */
    private function writeHeader(Worksheet $sheet, PayrollPeriod $period, string $periodLabel, array $rows): void
    {
        $sheet->setCellValue('D2', "SALARY {$periodLabel}");
        $sheet->setCellValue('D4', optional($period->pay_date)->format('d-M-Y') ?? now()->format('d-M-Y'));
        $sheet->setCellValue('G4', number_format($rows['total_amount'], 2, '.', ','));
        $sheet->setCellValue('G5', (string) $rows['record_count']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function writeDataRows(Worksheet $sheet, array $items, string $description): void
    {
        $startRow = 9;
        foreach ($items as $idx => $item) {
            $r = $startRow + $idx;
            $sheet->setCellValueExplicit("A{$r}", (string) ($item['employee_number'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("B{$r}", (string) ($item['employee_name'] ?? ''));
            $sheet->setCellValueExplicit("C{$r}", (string) ($item['account_number'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("D{$r}", (string) ($item['bank_name_or_code'] ?? ''));
            $sheet->setCellValue("E{$r}", number_format((float) ($item['amount'] ?? 0), 2, '.', ','));
            $sheet->setCellValue("F{$r}", $description);
            $sheet->setCellValue("G{$r}", (string) ($item['payment_mode'] ?? 'PesaLink'));
            $sheet->setCellValue("H{$r}", (string) ($item['branch_name_or_code'] ?? ''));
        }
    }

    private function salaryDescription(PayrollPeriod $period): string
    {
        $label = strtoupper((string) ($period->period_name ?? $period->name));
        $label = preg_replace('/\s+/', ' ', $label) ?? $label;
        $label = trim($label);
        $out = "SALARY {$label}";

        return mb_substr($out, 0, 50);
    }

    /**
     * @return array{record_count:int,total_amount:float,excluded_mpesa_count:int,excluded_mpesa_total:float,items:array<int,array<string,mixed>>}
     */
    private function buildRows(PayrollPeriod $period): array
    {
        $items = [];
        $total = 0.0;
        $excludedMpesaCount = 0;
        $excludedMpesaTotal = 0.0;

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
            $paymentMethod = strtolower((string) ($staff->payment_method ?? ''));

            $isMpesa = $paymentMethod === 'mpesa' || ! $account || trim((string) $account) === '';
            if ($isMpesa) {
                $excludedMpesaCount++;
                $excludedMpesaTotal += $amount;
                continue;
            }

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
            'excluded_mpesa_count' => $excludedMpesaCount,
            'excluded_mpesa_total' => round($excludedMpesaTotal, 2),
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
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return trim($name);
    }
}

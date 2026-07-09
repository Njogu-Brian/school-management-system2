<?php

namespace App\Services\Hr\PayrollExports;

use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use Illuminate\Support\Facades\Storage;

final class MpesaPayoutCsvExport
{
    public function export(PayrollPeriod $period, string $disk = 'local'): ExportResult
    {
        $period->loadMissing('records.staff');

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

            $account = trim((string) ($staff->bank_account ?? ''));
            $paymentMethod = strtolower((string) ($staff->payment_method ?? ''));
            $isMpesa = $paymentMethod === 'mpesa' || $account === '';
            if (! $isMpesa) {
                continue; // bank staff => not MPESA
            }

            $phone = trim((string) ($staff->phone_number ?? ''));
            $items[] = [
                $staff->staff_id ?: $staff->id,
                $staff->full_name,
                $phone,
                $this->fmt($amount),
            ];
            $total += $amount;
        }

        $filename = sprintf('MPESA Payout %s.csv', $period->period_name ?? $period->name);
        $path = sprintf('payroll/exports/%d/%s', $period->id, $this->safeFilename($filename));

        Storage::disk($disk)->makeDirectory(dirname($path));
        $full = Storage::disk($disk)->path($path);

        $fh = fopen($full, 'wb');
        if (! $fh) {
            throw new \RuntimeException('Failed to open export file for writing.');
        }

        fputcsv($fh, ['STAFF_NO', 'NAME', 'PHONE', 'AMOUNT']);
        foreach ($items as $row) {
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
                'row_count' => count($items),
                'total_amount' => round($total, 2),
                'selection_rule' => 'bank_account_missing => mpesa',
            ],
        );
    }

    private function fmt(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }

    private function safeFilename(string $name): string
    {
        $name = preg_replace('/[\\\\\\/\\:\\*\\?\"\\<\\>\\|]+/', '-', $name) ?? $name;
        $name = preg_replace('/\\s+/', ' ', $name) ?? $name;
        return trim($name);
    }
}


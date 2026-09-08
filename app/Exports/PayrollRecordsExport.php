<?php

namespace App\Exports;

use App\Models\PayrollRecord;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollRecordsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private Collection $records)
    {
    }

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'Period',
            'Pay Date',
            'Staff Name',
            'Staff ID',
            'Basic Salary',
            'Gross Salary',
            'Total Deductions',
            'Net Salary',
            'Status',
        ];
    }

    public function map($record): array
    {
        /** @var PayrollRecord $record */
        return [
            $record->payrollPeriod->period_name ?? '',
            optional($record->payrollPeriod?->pay_date)->format('Y-m-d') ?? '',
            $record->staff->name ?? ('Staff #'.$record->staff_id),
            $record->staff->staff_id ?? '',
            (float) $record->basic_salary,
            (float) $record->gross_salary,
            (float) $record->total_deductions,
            (float) $record->net_salary,
            ucfirst($record->status),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Payroll Records';
    }
}

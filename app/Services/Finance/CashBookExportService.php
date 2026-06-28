<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Vendor;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Builds an Excel "cash book" that mirrors the manual EXPENSES workbook:
 *  - Sheet1: one row per expense, grouped into month blocks, with the amount
 *    echoed under its category column and a per-month TOTAL row.
 *  - Summary: category x month pivot.
 *
 * Sourced live from the accounting system (expenses + expense lines).
 */
class CashBookExportService
{
    /** Spreadsheet category columns, in display order. */
    public const COLUMNS = [
        'FOOD', 'Stationary', 'Textbooks', 'EXAMS', 'Uniform', 'Communication',
        'Office Exp', 'Water', 'General repair', 'Furniture', 'Medical',
        'Service provider', 'Fuel', 'NTSA', 'Trash', 'Colnet', 'Construction',
        'Vehicle Repairs', 'Transport', 'Electricity', 'Labour', 'Donation',
        'Advance tax', 'Insurance', 'Lisence', 'Assets', 'LOAN', 'Salary',
        'Valuation & Inspection', 'ACTIVITIES', 'NSSF', 'SHA', 'PAYE', 'NITA',
        'Housing', 'Rent',
    ];

    /** DB category code => spreadsheet column. */
    public const CODE_TO_COLUMN = [
        'FUEL' => 'Fuel',
        'VEH-REPAIRS' => 'Vehicle Repairs', 'VEH-SERVICE' => 'Vehicle Repairs',
        'SPEED-GOVERNOR' => 'Vehicle Repairs', 'VEH-TRACKING' => 'Vehicle Repairs',
        'VEH-INSURANCE' => 'Insurance',
        'VEH-INSPECTION' => 'Valuation & Inspection', 'VEH-VALUATION' => 'Valuation & Inspection',
        'LAND-VALUATION' => 'Valuation & Inspection',
        'VEH-LOGBOOK' => 'NTSA', 'NTSA' => 'NTSA',
        'CAR-HIRE' => 'Transport', 'TRANSPORT' => 'Transport',
        'VEH-ADVANCE-TAX' => 'Advance tax', 'ADVANCE-TAX' => 'Advance tax',
        'VEH-PURCHASE' => 'Assets', 'ASSETS' => 'Assets', 'ADMIN-COMPUTER-EQUIPMENT' => 'Assets',
        'SALARIES' => 'Salary', 'WAGES' => 'Salary', 'STAFF' => 'Salary',
        'MEDICAL' => 'Medical',
        'PAYE' => 'PAYE', 'NSSF' => 'NSSF', 'NHIF' => 'SHA', 'HOUSING' => 'Housing', 'NITA' => 'NITA',
        'LOANS' => 'LOAN', 'LOAN-EQUITY-8659' => 'LOAN', 'LOAN-EQUITY-2564' => 'LOAN',
        'LOAN-EQUITY-986' => 'LOAN', 'LOAN-EQUITY-7419' => 'LOAN', 'LOAN-IM-BANK' => 'LOAN',
        'LOAN-FAMILY-BANK' => 'LOAN', 'LOAN-JACKFRUIT' => 'LOAN', 'LOAN-ED-PARTNERS' => 'LOAN',
        'LOANS-TCL-CREDIT' => 'LOAN',
        'ELECTRICITY' => 'Electricity', 'WATER' => 'Water',
        'INTERNET' => 'Communication', 'WIFI' => 'Communication', 'COMMUNICATION' => 'Communication',
        'TRASH' => 'Trash', 'SANITARY' => 'Colnet',
        'OFFICE' => 'Office Exp', 'ADMIN' => 'Office Exp', 'AUDIT-FEE' => 'Office Exp',
        'STATIONERY' => 'Stationary', 'LICENSE' => 'Lisence',
        'RENT' => 'Rent', 'DONATION' => 'Donation', 'FURNITURE' => 'Furniture',
        'TEXTBOOKS' => 'Textbooks', 'EXAM' => 'EXAMS', 'UNIFORM' => 'Uniform',
        'CATERING' => 'FOOD', 'FOOD' => 'FOOD',
        'ACTIVITIES' => 'ACTIVITIES', 'ACT-BALLET' => 'ACTIVITIES', 'ACT-SKATING' => 'ACTIVITIES',
        'ACT-TAEKWONDO' => 'ACTIVITIES', 'ACT-MUSIC' => 'ACTIVITIES', 'ACT-FRENCH' => 'ACTIVITIES',
        'ACTIVITIES-YORGHUT' => 'ACTIVITIES', 'ACTIVITIES-GRADUATION' => 'ACTIVITIES',
        'SCHOOL-TRIPS' => 'ACTIVITIES',
        'CONSTRUCTION' => 'Construction', 'BUILDINGS' => 'Construction',
        'LABOUR-CONSTRUCTION' => 'Labour', 'GENERAL-REPAIRS' => 'General repair',
        'OTHER' => 'Office Exp', 'MISC' => 'Office Exp', 'OTHER-AI-TOOLS' => 'Office Exp',
        'GENERATOR' => 'Fuel',
        // TXN_COST (bank/M-Pesa charges) intentionally omitted (no column).
    ];

    private const SKIP_CODES = ['TXN_COST'];

    private const MONTHS = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

    public function build(int $year): Spreadsheet
    {
        $categories = ExpenseCategory::query()->get(['id', 'code', 'parent_id'])->keyBy('id');
        $vendors = Vendor::query()->pluck('name', 'id');

        $expenses = Expense::query()
            ->with('lines')
            ->whereYear('expense_date', $year)
            ->whereIn('status', [Expense::STATUS_SUBMITTED, Expense::STATUS_APPROVED, Expense::STATUS_PAID])
            ->orderBy('expense_date')
            ->get();

        // entries grouped by month number (1..12)
        $byMonth = array_fill(1, 12, []);
        foreach ($expenses as $expense) {
            $item = $vendors[$expense->vendor_id] ?? null;
            foreach ($expense->lines as $line) {
                $column = $this->columnForCategory($line->category_id, $categories);
                if ($column === null) {
                    continue;
                }
                $amount = (float) $line->line_total;
                if ($amount <= 0) {
                    continue;
                }
                $date = $expense->expense_date;
                $month = (int) $date->format('n');
                $byMonth[$month][] = [
                    'date' => $date,
                    'item' => $item ?: ($line->description ?: 'Expense'),
                    'amount' => $amount,
                    'column' => $column,
                ];
            }
        }

        $spreadsheet = new Spreadsheet();
        $this->buildLedgerSheet($spreadsheet, $byMonth, $year);
        $this->buildSummarySheet($spreadsheet, $byMonth, $year);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function columnForCategory($categoryId, $categories): ?string
    {
        $seen = [];
        while ($categoryId !== null && isset($categories[$categoryId]) && ! isset($seen[$categoryId])) {
            $seen[$categoryId] = true;
            $code = $categories[$categoryId]->code;
            if (in_array($code, self::SKIP_CODES, true)) {
                return null;
            }
            if (isset(self::CODE_TO_COLUMN[$code])) {
                return self::CODE_TO_COLUMN[$code];
            }
            $categoryId = $categories[$categoryId]->parent_id;
        }

        return null;
    }

    private function buildLedgerSheet(Spreadsheet $spreadsheet, array $byMonth, int $year): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cash Book ' . $year);

        $headers = array_merge(['DATE', 'Voucher No.', 'ITEM', 'AMOUNT'], self::COLUMNS);
        $lastColIndex = count($headers);
        $colLetterOf = fn (int $i) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
        $columnIndex = [];
        foreach (self::COLUMNS as $i => $name) {
            $columnIndex[$name] = 5 + $i; // category columns start at column E (5)
        }

        $row = 1;
        for ($m = 1; $m <= 12; $m++) {
            $entries = $byMonth[$m];
            if (empty($entries)) {
                continue;
            }

            // Month header row
            foreach ($headers as $i => $h) {
                $sheet->setCellValueByColumnAndRow($i + 1, $row, $h);
            }
            $sheet->getStyle("A{$row}:{$colLetterOf($lastColIndex)}{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:{$colLetterOf($lastColIndex)}{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');
            $headerRow = $row;
            $row++;

            $firstDataRow = $row;
            $seq = 0;
            foreach ($entries as $e) {
                $seq++;
                $sheet->setCellValueByColumnAndRow(1, $row, $e['date']->format('Y-m-d'));
                $sheet->setCellValueByColumnAndRow(2, $row, self::MONTHS[$m - 1] . str_pad((string) $seq, 3, '0', STR_PAD_LEFT));
                $sheet->setCellValueByColumnAndRow(3, $row, $e['item']);
                $sheet->setCellValueByColumnAndRow(4, $row, $e['amount']);
                $sheet->setCellValueByColumnAndRow($columnIndex[$e['column']], $row, $e['amount']);
                $row++;
            }
            $lastDataRow = $row - 1;

            // TOTAL row
            $sheet->setCellValueByColumnAndRow(1, $row, 'TOTAL');
            for ($c = 4; $c <= $lastColIndex; $c++) {
                $letter = $colLetterOf($c);
                $sheet->setCellValue("{$letter}{$row}", "=SUM({$letter}{$firstDataRow}:{$letter}{$lastDataRow})");
            }
            $sheet->getStyle("A{$row}:{$colLetterOf($lastColIndex)}{$row}")->getFont()->setBold(true);
            $row += 2; // blank spacer between months
        }

        // Number formatting + widths
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(34);
        $sheet->getStyle('D1:' . $colLetterOf($lastColIndex) . max(1, $row))->getNumberFormat()->setFormatCode('#,##0');
    }

    private function buildSummarySheet(Spreadsheet $spreadsheet, array $byMonth, int $year): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Summary');

        // totals[column][month] = amount
        $totals = [];
        foreach (self::COLUMNS as $col) {
            $totals[$col] = array_fill(1, 12, 0.0);
        }
        foreach ($byMonth as $m => $entries) {
            foreach ($entries as $e) {
                $totals[$e['column']][$m] += $e['amount'];
            }
        }

        $sheet->setCellValue('A1', "EXPENSES SUMMARY YEAR {$year}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        // Header: category + 12 months + total
        $sheet->setCellValue('A2', 'Category');
        foreach (self::MONTHS as $i => $mon) {
            $sheet->setCellValueByColumnAndRow($i + 2, 2, $mon);
        }
        $sheet->setCellValueByColumnAndRow(14, 2, 'TOTAL');
        $sheet->getStyle('A2:N2')->getFont()->setBold(true);
        $sheet->getStyle('A2:N2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E1F2');

        $row = 3;
        foreach (self::COLUMNS as $col) {
            $sheet->setCellValueByColumnAndRow(1, $row, $col);
            $rowTotal = 0.0;
            foreach (range(1, 12) as $m) {
                $val = $totals[$col][$m];
                if ($val) {
                    $sheet->setCellValueByColumnAndRow($m + 1, $row, $val);
                }
                $rowTotal += $val;
            }
            $sheet->setCellValueByColumnAndRow(14, $row, $rowTotal ?: null);
            $row++;
        }

        // Grand total row
        $sheet->setCellValueByColumnAndRow(1, $row, 'GRAND TOTAL');
        for ($c = 2; $c <= 14; $c++) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet->setCellValue("{$letter}{$row}", "=SUM({$letter}3:{$letter}" . ($row - 1) . ')');
        }
        $sheet->getStyle("A{$row}:N{$row}")->getFont()->setBold(true);

        $sheet->getColumnDimension('A')->setWidth(24);
        foreach (range(2, 14) as $c) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c))->setWidth(12);
        }
        $sheet->getStyle('B3:N' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A2:N2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}

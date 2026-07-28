<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class SchoolCreditDebitNotesExport implements WithMultipleSheets
{
    public function __construct(
        protected array $detailRows,
        protected array $summaryRows,
        protected string $detailTitle = 'All Notes',
        protected string $summaryTitle = 'By Student & Votehead',
    ) {}

    public function sheets(): array
    {
        return [
            new class($this->detailRows, $this->detailTitle) implements FromArray, WithHeadings, WithTitle {
                public function __construct(protected array $rows, protected string $title) {}

                public function array(): array
                {
                    return $this->rows;
                }

                public function headings(): array
                {
                    return [
                        'Type',
                        'Note #',
                        'Student',
                        'Admission #',
                        'Class',
                        'Stream',
                        'Votehead',
                        'Invoice #',
                        'Amount',
                        'Reason',
                        'Issued Date',
                        'Issued By',
                    ];
                }

                public function title(): string
                {
                    return $this->title;
                }
            },
            new class($this->summaryRows, $this->summaryTitle) implements FromArray, WithHeadings, WithTitle {
                public function __construct(protected array $rows, protected string $title) {}

                public function array(): array
                {
                    return $this->rows;
                }

                public function headings(): array
                {
                    return [
                        'Student',
                        'Admission #',
                        'Class',
                        'Stream',
                        'Votehead',
                        'Credit Total',
                        'Debit Total',
                        'Net Adjustment',
                    ];
                }

                public function title(): string
                {
                    return $this->title;
                }
            },
        ];
    }
}

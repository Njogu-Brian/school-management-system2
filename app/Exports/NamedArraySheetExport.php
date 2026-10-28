<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class NamedArraySheetExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly array $rows,
        private readonly array $headers,
        private readonly string $title,
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }
}

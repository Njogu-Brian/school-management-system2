<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FeeVarianceWorkbookExport implements WithMultipleSheets
{
    /**
     * @param  list<array{title: string, headers: array, rows: array}>  $sheets
     */
    public function __construct(private readonly array $sheets) {}

    public function sheets(): array
    {
        return array_map(
            fn (array $sheet) => new NamedArraySheetExport(
                $sheet['rows'] ?? [],
                $sheet['headers'] ?? [],
                $sheet['title'] ?? 'Sheet'
            ),
            $this->sheets
        );
    }
}

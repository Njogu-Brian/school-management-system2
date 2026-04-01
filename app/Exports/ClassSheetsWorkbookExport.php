<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClassSheetsWorkbookExport implements WithMultipleSheets
{
    /**
     * @param  list<array{title: string, payload: array}>  $sheets
     */
    public function __construct(
        private readonly array $sheets
    ) {}

    public function sheets(): array
    {
        $out = [];
        foreach ($this->sheets as $i => $item) {
            $title = $item['title'] ?? ('Sheet '.($i + 1));
            $payload = $item['payload'] ?? [];
            $out[] = new ClassSheetExport($payload, $title);
        }

        return $out;
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PosProductTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        // Uniform-only manual POS template (no SKU / barcode).
        return [
            'name',
            'color',
            'selling_price',
            'buying_price',
            'sizes',
            'class_names',
            'track_stock',
            'is_active',
        ];
    }
}




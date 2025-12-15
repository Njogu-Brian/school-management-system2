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
        return [
            'name',
            'sku',
            'barcode',
            'type',
            'category',
            'brand',
            'description',
            'base_price',
            'cost_price',
            'stock_quantity',
            'min_stock_level',
            'track_stock',
            'allow_backorders',
            'is_active',
            'is_featured',
        ];
    }
}




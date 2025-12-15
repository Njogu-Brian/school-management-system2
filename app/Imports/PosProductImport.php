<?php

namespace App\Imports;

use App\Models\Pos\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PosProductImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new Product([
            'name' => $row['name'] ?? $row['product_name'] ?? null,
            'sku' => $row['sku'] ?? null,
            'barcode' => $row['barcode'] ?? null,
            'type' => $row['type'] ?? 'stationery',
            'category' => $row['category'] ?? null,
            'brand' => $row['brand'] ?? null,
            'description' => $row['description'] ?? null,
            'base_price' => $row['base_price'] ?? $row['price'] ?? 0,
            'cost_price' => $row['cost_price'] ?? null,
            'stock_quantity' => $row['stock_quantity'] ?? $row['quantity'] ?? 0,
            'min_stock_level' => $row['min_stock_level'] ?? 0,
            'track_stock' => isset($row['track_stock']) ? (bool)$row['track_stock'] : true,
            'allow_backorders' => isset($row['allow_backorders']) ? (bool)$row['allow_backorders'] : false,
            'is_active' => !isset($row['is_active']) || (bool)$row['is_active'],
            'is_featured' => isset($row['is_featured']) ? (bool)$row['is_featured'] : false,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
            'base_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
        ];
    }
}




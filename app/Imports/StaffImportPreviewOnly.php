<?php
namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class StaffImportPreviewOnly implements ToCollection
{
    public $rows;

    public function collection(Collection $rows)
    {
        $this->rows = $rows;
    }
}
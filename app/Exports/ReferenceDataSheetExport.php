<?php

namespace App\Exports;

use App\Models\Classroom;
use App\Models\Stream;
use App\Models\StudentCategory;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReferenceDataSheetExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        $rows = [];

        $max = max([
            Classroom::count(),
            Stream::count(),
            StudentCategory::count()
        ]);

        for ($i = 0; $i < $max; $i++) {
            $rows[] = [
                Classroom::skip($i)->value('name') ?? '',
                Stream::skip($i)->value('name') ?? '',
                StudentCategory::skip($i)->value('name') ?? '',
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['classrooms', 'streams', 'categories'];
    }
}

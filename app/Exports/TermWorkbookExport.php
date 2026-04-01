<?php

namespace App\Exports;

use App\Models\Academics\Classroom;
use App\Services\Academics\ExamReports\ClassSheetBuilder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TermWorkbookExport implements WithMultipleSheets
{
    public function __construct(
        private readonly int $academicYearId,
        private readonly int $termId,
        /** @var array<int> */
        private readonly array $classroomIds = []
    ) {}

    public function sheets(): array
    {
        $classrooms = Classroom::query()
            ->when(!empty($this->classroomIds), fn ($q) => $q->whereIn('id', $this->classroomIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        $builder = new ClassSheetBuilder();

        $sheets = [];
        foreach ($classrooms as $classroom) {
            $payload = $builder->buildForTerm($this->academicYearId, $this->termId, $classroom, null);
            $sheets[] = new ClassSheetExport($payload, $classroom->name ?? ('Class ' . $classroom->id));
        }

        return $sheets;
    }
}


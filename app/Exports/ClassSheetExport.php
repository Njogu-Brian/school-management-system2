<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClassSheetExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private readonly array $sheetPayload,
        private readonly string $title = 'Class Sheet'
    ) {}

    public function title(): string
    {
        // Excel limits sheet titles to 31 chars
        return mb_substr($this->title, 0, 31);
    }

    public function headings(): array
    {
        $subjects = $this->sheetPayload['subjects'] ?? [];
        $subjectNames = [];
        foreach ($subjects as $s) {
            $label = $s['code'] ? ($s['code'] . ' - ' . $s['name']) : $s['name'];
            $subjectNames[] = $label;
            $subjectNames[] = $label . ' Pos';
        }

        return array_merge(
            ['#', 'Admission No', 'Student Name'],
            $subjectNames,
            ['Total', 'Average', 'Class Pos', 'Stream Pos']
        );
    }

    public function array(): array
    {
        $subjects = $this->sheetPayload['subjects'] ?? [];
        $rows = $this->sheetPayload['rows'] ?? [];

        $out = [];
        foreach ($rows as $idx => $row) {
            $line = [
                $idx + 1,
                $row['admission_number'] ?? '',
                $row['name'] ?? '',
            ];

            foreach ($subjects as $s) {
                $sid = $s['id'];
                $line[] = $row['subject_scores'][$sid] ?? null;
                $line[] = $row['subject_positions'][$sid] ?? null;
            }

            $line[] = $row['total'] ?? null;
            $line[] = $row['average'] ?? null;
            $line[] = $row['class_position'] ?? $row['position'] ?? null;
            $line[] = $row['stream_position'] ?? null;
            $out[] = $line;
        }

        return $out;
    }
}


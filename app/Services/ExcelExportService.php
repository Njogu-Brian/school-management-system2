<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ExcelExportService
{
    /**
     * Export collection to Excel
     */
    public function export(Collection $data, array $headings, string $filename, array $options = [])
    {
        $export = new class($data, $headings, $options) implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle {
            protected $data;
            protected $headings;
            protected $options;

            public function __construct($data, $headings, $options)
            {
                $this->data = $data;
                $this->headings = $headings;
                $this->options = $options;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return $this->headings;
            }

            public function map($row): array
            {
                // Default mapping - can be overridden
                if (isset($this->options['mapping']) && is_callable($this->options['mapping'])) {
                    return call_user_func($this->options['mapping'], $row);
                }
                
                return (array) $row;
            }

            public function styles(Worksheet $sheet)
            {
                return [
                    1 => [
                        'font' => ['bold' => true, 'size' => 12],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E0E0E0']
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ],
                ];
            }

            public function title(): string
            {
                return $this->options['title'] ?? 'Sheet1';
            }
        };

        // Export to storage or download
        if ($options['save'] ?? false) {
            $path = $options['path'] ?? 'exports/' . $filename;
            Excel::store($export, $path, 'public');
            return [
                'success' => true,
                'path' => $path,
                'url' => Storage::url($path),
                'filename' => $filename,
            ];
        }

        return Excel::download($export, $filename);
    }

    /**
     * Export multiple sheets
     */
    public function exportMultiple(array $sheets, string $filename, array $options = [])
    {
        // This would require creating a custom export class with multiple sheets
        // For now, we'll use the single sheet export
        return $this->export(
            collect($sheets[0]['data'] ?? []),
            $sheets[0]['headings'] ?? [],
            $filename,
            array_merge($options, ['title' => $sheets[0]['title'] ?? 'Sheet1'])
        );
    }

    /**
     * Export schemes of work to Excel
     */
    public function exportSchemesOfWork(Collection $schemes, string $filename = null, array $options = [])
    {
        $filename = $filename ?? 'schemes_of_work_' . date('Y-m-d') . '.xlsx';
        
        $headings = [
            'ID', 'Title', 'Subject', 'Classroom', 'Academic Year', 
            'Term', 'Total Lessons', 'Lessons Completed', 'Progress %', 
            'Status', 'Created By', 'Approved By', 'Created At'
        ];

        $data = $schemes->map(function($scheme) {
            return [
                $scheme->id,
                $scheme->title,
                $scheme->subject->name ?? '',
                $scheme->classroom->name ?? '',
                $scheme->academicYear->year ?? '',
                $scheme->term->name ?? '',
                $scheme->total_lessons,
                $scheme->lessons_completed,
                $scheme->progress_percentage . '%',
                ucfirst($scheme->status),
                $scheme->creator->first_name ?? '',
                $scheme->approver->first_name ?? '',
                $scheme->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return $this->export($data, $headings, $filename, array_merge([
            'title' => 'Schemes of Work',
        ], $options));
    }

    /**
     * Export lesson plans to Excel
     */
    public function exportLessonPlans(Collection $lessonPlans, string $filename = null, array $options = [])
    {
        $filename = $filename ?? 'lesson_plans_' . date('Y-m-d') . '.xlsx';
        
        $headings = [
            'ID', 'Title', 'Lesson Number', 'Subject', 'Classroom', 
            'Substrand', 'Planned Date', 'Actual Date', 'Duration (min)', 
            'Status', 'Execution Status', 'Created By', 'Created At'
        ];

        $data = $lessonPlans->map(function($plan) {
            return [
                $plan->id,
                $plan->title,
                $plan->lesson_number,
                $plan->subject->name ?? '',
                $plan->classroom->name ?? '',
                $plan->substrand->name ?? '',
                $plan->planned_date->format('Y-m-d'),
                $plan->actual_date ? $plan->actual_date->format('Y-m-d') : '',
                $plan->duration_minutes,
                ucfirst($plan->status),
                $plan->execution_status ? ucfirst($plan->execution_status) : '',
                $plan->creator->first_name ?? '',
                $plan->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return $this->export($data, $headings, $filename, array_merge([
            'title' => 'Lesson Plans',
        ], $options));
    }

    /**
     * Export competencies to Excel
     */
    public function exportCompetencies(Collection $competencies, string $filename = null, array $options = [])
    {
        $filename = $filename ?? 'competencies_' . date('Y-m-d') . '.xlsx';
        
        $headings = [
            'Code', 'Name', 'Description', 'Learning Area', 'Strand', 
            'Substrand', 'Competency Level', 'Status'
        ];

        $data = $competencies->map(function($competency) {
            return [
                $competency->code,
                $competency->name,
                $competency->description,
                $competency->substrand->strand->learningArea->name ?? '',
                $competency->substrand->strand->name ?? '',
                $competency->substrand->name ?? '',
                $competency->competency_level ?? '',
                $competency->is_active ? 'Active' : 'Inactive',
            ];
        });

        return $this->export($data, $headings, $filename, array_merge([
            'title' => 'Competencies',
        ], $options));
    }
}

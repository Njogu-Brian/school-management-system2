<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class StudentTemplateExport implements FromArray, WithHeadings, WithEvents
{
    protected $rows;
    protected $classrooms;
    protected $streams;
    protected $categories;

    public function __construct($rows, $classrooms, $streams, $categories)
    {
        $this->rows       = $rows;
        $this->classrooms = $classrooms;
        $this->streams    = $streams;
        $this->categories = $categories;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'admission_number', 'first_name', 'middle_name', 'last_name',
            'gender', 'dob', 'classroom', 'stream', 'category',
            'father_name', 'father_phone', 'father_email', 'father_id_number',
            'mother_name', 'mother_phone', 'mother_email', 'mother_id_number',
            'guardian_name', 'guardian_phone', 'guardian_email'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $workbook = $event->sheet->getParent();

                $refSheet = $workbook->getSheetByName('reference_data') ?: $workbook->createSheet();
                $refSheet->setTitle('reference_data');

                foreach ($this->classrooms as $index => $value) {
                    $refSheet->setCellValue('A' . ($index + 1), $value);
                }

                foreach ($this->streams as $index => $value) {
                    $refSheet->setCellValue('B' . ($index + 1), $value);
                }

                foreach ($this->categories as $index => $value) {
                    $refSheet->setCellValue('C' . ($index + 1), $value);
                }

                $classroomRange = 'reference_data!$A$1:$A$' . max(1, count($this->classrooms));
                $streamRange    = 'reference_data!$B$1:$B$' . max(1, count($this->streams));
                $categoryRange  = 'reference_data!$C$1:$C$' . max(1, count($this->categories));

                for ($row = 2; $row <= 100; $row++) {
                    $sheet->getCell("G{$row}")
                        ->getDataValidation()
                        ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                        ->setFormula1($classroomRange)
                        ->setShowDropDown(true);

                    $sheet->getCell("H{$row}")
                        ->getDataValidation()
                        ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                        ->setFormula1($streamRange)
                        ->setShowDropDown(true);

                    $sheet->getCell("I{$row}")
                        ->getDataValidation()
                        ->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                        ->setFormula1($categoryRange)
                        ->setShowDropDown(true);
                }
            },
        ];
    }
}

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
        $this->rows = $rows;
        $this->classroomss = $classrooms;
        $this->streams = $streams;
        $this->categories = $categories;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            // Basic Info
            'admission_number', 'first_name', 'middle_name', 'last_name',
            'gender', 'dob', 'classroom', 'stream', 'category',
            // Identifiers
            'nemis_number', 'knec_assessment_number', 'national_id_number', 'passport_number',
            // Extended Demographics
            'religion', 'ethnicity', 'language_preference', 'blood_group',
            'home_address', 'home_city', 'home_county', 'home_postal_code',
            // Medical
            'allergies', 'chronic_conditions', 'medical_insurance_provider', 'medical_insurance_number',
            'emergency_medical_contact_name', 'emergency_medical_contact_phone',
            // Special Needs
            'has_special_needs', 'special_needs_description', 'learning_disabilities',
            // Previous Schools
            'previous_schools', 'transfer_reason',
            // Status
            'status', 'admission_date',
            // Parent Info
            'father_name', 'father_phone', 'father_email', 'father_id_number',
            'mother_name', 'mother_phone', 'mother_email', 'mother_id_number',
            'guardian_name', 'guardian_phone', 'guardian_email', 'guardian_relationship'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $refSheet = $event->sheet->getParent()->createSheet();
                $refSheet->setTitle('reference_data');

                $i = 1;
                foreach ($this->classroomss as $index => $value) {
                    $refSheet->setCellValue("A" . ($index + 1), $value);
                }
                foreach ($this->streams as $index => $value) {
                    $refSheet->setCellValue("B" . ($index + 1), $value);
                }
                foreach ($this->categories as $index => $value) {
                    $refSheet->setCellValue("C" . ($index + 1), $value);
                }

                // Set dropdowns on main sheet
               for ($row = 2; $row <= 100; $row++) {
                $sheet->getCell("G$row")->getDataValidation()->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                    ->setFormula1('reference_data!$A$1:$A$' . count($this->classroomss))
                    ->setShowDropDown(true);

                $sheet->getCell("H$row")->getDataValidation()->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                    ->setFormula1('reference_data!$B$1:$B$' . count($this->streams))
                    ->setShowDropDown(true);

                $sheet->getCell("I$row")->getDataValidation()->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)
                    ->setFormula1('reference_data!$C$1:$C$' . count($this->categories))
                    ->setShowDropDown(true);
            }
            }
        ];
    }
}

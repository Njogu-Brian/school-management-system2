<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class StudentUpdateTemplateExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected $students;
    protected $classrooms;
    protected $streams;
    protected $categories;

    public function __construct()
    {
        // Get existing students with their data
        $this->students = Student::with(['parent', 'classroom', 'stream', 'category'])
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->orderBy('admission_number')
            ->limit(100) // Limit to first 100 for template
            ->get();
        
        // Get reference data for dropdowns
        $this->classrooms = \App\Models\Academics\Classroom::orderBy('name')->pluck('name')->toArray();
        $this->streams = \App\Models\Academics\Stream::orderBy('name')->pluck('name')->toArray();
        $this->categories = \App\Models\StudentCategory::orderBy('name')->pluck('name')->toArray();
    }

    public function collection()
    {
        return $this->students;
    }

    public function headings(): array
    {
        return [
            // REQUIRED: Admission Number (used to identify student)
            'admission_number',
            
            // Student Basic Info
            'first_name',
            'middle_name',
            'last_name',
            'gender',
            'dob',
            
            // Academic Info
            'classroom',
            'stream',
            'category',
            
            // Identifiers
            'nemis_number',
            'knec_assessment_number',
            
            // Extended Demographics
            'religion',
            'residential_area',
            
            // Medical Information
            'has_allergies',
            'allergies_notes',
            'is_fully_immunized',
            'preferred_hospital',
            'emergency_contact_name',
            'emergency_contact_phone',
            'emergency_phone_country_code',
            
            // Parent/Guardian Information - Father
            'father_name',
            'father_phone_country_code',
            'father_phone',
            'father_whatsapp_country_code',
            'father_whatsapp',
            'father_email',
            'father_id_number',
            
            // Parent/Guardian Information - Mother
            'mother_name',
            'mother_phone_country_code',
            'mother_phone',
            'mother_whatsapp_country_code',
            'mother_whatsapp',
            'mother_email',
            'mother_id_number',
            
            // Parent/Guardian Information - Guardian
            'guardian_name',
            'guardian_phone_country_code',
            'guardian_phone',
            'guardian_relationship',
            'guardian_email',
            
            // Family Information
            'marital_status',
        ];
    }

    public function map($student): array
    {
        $parent = $student->parent;
        
        // Extract phone numbers without country code
        $fatherPhone = $parent ? $this->extractLocalPhone($parent->father_phone) : '';
        $motherPhone = $parent ? $this->extractLocalPhone($parent->mother_phone) : '';
        $guardianPhone = $parent ? $this->extractLocalPhone($parent->guardian_phone) : '';
        $emergencyPhone = $this->extractLocalPhone($student->emergency_contact_phone);
        
        return [
            // REQUIRED: Admission Number
            $student->admission_number,
            
            // Student Basic Info
            $student->first_name ?? '',
            $student->middle_name ?? '',
            $student->last_name ?? '',
            ucfirst($student->gender ?? ''),
            $student->dob ? $student->dob->format('Y-m-d') : '',
            
            // Academic Info
            $student->classroom ? $student->classroom->name : '',
            $student->stream ? $student->stream->name : '',
            $student->category ? $student->category->name : '',
            
            // Identifiers
            $student->nemis_number ?? '',
            $student->knec_assessment_number ?? '',
            
            // Extended Demographics
            $student->religion ?? '',
            $student->residential_area ?? '',
            
            // Medical Information
            $student->has_allergies ? 'Yes' : 'No',
            $student->allergies_notes ?? '',
            $student->is_fully_immunized ? 'Yes' : 'No',
            $student->preferred_hospital ?? '',
            $student->emergency_contact_name ?? '',
            $emergencyPhone,
            $this->extractCountryCode($student->emergency_contact_phone) ?: '+254',
            
            // Parent/Guardian Information - Father
            $parent->father_name ?? '',
            $parent->father_phone_country_code ?? '+254',
            $fatherPhone,
            $parent->father_whatsapp_country_code ?? '+254',
            $parent ? $this->extractLocalPhone($parent->father_whatsapp) : '',
            $parent->father_email ?? '',
            $parent->father_id_number ?? '',
            
            // Parent/Guardian Information - Mother
            $parent->mother_name ?? '',
            $parent->mother_phone_country_code ?? '+254',
            $motherPhone,
            $parent->mother_whatsapp_country_code ?? '+254',
            $parent ? $this->extractLocalPhone($parent->mother_whatsapp) : '',
            $parent->mother_email ?? '',
            $parent->mother_id_number ?? '',
            
            // Parent/Guardian Information - Guardian
            $parent->guardian_name ?? '',
            $parent->guardian_phone_country_code ?? '+254',
            $guardianPhone,
            $parent->guardian_relationship ?? '',
            $parent->guardian_email ?? '',
            
            // Family Information
            $parent->marital_status ?? '',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Create reference sheet for dropdowns
                $refSheet = $event->sheet->getParent()->createSheet();
                $refSheet->setTitle('Reference Data');
                
                // Add classrooms
                $refSheet->setCellValue('A1', 'Classrooms');
                foreach ($this->classrooms as $index => $name) {
                    $refSheet->setCellValue('A' . ($index + 2), $name);
                }
                
                // Add streams
                $refSheet->setCellValue('B1', 'Streams');
                foreach ($this->streams as $index => $name) {
                    $refSheet->setCellValue('B' . ($index + 2), $name);
                }
                
                // Add categories
                $refSheet->setCellValue('C1', 'Categories');
                foreach ($this->categories as $index => $name) {
                    $refSheet->setCellValue('C' . ($index + 2), $name);
                }
                
                // Add gender options
                $refSheet->setCellValue('D1', 'Gender');
                $refSheet->setCellValue('D2', 'Male');
                $refSheet->setCellValue('D3', 'Female');
                
                // Add boolean options
                $refSheet->setCellValue('E1', 'Yes/No');
                $refSheet->setCellValue('E2', 'Yes');
                $refSheet->setCellValue('E3', 'No');
                
                // Add marital status
                $refSheet->setCellValue('F1', 'Marital Status');
                $refSheet->setCellValue('F2', 'married');
                $refSheet->setCellValue('F3', 'single_parent');
                $refSheet->setCellValue('F4', 'co_parenting');
                
                // Set dropdowns on main sheet (starting from row 2)
                $lastRow = $this->students->count() + 1;
                
                // Classroom dropdown (column G)
                for ($row = 2; $row <= $lastRow + 50; $row++) {
                    $validation = $sheet->getCell("G$row")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setFormula1('"Reference Data"!$A$2:$A$' . (count($this->classrooms) + 1));
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Invalid Classroom');
                    $validation->setError('Please select a valid classroom from the list.');
                }
                
                // Stream dropdown (column H)
                for ($row = 2; $row <= $lastRow + 50; $row++) {
                    $validation = $sheet->getCell("H$row")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setFormula1('"Reference Data"!$B$2:$B$' . (count($this->streams) + 1));
                    $validation->setShowDropDown(true);
                }
                
                // Category dropdown (column I)
                for ($row = 2; $row <= $lastRow + 50; $row++) {
                    $validation = $sheet->getCell("I$row")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setFormula1('"Reference Data"!$C$2:$C$' . (count($this->categories) + 1));
                    $validation->setShowDropDown(true);
                }
                
                // Gender dropdown (column E)
                for ($row = 2; $row <= $lastRow + 50; $row++) {
                    $validation = $sheet->getCell("E$row")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setFormula1('"Reference Data"!$D$2:$D$3');
                    $validation->setShowDropDown(true);
                }
                
                // Has allergies dropdown (column M)
                for ($row = 2; $row <= $lastRow + 50; $row++) {
                    $validation = $sheet->getCell("M$row")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setFormula1('"Reference Data"!$E$2:$E$3');
                    $validation->setShowDropDown(true);
                }
                
                // Is fully immunized dropdown (column O)
                for ($row = 2; $row <= $lastRow + 50; $row++) {
                    $validation = $sheet->getCell("O$row")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setFormula1('"Reference Data"!$E$2:$E$3');
                    $validation->setShowDropDown(true);
                }
                
                // Marital status dropdown (last column)
                $maritalCol = 'AD'; // Column AD
                for ($row = 2; $row <= $lastRow + 50; $row++) {
                    $validation = $sheet->getCell("$maritalCol$row")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setFormula1('"Reference Data"!$F$2:$F$4');
                    $validation->setShowDropDown(true);
                }
                
                // Freeze first row
                $sheet->freezePane('A2');
                
                // Auto-size columns
                foreach (range('A', 'AD') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        ];
    }
    
    private function extractLocalPhone($phone)
    {
        if (empty($phone)) return '';
        
        // Remove country code if present
        $phone = preg_replace('/^\+254/', '', $phone);
        $phone = preg_replace('/^254/', '', $phone);
        
        return trim($phone);
    }
    
    private function extractCountryCode($phone)
    {
        if (empty($phone)) return '+254';
        
        if (preg_match('/^\+254/', $phone)) {
            return '+254';
        } elseif (preg_match('/^254/', $phone)) {
            return '+254';
        }
        
        return '+254'; // Default
    }
}

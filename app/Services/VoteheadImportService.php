<?php

namespace App\Services;

use App\Models\Votehead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoteheadImportService
{
    /**
     * Import voteheads from CSV data
     * 
     * @param array $rows CSV rows (associative arrays)
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function import(array $rows): array
    {
        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $updatedCount = 0;

        DB::beginTransaction();
        
        try {
            foreach ($rows as $rowIndex => $row) {
                // Convert row to array if it's an object
                $data = is_array($row) ? $row : (array) $row;
                
                // Validate row data
                $validation = $this->validateRow($data, $rowIndex + 1);
                
                if ($validation['valid']) {
                    try {
                        // Check if votehead exists by code or name
                        $votehead = null;
                        
                        if (!empty($data['code'])) {
                            $votehead = Votehead::where('code', $data['code'])->first();
                        }
                        
                        if (!$votehead && !empty($data['name'])) {
                            $votehead = Votehead::where('name', $data['name'])->first();
                        }
                        
                        // Prepare data for create/update
                        // Code will be auto-generated if not provided (via model boot method)
                        $voteheadData = [
                            'name' => $data['name'],
                            'code' => !empty($data['code']) ? $data['code'] : null, // Let model generate if empty
                            'description' => $data['description'] ?? null,
                            'category' => $data['category'] ?? null,
                            'is_mandatory' => $this->parseBoolean($data['is_mandatory'] ?? false),
                            'charge_type' => $data['charge_type'],
                            'is_optional' => $this->parseBoolean($data['is_optional'] ?? false),
                            'is_active' => $this->parseBoolean($data['is_active'] ?? true),
                        ];
                        
                        if ($votehead) {
                            // Update existing
                            $votehead->update($voteheadData);
                            $updatedCount++;
                        } else {
                            // Create new
                            Votehead::create($voteheadData);
                            $successCount++;
                        }
                    } catch (\Exception $e) {
                        $failedCount++;
                        $errors[] = [
                            'row' => $rowIndex + 1,
                            'data' => $data,
                            'error' => $e->getMessage(),
                        ];
                    }
                } else {
                    $failedCount++;
                    $errors[] = [
                        'row' => $rowIndex + 1,
                        'data' => $data,
                        'errors' => $validation['errors'],
                    ];
                }
            }
            
            DB::commit();
            
            return [
                'success' => $successCount,
                'updated' => $updatedCount,
                'failed' => $failedCount,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate a single row of import data
     */
    protected function validateRow(array $data, int $rowNumber): array
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'is_mandatory' => 'nullable|boolean',
            'charge_type' => 'required|in:per_student,once,once_annually,per_family',
            'is_optional' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'Row ' . $rowNumber . ': Name is required',
            'charge_type.required' => 'Row ' . $rowNumber . ': Charge type is required',
            'charge_type.in' => 'Row ' . $rowNumber . ': Charge type must be one of: per_student, once, once_annually, per_family',
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->all(),
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    /**
     * Parse boolean value from various formats
     */
    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_numeric($value)) {
            return (bool) $value;
        }
        
        $value = strtolower(trim((string) $value));
        
        return in_array($value, ['1', 'true', 'yes', 'y', 'on']);
    }

    /**
     * Generate Excel template with dropdown validation for categories
     */
    public function generateExcelTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Get all active categories for dropdown
        $categories = \App\Models\VoteheadCategory::active()
            ->orderBy('display_order')
            ->pluck('name')
            ->toArray();
        
        $categoryList = implode(',', $categories);
        
        // Headers
        $headers = [
            'code (auto-generated if empty)',
            'name',
            'description',
            'category',
            'is_mandatory',
            'charge_type',
            'is_optional',
            'is_active',
        ];
        
        // Set headers
        $sheet->fromArray($headers, null, 'A1');
        
        // Style header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(15);
        
        // Get existing voteheads to prefill template
        $existingVoteheads = \App\Models\Votehead::orderBy('category')->orderBy('name')->get();
        
        $rowIndex = 2;
        
        if ($existingVoteheads->isNotEmpty()) {
            // Add existing voteheads
            foreach ($existingVoteheads as $votehead) {
                $sheet->setCellValue('A' . $rowIndex, $votehead->code ?? '');
                $sheet->setCellValue('B' . $rowIndex, $votehead->name);
                $sheet->setCellValue('C' . $rowIndex, $votehead->description ?? '');
                $sheet->setCellValue('D' . $rowIndex, $votehead->category ?? '');
                $sheet->setCellValue('E' . $rowIndex, $votehead->is_mandatory ? '1' : '0');
                $sheet->setCellValue('F' . $rowIndex, $votehead->charge_type);
                $sheet->setCellValue('G' . $rowIndex, $votehead->is_optional ? '1' : '0');
                $sheet->setCellValue('H' . $rowIndex, $votehead->is_active ? '1' : '1');
                
                $rowIndex++;
            }
        } else {
            // Add example rows
            $examples = [
                ['', 'Tuition Fees', 'Main tuition fee for students', 'Tuition', '1', 'per_student', '0', '1'],
                ['', 'Library Fee', 'Library maintenance and book access fee', 'Library', '1', 'per_student', '0', '1'],
                ['', 'Admission Fee', 'One-time admission fee', 'Administrative', '1', 'once', '0', '1'],
            ];
            
            foreach ($examples as $example) {
                $sheet->fromArray($example, null, 'A' . $rowIndex);
                $rowIndex++;
            }
        }
        
        // Add dropdown validation to all data rows (starting from row 2)
        $lastRow = $rowIndex - 1;
        if ($lastRow >= 2) {
            // Add dropdowns for existing rows plus 100 more rows for future entries
            for ($row = 2; $row <= $lastRow + 100; $row++) {
                // Category dropdown (column D)
                $this->addCategoryDropdown($sheet, 'D' . $row, $categories);
                
                // Charge type dropdown (column F)
                $chargeTypes = ['per_student', 'once', 'once_annually', 'per_family'];
                $chargeTypeList = implode(',', $chargeTypes);
                $validation = $sheet->getCell('F' . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"' . $chargeTypeList . '"');
                $validation->setPromptTitle('Select Charge Type');
                $validation->setPrompt('Please select a charge type from the dropdown list.');
                $validation->setErrorTitle('Invalid Charge Type');
                $validation->setError('The value must be one of: per_student, once, once_annually, per_family');
            }
        }
        
        // Create writer and return as stream
        $writer = new Xlsx($spreadsheet);
        
        return new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="voteheads_import_template.xlsx"',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }
    
    /**
     * Add category dropdown validation to a cell
     */
    protected function addCategoryDropdown($sheet, $cell, array $categories)
    {
        $categoryList = implode(',', $categories);
        
        $validation = $sheet->getCell($cell)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1('"' . $categoryList . '"');
        
        // Set helpful messages
        $validation->setPromptTitle('Select Category');
        $validation->setPrompt('Please select a category from the dropdown list.');
        $validation->setErrorTitle('Invalid Category');
        $validation->setError('The value you entered is not in the list of valid categories.');
    }
    
    /**
     * Generate CSV template (fallback for compatibility)
     */
    public function generateTemplate(): string
    {
        // Get all active categories
        $categories = \App\Models\VoteheadCategory::active()
            ->orderBy('display_order')
            ->pluck('name')
            ->toArray();
        
        $categoryList = implode(';', $categories);
        
        $headers = [
            'code (auto-generated if empty)',
            'name',
            'description',
            'category',
            'is_mandatory',
            'charge_type',
            'is_optional',
            'is_active',
        ];
        
        // Get existing voteheads to prefill template
        $existingVoteheads = \App\Models\Votehead::orderBy('category')->orderBy('name')->get();
        
        // Add category list as first row (comment)
        $csv = '# Valid Categories: ' . $categoryList . "\n";
        
        // Add headers
        $csv .= implode(',', $headers) . "\n";
        
        // Add existing voteheads
        foreach ($existingVoteheads as $votehead) {
            $row = [
                $votehead->code ?? '',
                $votehead->name,
                $votehead->description ?? '',
                $votehead->category ?? '',
                $votehead->is_mandatory ? '1' : '0',
                $votehead->charge_type,
                $votehead->is_optional ? '1' : '0',
                $votehead->is_active ? '1' : '1',
            ];
            
            // Escape commas and quotes
            $escapedRow = array_map(function($value) {
                if (strpos($value, ',') !== false || strpos($value, '"') !== false) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }
                return $value;
            }, $row);
            
            $csv .= implode(',', $escapedRow) . "\n";
        }
        
        // Add example rows if no existing voteheads
        if ($existingVoteheads->isEmpty()) {
            $exampleRows = [
                ['', 'Tuition Fees', 'Main tuition fee for students', 'Tuition', '1', 'per_student', '0', '1'],
                ['', 'Library Fee', 'Library maintenance and book access fee', 'Library', '1', 'per_student', '0', '1'],
                ['', 'Admission Fee', 'One-time admission fee', 'Administrative', '1', 'once', '0', '1'],
            ];
            
            foreach ($exampleRows as $row) {
                $escapedRow = array_map(function($value) {
                    if (strpos($value, ',') !== false || strpos($value, '"') !== false) {
                        return '"' . str_replace('"', '""', $value) . '"';
                    }
                    return $value;
                }, $row);
                
                $csv .= implode(',', $escapedRow) . "\n";
            }
        }
        
        return $csv;
    }
}


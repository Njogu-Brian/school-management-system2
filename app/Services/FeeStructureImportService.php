<?php

namespace App\Services;

use App\Models\FeeStructure;
use App\Models\FeeCharge;
use App\Models\Votehead;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FeeStructureImportService
{
    /**
     * Import fee structures from CSV data
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
            // Group rows by structure identifier (classroom + academic_year + term + stream)
            $groupedRows = $this->groupRowsByStructure($rows);
            
            foreach ($groupedRows as $structureKey => $structureRows) {
                try {
                    // First row contains structure metadata
                    $firstRow = $structureRows[0];
                    $data = is_array($firstRow) ? $firstRow : (array) $firstRow;
                    
                    // Validate structure metadata
                    $structureValidation = $this->validateStructureRow($data, $structureKey);
                    
                    if (!$structureValidation['valid']) {
                        $failedCount++;
                        $errors[] = [
                            'structure' => $structureKey,
                            'error' => 'Structure validation failed: ' . implode(', ', $structureValidation['errors']),
                            'rows' => $structureRows,
                        ];
                        continue;
                    }
                    
                    // Resolve foreign keys
                    $classroom = $this->resolveClassroom($data['classroom'] ?? $data['classroom_id'] ?? null);
                    $academicYear = $this->resolveAcademicYear($data['academic_year'] ?? $data['academic_year_id'] ?? $data['year'] ?? null);
                    $term = $this->resolveTerm($data['term'] ?? $data['term_id'] ?? null, $academicYear?->id);
                    $stream = $this->resolveStream($data['stream'] ?? $data['stream_id'] ?? null);
                    $studentCategory = $this->resolveStudentCategory(
                        $data['student_category'] ?? $data['category'] ?? $data['student_category_id'] ?? null
                    );
                    
                    if (!$classroom) {
                        $failedCount++;
                        $errors[] = [
                            'structure' => $structureKey,
                            'error' => 'Classroom not found: ' . ($data['classroom'] ?? $data['classroom_id'] ?? 'N/A'),
                            'rows' => $structureRows,
                        ];
                        continue;
                    }
                    
                    // Check if structure already exists (including category)
                    $existingStructure = FeeStructure::where('classroom_id', $classroom->id)
                        ->where('academic_year_id', $academicYear?->id)
                        ->where('term_id', $term?->id)
                        ->where('stream_id', $stream?->id)
                        ->where('student_category_id', $studentCategory?->id)
                        ->first();
                    
                    // Prepare structure data
                    $structureData = [
                        'name' => $data['structure_name'] ?? $data['name'] ?? ($classroom->name . ' Fee Structure'),
                        'classroom_id' => $classroom->id,
                        'academic_year_id' => $academicYear?->id,
                        'term_id' => $term?->id,
                        'stream_id' => $stream?->id,
                        'student_category_id' => $studentCategory?->id,
                        'year' => $academicYear?->year ?? date('Y'),
                        'version' => $data['version'] ?? 1,
                        'is_active' => $this->parseBoolean($data['is_active'] ?? true),
                        'created_by' => auth()->id(),
                    ];
                    
                    if ($existingStructure) {
                        // Update existing structure
                        $feeStructure = $existingStructure;
                        $feeStructure->update($structureData);
                        // Delete existing charges to replace them
                        $feeStructure->charges()->delete();
                        $updatedCount++;
                    } else {
                        // Create new structure
                        $feeStructure = FeeStructure::create($structureData);
                        $successCount++;
                    }
                    
                    // Process charge rows
                    foreach ($structureRows as $rowIndex => $chargeRow) {
                        $chargeData = is_array($chargeRow) ? $chargeRow : (array) $chargeRow;
                        
                        // Skip if this row doesn't have votehead information
                        if (empty($chargeData['votehead']) && empty($chargeData['votehead_code']) && empty($chargeData['votehead_id'])) {
                            continue;
                        }
                        
                        // Validate charge row
                        $chargeValidation = $this->validateChargeRow($chargeData, $rowIndex + 1);
                        
                        if (!$chargeValidation['valid']) {
                            $errors[] = [
                                'structure' => $structureKey,
                                'row' => $rowIndex + 1,
                                'error' => 'Charge validation failed: ' . implode(', ', $chargeValidation['errors']),
                                'data' => $chargeData,
                            ];
                            continue;
                        }
                        
                        // Resolve votehead
                        $votehead = $this->resolveVotehead(
                            $chargeData['votehead'] ?? null,
                            $chargeData['votehead_code'] ?? null,
                            $chargeData['votehead_id'] ?? null
                        );
                        
                        if (!$votehead) {
                            $errors[] = [
                                'structure' => $structureKey,
                                'row' => $rowIndex + 1,
                                'error' => 'Votehead not found',
                                'data' => $chargeData,
                            ];
                            continue;
                        }
                        
                        // Create charges for each term (1, 2, 3) if amount is provided
                        foreach ([1, 2, 3] as $termNumber) {
                            $amountKey = 'term_' . $termNumber;
                            $amount = $chargeData[$amountKey] ?? $chargeData['amount'] ?? null;
                            
                            if ($amount !== null && $amount !== '' && (float) $amount > 0) {
                                FeeCharge::create([
                                    'fee_structure_id' => $feeStructure->id,
                                    'votehead_id' => $votehead->id,
                                    'term' => $termNumber,
                                    'amount' => (float) $amount,
                                ]);
                            }
                        }
                    }
                    
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = [
                        'structure' => $structureKey,
                        'error' => $e->getMessage(),
                        'rows' => $structureRows,
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
     * Group rows by structure identifier
     */
    protected function groupRowsByStructure(array $rows): array
    {
        $grouped = [];
        
        foreach ($rows as $row) {
            $data = is_array($row) ? $row : (array) $row;
            
            // Create unique key for structure: classroom + academic_year + term + stream + category
            $key = sprintf(
                '%s_%s_%s_%s_%s',
                $data['classroom'] ?? $data['classroom_id'] ?? 'null',
                $data['academic_year'] ?? $data['academic_year_id'] ?? $data['year'] ?? 'null',
                $data['term'] ?? $data['term_id'] ?? 'null',
                $data['stream'] ?? $data['stream_id'] ?? 'null',
                $data['student_category'] ?? $data['student_category_id'] ?? $data['category'] ?? 'null'
            );
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            
            $grouped[$key][] = $data;
        }
        
        return $grouped;
    }

    /**
     * Validate structure row
     */
    protected function validateStructureRow(array $data, string $structureKey): array
    {
        $validator = Validator::make($data, [
            'classroom' => 'required_without:classroom_id',
            'classroom_id' => 'required_without:classroom|exists:classrooms,id',
            'academic_year' => 'nullable',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'year' => 'nullable|numeric',
            'term' => 'nullable',
            'term_id' => 'nullable|exists:terms,id',
            'stream' => 'nullable',
            'stream_id' => 'nullable|exists:streams,id',
            'student_category' => 'nullable',
            'student_category_id' => 'nullable|exists:student_categories,id',
            'category' => 'nullable', // Alias for student_category
            'structure_name' => 'nullable|string|max:255',
            'version' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
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
     * Validate charge row
     */
    protected function validateChargeRow(array $data, int $rowNumber): array
    {
        $validator = Validator::make($data, [
            'votehead' => 'required_without_all:votehead_code,votehead_id',
            'votehead_code' => 'required_without_all:votehead,votehead_id',
            'votehead_id' => 'required_without_all:votehead,votehead_code|exists:voteheads,id',
            'term_1' => 'nullable|numeric|min:0',
            'term_2' => 'nullable|numeric|min:0',
            'term_3' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
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
     * Resolve classroom by name or ID
     */
    protected function resolveClassroom($identifier)
    {
        if (!$identifier) {
            return null;
        }
        
        if (is_numeric($identifier)) {
            return Classroom::find($identifier);
        }
        
        return Classroom::where('name', $identifier)->first();
    }

    /**
     * Resolve academic year by year or ID
     */
    protected function resolveAcademicYear($identifier)
    {
        if (!$identifier) {
            return null;
        }
        
        if (is_numeric($identifier)) {
            $year = AcademicYear::find($identifier);
            if ($year) {
                return $year;
            }
            // Try finding by year value
            return AcademicYear::where('year', $identifier)->first();
        }
        
        // Assume it's a year value
        return AcademicYear::where('year', $identifier)->first();
    }

    /**
     * Resolve term by name or ID
     */
    protected function resolveTerm($identifier, ?int $academicYearId = null)
    {
        if (!$identifier) {
            return null;
        }
        
        if (is_numeric($identifier)) {
            $term = Term::find($identifier);
            if ($term) {
                return $term;
            }
        }
        
        $query = Term::where('name', $identifier);
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        
        return $query->first();
    }

    /**
     * Resolve stream by name or ID
     */
    protected function resolveStream($identifier)
    {
        if (!$identifier) {
            return null;
        }
        
        if (is_numeric($identifier)) {
            return Stream::find($identifier);
        }
        
        return Stream::where('name', $identifier)->first();
    }

    /**
     * Resolve student category by name or ID
     */
    protected function resolveStudentCategory($identifier)
    {
        if (!$identifier) {
            return null;
        }
        
        if (is_numeric($identifier)) {
            return \App\Models\StudentCategory::find($identifier);
        }
        
        return \App\Models\StudentCategory::where('name', $identifier)->first();
    }

    /**
     * Resolve votehead by name, code, or ID
     */
    protected function resolveVotehead(?string $name = null, ?string $code = null, ?int $id = null)
    {
        if ($id) {
            return Votehead::find($id);
        }
        
        if ($code) {
            return Votehead::where('code', $code)->first();
        }
        
        if ($name) {
            return Votehead::where('name', $name)->first();
        }
        
        return null;
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
     * Generate CSV template prefilled with existing voteheads
     */
    public function generateTemplate(): string
    {
        // Get existing voteheads to prefill template
        $voteheads = \App\Models\Votehead::active()->orderBy('category')->orderBy('name')->get();
        
        // Get classrooms for reference
        $classrooms = \App\Models\Academics\Classroom::orderBy('name')->get();
        $classroomList = $classrooms->pluck('name')->toArray();
        
        // Get academic years
        $academicYears = \App\Models\AcademicYear::orderBy('year', 'desc')->get();
        $yearList = $academicYears->pluck('year')->toArray();
        
        // Get terms
        $terms = \App\Models\Term::orderBy('academic_year_id')->orderBy('name')->get();
        $termList = $terms->pluck('name')->unique()->toArray();
        
        // Get student categories
        $categories = \App\Models\StudentCategory::orderBy('name')->get();
        $categoryList = $categories->pluck('name')->toArray();
        
        $headers = [
            'classroom',
            'academic_year',
            'term',
            'stream',
            'student_category',
            'structure_name',
            'votehead',
            'votehead_code',
            'term_1',
            'term_2',
            'term_3',
            'is_active',
        ];
        
        $csv = '';
        
        // Add reference lists as comments
        if (!empty($classroomList)) {
            $csv .= '# Valid Classrooms: ' . implode(';', $classroomList) . "\n";
        }
        if (!empty($yearList)) {
            $csv .= '# Valid Academic Years: ' . implode(';', $yearList) . "\n";
        }
        if (!empty($termList)) {
            $csv .= '# Valid Terms: ' . implode(';', $termList) . "\n";
        }
        if (!empty($categoryList)) {
            $csv .= '# Valid Student Categories: ' . implode(';', $categoryList) . "\n";
        }
        if ($voteheads->isNotEmpty()) {
            $voteheadList = $voteheads->map(function($v) {
                return $v->name . ' (' . ($v->code ?? 'N/A') . ')';
            })->toArray();
            $csv .= '# Available Voteheads: ' . implode(';', $voteheadList) . "\n";
        }
        
        // Add headers
        $csv .= implode(',', $headers) . "\n";
        
        // Create example structure with existing voteheads
        if ($voteheads->isNotEmpty() && $classrooms->isNotEmpty()) {
            $exampleClassroom = $classrooms->first();
            $exampleYear = !empty($yearList) ? $yearList[0] : date('Y');
            $exampleTerm = !empty($termList) ? $termList[0] : '';
            $structureName = $exampleClassroom->name . ' Fee Structure ' . $exampleYear;
            
            // Add one row per votehead for the example structure
            foreach ($voteheads->take(10) as $votehead) { // Limit to first 10 voteheads
                $row = [
                    $exampleClassroom->name,
                    $exampleYear,
                    $exampleTerm,
                    '',
                    $structureName,
                    $votehead->name,
                    $votehead->code ?? '',
                    '',
                    '',
                    '',
                    '1',
                ];
                
                $escapedRow = array_map(function($value) {
                    if (strpos($value, ',') !== false || strpos($value, '"') !== false) {
                        return '"' . str_replace('"', '""', $value) . '"';
                    }
                    return $value;
                }, $row);
                
                $csv .= implode(',', $escapedRow) . "\n";
            }
        } else {
            // Fallback example rows
            $exampleRows = [
                [
                    'Grade 1A',
                    '2025',
                    'Term 1',
                    '',
                    'Grade 1A Fee Structure 2025',
                    'Tuition Fees',
                    'TUITION',
                    '50000',
                    '50000',
                    '50000',
                    '1',
                ],
                [
                    'Grade 1A',
                    '2025',
                    'Term 1',
                    '',
                    'Grade 1A Fee Structure 2025',
                    'Library Fee',
                    'LIB',
                    '2000',
                    '2000',
                    '2000',
                    '',
                ],
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


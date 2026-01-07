<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Models\DropOffPoint;
use App\Models\StudentAssignment;
use App\Models\TransportFee;
use App\Services\TransportFeeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class TransportAssignmentImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $successCount = 0;
    public int $updatedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];
    public array $conflicts = [];
    public array $feeConflicts = [];
    public array $missingStudents = []; // Students not found by name - need manual linking
    public bool $previewOnly = false;
    public array $previewData = [];
    public bool $syncTransportFees = false;
    public ?int $year = null;
    public ?int $term = null;

    public function __construct(bool $previewOnly = false, bool $syncTransportFees = false, ?int $year = null, ?int $term = null)
    {
        $this->previewOnly = $previewOnly;
        $this->syncTransportFees = $syncTransportFees;
        $this->year = $year;
        $this->term = $term;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because index starts at 0 and we have header row
            
            try {
                $this->processRow($row, $rowNumber);
            } catch (\Exception $e) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                    'data' => $row->toArray()
                ];
                $this->skippedCount++;
                Log::error("Transport import error on row {$rowNumber}: " . $e->getMessage());
            }
        }
    }

    protected function processRow($row, $rowNumber)
    {
        // Extract and clean data - handle various column name formats
        $rowArray = $row->toArray();
        
        // Get student name from Excel (primary identifier)
        $studentName = trim($rowArray['name'] ?? $rowArray['student_name'] ?? '');
        // Route is the drop-off point from Excel
        $route = strtoupper(trim($rowArray['route'] ?? $rowArray['drop_off_point'] ?? ''));
        // Vehicle info contains vehicle code and trip number
        $vehicleInfo = trim($rowArray['vehicle'] ?? $rowArray['vehicle_trip'] ?? '');

        // Validate required fields
        if (empty($studentName)) {
            throw new \Exception("Student name is required");
        }

        // Find student using smart name matching
        $student = $this->findStudentByName($studentName);

        if (!$student) {
            // In preview mode, add to missing students list for manual linking
            if ($this->previewOnly) {
                $this->missingStudents[] = [
                    'row' => $rowNumber,
                    'name' => $studentName,
                    'route' => $route,
                    'vehicle' => $vehicleInfo,
                    'excel_data' => $rowArray
                ];
                $this->skippedCount++;
                return; // Skip this row in preview, will be linked manually
            }
            throw new \Exception("Student with name '{$studentName}' not found");
        }
        
        // Get class name and admission number from database student record
        $className = $student->class ? $student->class->name : '';
        $admissionNumber = $student->admission_number;

        // Parse vehicle and trip information
        // Format: "KDR TRIP 1" or "KCB TRIP 2" or "OWN"
        if (strtoupper($vehicleInfo) === 'OWN') {
            // Student uses own transport - skip assignment
            if ($this->previewOnly) {
                $this->previewData[] = [
                    'row' => $rowNumber,
                    'admission_number' => $admissionNumber,
                    'student_name' => $student->full_name,
                    'route' => $route,
                    'class' => $className,
                    'vehicle' => 'OWN',
                    'trip' => 'N/A',
                    'status' => 'skipped',
                    'message' => 'Student uses own transport'
                ];
            }
            $this->skippedCount++;
            return;
        }

        // Parse vehicle number and trip number
        // Supported formats:
        // - "KDR TRIP 1" (3-letter code)
        // - "KDR936F TRIP 1" (full registration)
        // - "KAQ967W TRIP 2" (full registration with trip number)
        $parts = explode(' ', trim($vehicleInfo));
        
        if (count($parts) < 3) {
            throw new \Exception("Invalid vehicle format. Expected format: 'KDR TRIP 1' or 'KDR936F TRIP 1'");
        }

        $vehicleNumberRaw = strtoupper(trim($parts[0]));
        $tripNumber = trim($parts[2] ?? '1');

        // Extract first 3 letters from vehicle number (e.g., KAQ967W -> KAQ, KDR936F -> KDR, KDR -> KDR)
        // This allows Excel to have either 3-letter codes or full registration numbers
        $vehicleCode = substr($vehicleNumberRaw, 0, 3);

        // Find vehicle by matching the first 3 characters
        $vehicle = Vehicle::where('vehicle_number', 'LIKE', $vehicleCode . '%')->first();
        
        if (!$vehicle) {
            // Try exact match as fallback
            $vehicle = Vehicle::where('vehicle_number', $vehicleNumberRaw)->first();
        }
        
        if (!$vehicle) {
            throw new \Exception("Vehicle starting with '{$vehicleCode}' (from '{$vehicleNumberRaw}') not found. Please create it first.");
        }

        // Find or create drop-off point
        $dropOffPoint = null;
        if (!empty($route) && strtoupper($route) !== 'OWN') {
            $dropOffPoint = DropOffPoint::firstOrCreate(
                ['name' => $route]
            );
        }

        // Check for route conflict in student assignment
        $existingAssignment = StudentAssignment::where('student_id', $student->id)->first();
        $hasConflict = false;
        $conflictMessage = '';
        $hasFeeConflict = false;
        $feeConflictMessage = '';

        if ($existingAssignment && $existingAssignment->eveningDropOffPoint) {
            $existingRoute = $existingAssignment->eveningDropOffPoint->name;
            if ($existingRoute !== $route && !empty($route)) {
                $hasConflict = true;
                $conflictMessage = "Route conflict: System has '{$existingRoute}', Excel has '{$route}'";
                
                $this->conflicts[] = [
                    'row' => $rowNumber,
                    'admission_number' => $admissionNumber,
                    'student_name' => $student->full_name,
                    'existing_route' => $existingRoute,
                    'new_route' => $route,
                    'student_id' => $student->id,
                    'drop_off_point_id' => $dropOffPoint ? $dropOffPoint->id : null
                ];
            }
        }

        // Check for transport fee conflict
        if ($dropOffPoint) {
            [$currentYear, $currentTerm] = TransportFeeService::resolveYearAndTerm($this->year, $this->term);
            $existingFee = TransportFee::where('student_id', $student->id)
                ->where('year', $currentYear)
                ->where('term', $currentTerm)
                ->first();

            if ($existingFee && $existingFee->drop_off_point_id) {
                $feeDropOffPoint = DropOffPoint::find($existingFee->drop_off_point_id);
                if ($feeDropOffPoint && $feeDropOffPoint->id !== $dropOffPoint->id) {
                    $hasFeeConflict = true;
                    $feeConflictMessage = "Transport fee has drop-off point '{$feeDropOffPoint->name}', Excel has '{$route}'";
                    
                    $this->feeConflicts[] = [
                        'row' => $rowNumber,
                        'admission_number' => $admissionNumber,
                        'student_name' => $student->full_name,
                        'existing_fee_route' => $feeDropOffPoint->name,
                        'new_route' => $route,
                        'student_id' => $student->id,
                        'drop_off_point_id' => $dropOffPoint->id,
                        'transport_fee_id' => $existingFee->id,
                        'fee_amount' => $existingFee->amount
                    ];
                }
            }
        }

        // Find or create trip for this vehicle
        // Use the vehicle code (first 3 letters) for trip name consistency
        $tripName = "{$vehicleCode} TRIP {$tripNumber}";
        $trip = Trip::firstOrCreate(
            [
                'vehicle_id' => $vehicle->id,
                'trip_name' => $tripName,
                'direction' => 'dropoff' // Evening drop-off (using 'dropoff' not 'evening')
            ],
            [
                'day_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
            ]
        );

        if ($this->previewOnly) {
            $status = 'ready';
            $message = 'Ready to import';
            
            if ($hasConflict) {
                $status = 'conflict';
                $message = $conflictMessage;
            } elseif ($hasFeeConflict) {
                $status = 'fee_conflict';
                $message = $feeConflictMessage . ' (Transport fee may need update)';
            }

            $this->previewData[] = [
                'row' => $rowNumber,
                'admission_number' => $admissionNumber,
                'student_name' => $student->full_name,
                'route' => $route,
                'class' => $className,
                'vehicle' => $vehicleCode,
                'trip' => $tripName,
                'status' => $status,
                'message' => $message,
                'existing_route' => $existingAssignment && $existingAssignment->eveningDropOffPoint 
                    ? $existingAssignment->eveningDropOffPoint->name 
                    : 'None',
                'has_fee_conflict' => $hasFeeConflict
            ];
        } else {
            // Create or update student assignment (only if no conflict or conflict resolved)
            if (!$hasConflict) {
                $assignmentData = [
                    'student_id' => $student->id,
                    'evening_trip_id' => $trip->id,
                ];

                if ($dropOffPoint) {
                    $assignmentData['evening_drop_off_point_id'] = $dropOffPoint->id;
                }

                if ($existingAssignment) {
                    $existingAssignment->update($assignmentData);
                    $this->updatedCount++;
                } else {
                    StudentAssignment::create($assignmentData);
                    $this->successCount++;
                }

                // Sync transport fee if enabled and drop-off point exists
                if ($this->syncTransportFees && $dropOffPoint) {
                    [$currentYear, $currentTerm] = TransportFeeService::resolveYearAndTerm($this->year, $this->term);
                    
                    // Only update drop-off point, preserve existing fee amount
                    $existingFee = TransportFee::where('student_id', $student->id)
                        ->where('year', $currentYear)
                        ->where('term', $currentTerm)
                        ->first();

                    if ($existingFee) {
                        // Update existing fee with new drop-off point (preserve amount)
                        TransportFeeService::upsertFee([
                            'student_id' => $student->id,
                            'amount' => $existingFee->amount, // Preserve existing amount
                            'year' => $currentYear,
                            'term' => $currentTerm,
                            'drop_off_point_id' => $dropOffPoint->id,
                            'drop_off_point_name' => $dropOffPoint->name,
                            'source' => 'import_sync',
                            'note' => 'Drop-off point updated from transport assignment import',
                            'skip_invoice' => $existingFee->amount == 0, // Skip invoice if amount is 0
                        ]);
                    }
                }
            }
        }
    }

    public function getResults()
    {
        return [
            'success' => $this->successCount,
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errors,
            'conflicts' => $this->conflicts,
            'fee_conflicts' => $this->feeConflicts,
            'missing_students' => $this->missingStudents,
            'preview_data' => $this->previewData
        ];
    }

    /**
     * Smart name matching algorithm
     * 1. Try exact full name match
     * 2. Try matching with name parts in any order
     * 3. If 2+ name parts match and only one student has those parts, use that student
     * 4. If multiple students share 2 names, use 3rd name to disambiguate
     */
    protected function findStudentByName(string $searchName): ?Student
    {
        // Normalize and split the search name into parts
        $searchParts = $this->normalizeNameParts($searchName);
        
        if (empty($searchParts)) {
            return null;
        }

        // Get all active students
        $students = Student::where('archive', 0)
            ->where('is_alumni', false)
            ->get();

        $matches = [];

        foreach ($students as $student) {
            // Get student's name parts from database
            $studentParts = $this->getStudentNameParts($student);
            
            // Count how many name parts match (order doesn't matter)
            $matchCount = $this->countMatchingParts($searchParts, $studentParts);
            
            if ($matchCount >= 2) {
                $matches[] = [
                    'student' => $student,
                    'match_count' => $matchCount,
                    'total_search_parts' => count($searchParts),
                    'total_student_parts' => count($studentParts),
                    'student_parts' => $studentParts
                ];
            }
        }

        // No matches found
        if (empty($matches)) {
            return null;
        }

        // Sort by match count (highest first)
        usort($matches, function($a, $b) {
            return $b['match_count'] - $a['match_count'];
        });

        // If only one match, return it
        if (count($matches) === 1) {
            return $matches[0]['student'];
        }

        // Multiple matches - try to find unique match
        $topMatchCount = $matches[0]['match_count'];
        $topMatches = array_filter($matches, fn($m) => $m['match_count'] === $topMatchCount);

        // If only one student has the highest match count, use that
        if (count($topMatches) === 1) {
            return $matches[0]['student'];
        }

        // Multiple students with same match count
        // Try to disambiguate using additional name parts
        foreach ($topMatches as $match) {
            $studentParts = $match['student_parts'];
            
            // Check if ALL search parts are found in student parts
            $allMatch = true;
            foreach ($searchParts as $searchPart) {
                $found = false;
                foreach ($studentParts as $studentPart) {
                    if ($this->fuzzyMatch($searchPart, $studentPart)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $allMatch = false;
                    break;
                }
            }
            
            if ($allMatch) {
                // This student matches all search parts
                // Check if any other student also matches all parts
                $fullMatchCount = 0;
                $fullMatchStudent = null;
                
                foreach ($topMatches as $m) {
                    $allPartsMatch = true;
                    foreach ($searchParts as $searchPart) {
                        $found = false;
                        foreach ($m['student_parts'] as $sp) {
                            if ($this->fuzzyMatch($searchPart, $sp)) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $allPartsMatch = false;
                            break;
                        }
                    }
                    if ($allPartsMatch) {
                        $fullMatchCount++;
                        $fullMatchStudent = $m['student'];
                    }
                }
                
                // If only one student matches all parts, return that student
                if ($fullMatchCount === 1) {
                    return $fullMatchStudent;
                }
            }
        }

        // Still ambiguous - return the first match (highest score)
        // This will be handled by manual linking if in preview mode
        return null;
    }

    /**
     * Normalize a name string into an array of uppercase name parts
     */
    protected function normalizeNameParts(string $name): array
    {
        // Remove extra spaces, convert to uppercase
        $name = strtoupper(trim(preg_replace('/\s+/', ' ', $name)));
        
        // Split by space
        $parts = explode(' ', $name);
        
        // Filter out empty parts and very short parts (like initials)
        $parts = array_filter($parts, fn($p) => strlen($p) >= 2);
        
        return array_values($parts);
    }

    /**
     * Get name parts from a student record
     */
    protected function getStudentNameParts(Student $student): array
    {
        $parts = [];
        
        if (!empty($student->first_name)) {
            $parts[] = strtoupper(trim($student->first_name));
        }
        if (!empty($student->middle_name)) {
            // Middle name might contain multiple names
            $middleParts = explode(' ', strtoupper(trim($student->middle_name)));
            foreach ($middleParts as $mp) {
                if (strlen($mp) >= 2) {
                    $parts[] = $mp;
                }
            }
        }
        if (!empty($student->last_name)) {
            $parts[] = strtoupper(trim($student->last_name));
        }
        
        return $parts;
    }

    /**
     * Count how many name parts match between search and student
     */
    protected function countMatchingParts(array $searchParts, array $studentParts): int
    {
        $matchCount = 0;
        $usedStudentParts = [];
        
        foreach ($searchParts as $searchPart) {
            foreach ($studentParts as $index => $studentPart) {
                if (!isset($usedStudentParts[$index]) && $this->fuzzyMatch($searchPart, $studentPart)) {
                    $matchCount++;
                    $usedStudentParts[$index] = true;
                    break;
                }
            }
        }
        
        return $matchCount;
    }

    /**
     * Fuzzy match two name parts
     * Handles slight variations like MWANGI vs MWANGI, NYAMWITHA vs NYAMUITHA
     */
    protected function fuzzyMatch(string $part1, string $part2): bool
    {
        // Exact match
        if ($part1 === $part2) {
            return true;
        }
        
        // One contains the other (for abbreviated names)
        if (str_contains($part1, $part2) || str_contains($part2, $part1)) {
            return true;
        }
        
        // Levenshtein distance for typos (allow 1-2 character differences for longer names)
        $len = max(strlen($part1), strlen($part2));
        $maxDistance = $len >= 6 ? 2 : ($len >= 4 ? 1 : 0);
        
        if (levenshtein($part1, $part2) <= $maxDistance) {
            return true;
        }
        
        // Soundex match for phonetically similar names
        if (soundex($part1) === soundex($part2)) {
            return true;
        }
        
        return false;
    }
}


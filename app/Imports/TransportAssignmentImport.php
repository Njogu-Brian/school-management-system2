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
        // Extract and clean data
        $admissionNumber = trim($row['admission_no'] ?? $row['admission'] ?? '');
        $name = trim($row['name'] ?? '');
        $route = trim($row['route'] ?? '');
        $className = trim($row['class'] ?? '');
        $vehicleInfo = trim($row['vehicle'] ?? '');

        // Validate required fields
        if (empty($admissionNumber)) {
            throw new \Exception("Admission number is required");
        }

        // Find student
        $student = Student::where('admission_number', $admissionNumber)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->first();

        if (!$student) {
            throw new \Exception("Student with admission number '{$admissionNumber}' not found");
        }

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
        $parts = explode(' ', $vehicleInfo);
        if (count($parts) < 3) {
            throw new \Exception("Invalid vehicle format. Expected format: 'KDR TRIP 1' or 'KDR936F TRIP 1'");
        }

        $vehicleNumberRaw = $parts[0];
        $tripNumber = $parts[2] ?? '1';

        // Extract first 3 letters from vehicle number (e.g., KAQ967W -> KAQ, KDR936F -> KDR)
        // This allows Excel to have full registration numbers while matching with 3-letter codes in DB
        $vehicleNumber = strtoupper(substr($vehicleNumberRaw, 0, 3));

        // Find vehicle by matching the first 3 characters
        $vehicle = Vehicle::where('vehicle_number', 'LIKE', $vehicleNumber . '%')->first();
        
        if (!$vehicle) {
            // Try exact match as fallback
            $vehicle = Vehicle::where('vehicle_number', $vehicleNumberRaw)->first();
        }
        
        if (!$vehicle) {
            throw new \Exception("Vehicle starting with '{$vehicleNumber}' (from '{$vehicleNumberRaw}') not found. Please create it first.");
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
        $tripName = "{$vehicleNumber} TRIP {$tripNumber}";
        $trip = Trip::firstOrCreate(
            [
                'vehicle_id' => $vehicle->id,
                'trip_name' => $tripName,
                'direction' => 'evening' // Evening drop-off
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
                'vehicle' => $vehicleNumber,
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
            'preview_data' => $this->previewData
        ];
    }
}


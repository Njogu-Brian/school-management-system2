<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Imports\TransportAssignmentImport;
use App\Models\TransportImportLog;
use App\Models\Student;
use App\Models\StudentAssignment;
use App\Models\DropOffPoint;
use App\Models\Vehicle;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class TransportImportController extends Controller
{
    /**
     * Show the import form
     */
    public function importForm()
    {
        $recentImports = TransportImportLog::with('importedBy')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('transport.import.form', compact('recentImports'));
    }

    /**
     * Preview the import data and show conflicts
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'year' => 'nullable|integer',
            'term' => 'nullable|integer|in:1,2,3',
        ]);

        try {
            // Check if file was actually uploaded
            if (!$request->hasFile('file')) {
                return back()->with('error', 'No file uploaded. Please select a file to import.');
            }

            $file = $request->file('file');
            
            // Check if file is valid
            if (!$file->isValid()) {
                return back()->with('error', 'File upload failed. Please try again. Error: ' . $file->getErrorMessage());
            }

            [$year, $term] = \App\Services\TransportFeeService::resolveYearAndTerm($request->year, $request->term);
            
            $import = new TransportAssignmentImport(true, false, $year, $term); // Preview mode
            Excel::import($import, $file);

            $results = $import->getResults();

            // Store file temporarily for actual import
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('temp/transport-imports', $filename);

            if (!$path) {
                return back()->with('error', 'Failed to save uploaded file. Please check storage permissions.');
            }

            return view('transport.import.preview', [
                'previewData' => $results['preview_data'],
                'conflicts' => $results['conflicts'],
                'feeConflicts' => $results['fee_conflicts'] ?? [],
                'missingStudents' => $results['missing_students'] ?? [],
                'errors' => $results['errors'],
                'skipped' => $results['skipped'],
                'filename' => $filename,
                'tempPath' => $path,
                'year' => $year,
                'term' => $term
            ]);

        } catch (\Exception $e) {
            Log::error('Transport import preview error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }

    /**
     * Process the import with conflict resolutions
     */
    public function import(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'conflict_resolutions' => 'nullable|array',
            'fee_conflict_resolutions' => 'nullable|array',
            'student_links' => 'nullable|array', // Manual student links
            'sync_transport_fees' => 'nullable|boolean',
            'year' => 'nullable|integer',
            'term' => 'nullable|integer|in:1,2,3',
        ]);

        DB::beginTransaction();
        
        try {
            $filePath = storage_path('app/temp/transport-imports/' . $request->filename);
            
            if (!file_exists($filePath)) {
                throw new \Exception('Import file not found. Please upload again.');
            }

            [$year, $term] = \App\Services\TransportFeeService::resolveYearAndTerm($request->year, $request->term);
            $syncTransportFees = $request->boolean('sync_transport_fees', false);

            // Process manual student links first (for students not found by name)
            if ($request->has('student_links')) {
                foreach ($request->student_links as $rowNumber => $studentId) {
                    if (!empty($studentId)) {
                        $linkData = $request->input("student_link_data.{$rowNumber}");
                        if ($linkData) {
                            $data = json_decode($linkData, true);
                            $student = Student::find($studentId);
                            
                            if ($student && isset($data['route']) && isset($data['vehicle'])) {
                                // Process this manually linked student
                                $this->processManualLink($student, $data, $year, $term);
                            }
                        }
                    }
                }
            }

            // Process conflict resolutions
            if ($request->has('conflict_resolutions')) {
                foreach ($request->conflict_resolutions as $studentId => $resolution) {
                    if ($resolution === 'use_excel') {
                        $conflictData = $request->input("conflict_data.{$studentId}");
                        if ($conflictData) {
                            $data = json_decode($conflictData, true);
                            
                            // Update student assignment with new drop-off point
                            $assignment = StudentAssignment::where('student_id', $studentId)->first();
                            if ($assignment && isset($data['drop_off_point_id'])) {
                                $assignment->update([
                                    'evening_drop_off_point_id' => $data['drop_off_point_id']
                                ]);
                            }
                        }
                    }
                    // If 'use_system', we don't need to do anything
                }
            }

            // Process transport fee conflict resolutions
            if ($request->has('fee_conflict_resolutions') && $syncTransportFees) {
                foreach ($request->fee_conflict_resolutions as $studentId => $resolution) {
                    if ($resolution === 'update_fee') {
                        $feeConflictData = $request->input("fee_conflict_data.{$studentId}");
                        if ($feeConflictData) {
                            $data = json_decode($feeConflictData, true);
                            
                            // Update transport fee with new drop-off point (preserve amount)
                            if (isset($data['transport_fee_id']) && isset($data['drop_off_point_id'])) {
                                $fee = \App\Models\TransportFee::find($data['transport_fee_id']);
                                if ($fee) {
                                    \App\Services\TransportFeeService::upsertFee([
                                        'student_id' => $studentId,
                                        'amount' => $fee->amount, // Preserve existing amount
                                        'year' => $year,
                                        'term' => $term,
                                        'drop_off_point_id' => $data['drop_off_point_id'],
                                        'drop_off_point_name' => $data['drop_off_point_name'] ?? null,
                                        'source' => 'import_sync',
                                        'note' => 'Drop-off point updated from transport assignment import',
                                        'skip_invoice' => $fee->amount == 0,
                                    ]);
                                }
                            }
                        }
                    }
                    // If 'keep_fee', we don't update the transport fee
                }
            }

            // Now process the full import
            $import = new TransportAssignmentImport(false, $syncTransportFees, $year, $term); // Actual import mode
            Excel::import($import, $filePath);

            $results = $import->getResults();

            // Log the import
            $log = TransportImportLog::create([
                'filename' => $request->filename,
                'imported_by' => Auth::id(),
                'total_rows' => $results['success'] + $results['updated'] + $results['skipped'] + count($results['errors']),
                'success_count' => $results['success'],
                'updated_count' => $results['updated'],
                'skipped_count' => $results['skipped'],
                'error_count' => count($results['errors']),
                'conflict_count' => count($results['conflicts']),
                'errors' => $results['errors'],
                'conflicts' => $results['conflicts'],
                'status' => count($results['errors']) > 0 ? 'completed' : 'completed'
            ]);

            // Clean up temp file
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            DB::commit();

            $message = "Import completed successfully! ";
            $message .= "Created: {$results['success']}, ";
            $message .= "Updated: {$results['updated']}, ";
            $message .= "Skipped: {$results['skipped']}";
            
            if (count($results['errors']) > 0) {
                $message .= ", Errors: " . count($results['errors']);
            }

            if ($syncTransportFees) {
                $message .= " (Transport fees synced)";
            }

            return redirect()->route('transport.import.form')
                ->with('success', $message)
                ->with('import_log_id', $log->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transport import error: ' . $e->getMessage());
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Show import log details
     */
    public function showLog($id)
    {
        $log = TransportImportLog::with('importedBy')->findOrFail($id);
        return view('transport.import.log', compact('log'));
    }

    /**
     * Process manually linked student
     */
    private function processManualLink($student, $data, $year, $term)
    {
        $route = strtoupper(trim($data['route'] ?? ''));
        $vehicleInfo = trim($data['vehicle'] ?? '');

        // Skip if OWN transport
        if (strtoupper($vehicleInfo) === 'OWN' || strtoupper($route) === 'OWN') {
            return;
        }

        // Parse vehicle and trip
        $parts = explode(' ', trim($vehicleInfo));
        if (count($parts) < 3) {
            return; // Invalid format
        }

        $vehicleNumberRaw = strtoupper(trim($parts[0]));
        $tripNumber = trim($parts[2] ?? '1');
        $vehicleCode = substr($vehicleNumberRaw, 0, 3);

        // Find vehicle
        $vehicle = Vehicle::where('vehicle_number', 'LIKE', $vehicleCode . '%')->first();
        if (!$vehicle) {
            $vehicle = Vehicle::where('vehicle_number', $vehicleNumberRaw)->first();
        }
        if (!$vehicle) {
            return; // Vehicle not found
        }

        // Find or create drop-off point
        $dropOffPoint = null;
        if (!empty($route) && strtoupper($route) !== 'OWN') {
            $dropOffPoint = DropOffPoint::firstOrCreate(['name' => $route]);
        }

        // Find or create trip
        $tripName = "{$vehicleCode} TRIP {$tripNumber}";
        $trip = Trip::firstOrCreate(
            [
                'vehicle_id' => $vehicle->id,
                'trip_name' => $tripName,
                'direction' => 'dropoff'
            ],
            [
                'day_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
            ]
        );

        // Create or update student assignment
        $assignmentData = [
            'student_id' => $student->id,
            'evening_trip_id' => $trip->id,
        ];

        if ($dropOffPoint) {
            $assignmentData['evening_drop_off_point_id'] = $dropOffPoint->id;
        }

        $existingAssignment = StudentAssignment::where('student_id', $student->id)->first();
        if ($existingAssignment) {
            $existingAssignment->update($assignmentData);
        } else {
            StudentAssignment::create($assignmentData);
        }
    }

    /**
     * Download template Excel file
     */
    public function downloadTemplate()
    {
        // Template requires NAME, ROUTE, and VEHICLE
        // Student is identified by name from the database
        $headers = [
            'NAME',
            'ROUTE',
            'VEHICLE'
        ];

        $sampleData = [
            ['John Doe', 'REGEN', 'KDR TRIP 1'],
            ['Jane Smith', 'RUKUBI', 'KCB TRIP 2'],
            ['Alice Brown', 'MUTHURE', 'KAQ TRIP 1'],
            ['Bob Wilson', 'OWN', 'OWN'],
        ];

        return Excel::download(new \App\Exports\ArrayExport($sampleData, $headers), 'transport_import_template.xlsx');
    }
}


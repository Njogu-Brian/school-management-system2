<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Imports\TransportAssignmentImport;
use App\Models\TransportImportLog;
use App\Models\Student;
use App\Models\StudentAssignment;
use App\Models\DropOffPoint;
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
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'year' => 'nullable|integer',
            'term' => 'nullable|integer|in:1,2,3',
        ]);

        try {
            [$year, $term] = \App\Services\TransportFeeService::resolveYearAndTerm($request->year, $request->term);
            
            $import = new TransportAssignmentImport(true, false, $year, $term); // Preview mode
            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            // Store file temporarily for actual import
            $filename = time() . '_' . $request->file('file')->getClientOriginalName();
            $path = $request->file('file')->storeAs('temp/transport-imports', $filename);

            return view('transport.import.preview', [
                'previewData' => $results['preview_data'],
                'conflicts' => $results['conflicts'],
                'feeConflicts' => $results['fee_conflicts'] ?? [],
                'errors' => $results['errors'],
                'skipped' => $results['skipped'],
                'filename' => $filename,
                'tempPath' => $path,
                'year' => $year,
                'term' => $term
            ]);

        } catch (\Exception $e) {
            Log::error('Transport import preview error: ' . $e->getMessage());
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

            // Process conflict resolutions first
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
     * Download template Excel file
     */
    public function downloadTemplate()
    {
        // Simplified template - only requires ADMISSION NO, ROUTE, and VEHICLE
        // Student name and class are fetched from database using admission number
        $headers = [
            'ADMISSION NO',
            'ROUTE',
            'VEHICLE'
        ];

        $sampleData = [
            ['RKS001', 'REGEN', 'KDR TRIP 1'],
            ['RKS002', 'RUKUBI', 'KCB TRIP 2'],
            ['RKS003', 'MUTHURE', 'KAQ TRIP 1'],
            ['RKS004', 'OWN', 'OWN'],
        ];

        return Excel::download(new \App\Exports\ArrayExport($sampleData, $headers), 'transport_import_template.xlsx');
    }
}


<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\DropOffPoint;
use App\Models\Student;
use App\Models\TransportFee;
use App\Services\TransportFeeService;
use App\Exports\ArrayExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class TransportFeeController extends Controller
{
    public function index(Request $request)
    {
        [$year, $term, $academicYearId] = TransportFeeService::resolveYearAndTerm($request->year, $request->term);

        $classroomId = $request->input('classroom_id');
        $classrooms = Classroom::orderBy('name')->get();
        $dropOffPoints = DropOffPoint::orderBy('name')->get();
        // Get all active students (not just those with existing transport fees)
        // This allows viewing and assigning transport fees to any student
        $students = Student::with(['classroom', 'stream', 'dropOffPoint', 'assignments.morningDropOffPoint', 'assignments.eveningDropOffPoint'])
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->when($classroomId, fn($q) => $q->where('classroom_id', $classroomId))
            ->orderBy('first_name')
            ->get();
            
        $feeStudentIds = TransportFee::where('year', $year)->where('term', $term)->pluck('student_id');

        $studentIds = $students->pluck('id');
        $feeMap = $studentIds->isEmpty() 
            ? collect() 
            : TransportFee::where('year', $year)
                ->where('term', $term)
                ->whereIn('student_id', $studentIds)
                ->get()
                ->keyBy('student_id');
            
        // Get student assignments for morning/evening drop-off points
        $assignmentMap = $studentIds->isEmpty()
            ? collect()
            : \App\Models\StudentAssignment::whereIn('student_id', $studentIds)
                ->get()
                ->keyBy('student_id');

        $totalAmount = $feeMap->sum('amount');
        
        // Get recent imports for this term/year (for sidebar display)
        $recentImports = \App\Models\TransportFeeImport::with('importedBy', 'reversedBy')
            ->where('year', $year)
            ->where('term', $term)
            ->orderBy('imported_at', 'desc')
            ->limit(5)
            ->get();

        return view('finance.transport_fees.index', [
            'classrooms' => $classrooms,
            'classroomId' => $classroomId,
            'students' => $students,
            'feeMap' => $feeMap,
            'assignmentMap' => $assignmentMap,
            'dropOffPoints' => $dropOffPoints,
            'year' => $year,
            'term' => $term,
            'academicYearId' => $academicYearId,
            'totalAmount' => $totalAmount,
            'recentImports' => $recentImports,
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer',
            'term' => 'nullable|integer|in:1,2,3',
            'fees' => 'array',
        ]);

        [$year, $term] = TransportFeeService::resolveYearAndTerm($request->year, $request->term);
        $updated = 0;

        foreach ($request->input('fees', []) as $studentId => $row) {
            $amount = $row['amount'] ?? null;
            $amount = ($amount === '' || $amount === null) ? null : (is_numeric($amount) ? (float) $amount : null);

            $dropOffPointId = $row['drop_off_point_id'] ?? null;
            $dropOffPointName = $row['drop_off_point_name'] ?? null;
            
            // Handle morning and evening drop-off points
            $morningDropOffPointId = $row['morning_drop_off_point_id'] ?? null;
            $eveningDropOffPointId = $row['evening_drop_off_point_id'] ?? null;

            try {
                // Update transport fee (even if amount is null - will create drop-off point only)
                if ($dropOffPointId || $dropOffPointName || $amount !== null) {
                    TransportFeeService::upsertFee([
                        'student_id' => $studentId,
                        'amount' => $amount ?? 0,
                        'year' => $year,
                        'term' => $term,
                        'drop_off_point_id' => $dropOffPointId ?: null,
                        'drop_off_point_name' => $dropOffPointName,
                        'source' => 'manual',
                        'note' => 'Updated from transport fee class view',
                        'skip_invoice' => $amount === null || $amount == 0,
                    ]);
                }
                
                // Update student assignment for morning/evening drop-off points
                $assignment = \App\Models\StudentAssignment::where('student_id', $studentId)->first();
                if ($assignment) {
                    $assignment->update([
                        'morning_drop_off_point_id' => $morningDropOffPointId ?: null,
                        'evening_drop_off_point_id' => $eveningDropOffPointId ?: null,
                    ]);
                } elseif ($morningDropOffPointId || $eveningDropOffPointId) {
                    // Create assignment if it doesn't exist but we have drop-off points
                    \App\Models\StudentAssignment::create([
                        'student_id' => $studentId,
                        'morning_drop_off_point_id' => $morningDropOffPointId ?: null,
                        'evening_drop_off_point_id' => $eveningDropOffPointId ?: null,
                    ]);
                }
                
                $updated++;
            } catch (\Throwable $e) {
                Log::warning('Transport fee update failed', [
                    'student_id' => $studentId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', "{$updated} transport fee(s) and drop-off point(s) updated for Term {$term}, {$year}.");
    }

    public function importPreview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
            'year' => 'nullable|integer',
            'term' => 'nullable|integer|in:1,2,3',
        ]);

        [$year, $term] = TransportFeeService::resolveYearAndTerm($request->year, $request->term);

        $sheet = Excel::toArray([], $request->file('file'))[0] ?? [];

        if (empty($sheet)) {
            return back()->with('error', 'The uploaded file is empty.');
        }

        $headerRow = array_shift($sheet);
        $headers = [];
        foreach ($headerRow as $index => $header) {
            $headers[$index] = Str::slug(Str::lower(trim((string) $header)), '_');
        }

        $dropOffPoints = DropOffPoint::orderBy('name')->get();
        $preview = [];
        $missingDropOffs = [];
        $studentsToMatch = [];
        $total = 0;

        foreach ($sheet as $rowIndex => $row) {
            $assoc = [];
            foreach ($headers as $i => $key) {
                $assoc[$key] = $row[$i] ?? null;
            }

            $admission = trim((string) ($assoc['admission_number'] ?? $assoc['admission_no'] ?? $assoc['adm_no'] ?? ''));
            $student = $admission
                ? Student::where('admission_number', $admission)->first()
                : null;

            $matchedStudents = [];
            if (!$student) {
                $studentName = trim((string) ($assoc['student_name'] ?? $assoc['name'] ?? ''));
                if ($studentName !== '') {
                    // Try exact match first
                    $student = Student::whereRaw('LOWER(CONCAT(first_name," ",last_name)) = ?', [Str::lower($studentName)])->first();
                    
                    // If still not found and no admission number, find potential matches
                    if (!$student && !$admission) {
                        $nameParts = explode(' ', $studentName, 2);
                        if (count($nameParts) >= 2) {
                            $matchedStudents = Student::whereRaw('LOWER(first_name) = ?', [Str::lower($nameParts[0])])
                                ->whereRaw('LOWER(last_name) LIKE ?', [Str::lower($nameParts[1] ?? '') . '%'])
                                ->limit(10)
                                ->get();
                        } else {
                            $matchedStudents = Student::whereRaw('LOWER(first_name) LIKE ? OR LOWER(last_name) LIKE ?', [
                                Str::lower($studentName) . '%',
                                Str::lower($studentName) . '%'
                            ])
                            ->limit(10)
                            ->get();
                        }
                        
                        if ($matchedStudents->count() === 1) {
                            $student = $matchedStudents->first();
                        } elseif ($matchedStudents->count() > 1) {
                            // Store for user to select
                            $studentsToMatch[] = [
                                'row_index' => $rowIndex,
                                'student_name' => $studentName,
                                'admission_number' => $admission,
                                'matched_students' => $matchedStudents,
                            ];
                        }
                    }
                }
            }

            // Try multiple column name variations for amount
            $amountField = $assoc['transport_fee'] ?? $assoc['fee'] ?? $assoc['amount'] 
                ?? $assoc['transport_amount'] ?? $assoc['fee_amount'] ?? $assoc['charge'] 
                ?? $assoc['transport_charge'] ?? null;
            
            // Parse amount - handle formatted numbers (currency symbols, commas, whitespace)
            $amount = null;
            if ($amountField !== null && $amountField !== '') {
                // Remove currency symbols, commas, whitespace
                $cleaned = trim((string) $amountField);
                $cleaned = preg_replace('/[^\d.-]/', '', $cleaned); // Remove everything except digits, dots, and minus
                
                if ($cleaned !== '' && is_numeric($cleaned)) {
                    $amount = (float) $cleaned;
                    // Ensure amount is not negative (fees shouldn't be negative)
                    if ($amount < 0) {
                        $amount = null;
                    }
                }
            }

            $dropName = $assoc['drop_off_point'] ?? $assoc['dropoff_point'] ?? $assoc['drop_point'] ?? null;
            $dropName = $dropName ? trim((string) $dropName) : null;
            
            // Check if drop-off point indicates "own means" transport
            $isOwnMeans = self::isOwnMeans($dropName);
            
            $matchedDrop = null;
            if ($dropName && !$isOwnMeans) {
                $matchedDrop = $dropOffPoints->first(fn($p) => Str::lower($p->name) === Str::lower($dropName));
                if (!$matchedDrop) {
                    $missingDropOffs[] = $dropName;
                }
            }

            $status = 'ok';
            $message = null;
            $changeType = 'new'; // new, existing, changed_amount, changed_dropoff, changed_both
            $existingAmount = null;
            $existingDropOffPointId = null;
            $existingDropOffPointName = null;
            $needsConfirmation = false;
            
            if (!$student) {
                if (!empty($matchedStudents)) {
                    $status = 'needs_matching';
                    $message = 'Multiple students found - please select';
                } else {
                    $status = 'missing_student';
                    $message = 'Student not found';
                }
            } elseif ($isOwnMeans) {
                $status = 'own_means';
                $message = 'Own means transport (no fee)';
            } elseif ($amount === null) {
                $status = 'missing_amount';
                $message = 'Amount is missing or invalid (will create drop-off point only)';
            } else {
                // Check if transport fee already exists for this student/year/term
                $existingTransportFee = \App\Models\TransportFee::where('student_id', $student->id)
                    ->where('year', $year)
                    ->where('term', $term)
                    ->first();
                
                if ($existingTransportFee) {
                    $existingAmount = (float) $existingTransportFee->amount;
                    $existingDropOffPointId = $existingTransportFee->drop_off_point_id;
                    $existingDropOffPointName = $existingTransportFee->drop_off_point_name;
                    
                    $amountChanged = abs($existingAmount - $amount) >= 0.01;
                    $dropOffChanged = false;
                    
                    if ($dropName && !$isOwnMeans) {
                        $dropOffChanged = ($matchedDrop?->id ?? null) !== $existingDropOffPointId 
                            && Str::lower($dropName) !== Str::lower($existingDropOffPointName ?? '');
                    } elseif (!$dropName && $existingDropOffPointId) {
                        $dropOffChanged = true;
                    } elseif ($dropName && $existingDropOffPointId && !$matchedDrop) {
                        // New drop-off point name vs existing
                        $dropOffChanged = Str::lower($dropName) !== Str::lower($existingDropOffPointName ?? '');
                    }
                    
                    if (!$amountChanged && !$dropOffChanged) {
                        $changeType = 'existing';
                        $status = 'already_billed';
                        $message = 'Already billed';
                    } else {
                        if ($amountChanged && $dropOffChanged) {
                            $changeType = 'changed_both';
                            $message = "Amount: " . number_format($existingAmount, 2) . " → " . number_format($amount, 2) . 
                                      " | Drop-off: " . ($existingDropOffPointName ?? 'None') . " → " . ($dropName ?? 'None');
                        } elseif ($amountChanged) {
                            $changeType = 'changed_amount';
                            $message = "Amount changed: " . number_format($existingAmount, 2) . " → " . number_format($amount, 2);
                        } else {
                            $changeType = 'changed_dropoff';
                            $message = "Drop-off changed: " . ($existingDropOffPointName ?? 'None') . " → " . ($dropName ?? 'None');
                        }
                        $needsConfirmation = true;
                    }
                } else {
                    $changeType = 'new';
                }
            }

            if ($status === 'ok' || $needsConfirmation) {
                $total += $amount ?? 0;
            }

            $preview[] = [
                'student_id' => $student?->id,
                'student_name' => $student?->full_name ?? ($assoc['student_name'] ?? $assoc['name'] ?? null),
                'admission_number' => $admission ?: ($student?->admission_number ?? null),
                'amount' => $amount,
                'existing_amount' => $existingAmount,
                'drop_off_point_id' => $matchedDrop?->id,
                'drop_off_point_name' => $dropName,
                'existing_drop_off_point_id' => $existingDropOffPointId,
                'existing_drop_off_point_name' => $existingDropOffPointName,
                'is_own_means' => $isOwnMeans,
                'change_type' => $changeType,
                'needs_confirmation' => $needsConfirmation,
                'status' => $status,
                'message' => $message,
                'matched_students' => !empty($matchedStudents) ? $matchedStudents->map(function($s) {
                    return [
                        'id' => $s->id,
                        'name' => $s->full_name,
                        'admission_number' => $s->admission_number,
                    ];
                })->toArray() : null,
                'original_row_data' => $assoc, // Store original data for student search
            ];
        }

        $missingDropOffs = collect($missingDropOffs)->filter()->unique()->values();

        return view('finance.transport_fees.import_preview', [
            'preview' => $preview,
            'dropOffPoints' => $dropOffPoints,
            'missingDropOffs' => $missingDropOffs,
            'studentsToMatch' => $studentsToMatch,
            'year' => $year,
            'term' => $term,
            'total' => $total,
        ]);
    }

    public function importCommit(Request $request)
    {
        $request->validate([
            'rows' => 'required|array',
            'dropoff_map' => 'array',
            'student_matches' => 'nullable|array', // For student matching: row_index => student_id
            'confirmations' => 'nullable|array', // For confirmations: row_index => 'use_new' or 'keep_existing'
            'skip_rows' => 'nullable|array', // For skipping rows: row_index => 1
            'year' => 'required|integer',
            'term' => 'required|integer|in:1,2,3',
        ]);

        [$year, $term, $academicYearId] = TransportFeeService::resolveYearAndTerm($request->year, $request->term);
        $map = $request->input('dropoff_map', []);
        $studentMatches = $request->input('student_matches', []);
        $confirmations = $request->input('confirmations', []);
        $skipRows = $request->input('skip_rows', []);
        $createdOrUpdated = 0;
        $skipped = 0;
        $failed = 0;
        $dropOffPointsCreated = 0;
        $totalAmount = 0;

        $dropCache = DropOffPoint::orderBy('name')->get()->keyBy(function ($p) {
            return Str::lower($p->name);
        });
        
        // Create import batch record
        $importBatch = \App\Models\TransportFeeImport::create([
            'year' => $year,
            'term' => $term,
            'academic_year_id' => $academicYearId,
            'imported_by' => auth()->id(),
            'imported_at' => now(),
            'is_reversed' => false,
        ]);

        foreach ($request->rows as $index => $encoded) {
            $row = json_decode(base64_decode($encoded), true);
            
            if (!$row) {
                continue;
            }

            // Handle student matching
            $studentId = $row['student_id'] ?? null;
            if (!$studentId && isset($studentMatches[$index])) {
                $studentId = $studentMatches[$index];
            }
            
            // Handle missing student - check if student was selected via search or if row should be skipped
            if (!$studentId && ($row['status'] ?? '') === 'missing_student') {
                // Check if row is marked to skip
                if (isset($skipRows[$index]) && $skipRows[$index] == '1') {
                    $skipped++;
                    continue; // Skip this row
                }
                
                // Check if student was selected via student_matches for missing students
                if (isset($studentMatches[$index])) {
                    $studentId = $studentMatches[$index];
                } else {
                    // Skip rows without valid student (unless explicitly marked to skip)
                    $skipped++;
                    continue;
                }
            }
            
            // Skip rows without valid student (after checking for selection)
            if (!$studentId) {
                // Check if row is marked to skip
                if (isset($skipRows[$index]) && $skipRows[$index] == '1') {
                    $skipped++;
                    continue;
                }
                $skipped++;
                continue;
            }

            // Determine change type and whether there are actual changes
            $changeType = $row['change_type'] ?? '';
            $isAlreadyBilled = ($row['status'] ?? '') === 'already_billed';
            $hasChanges = in_array($changeType, ['changed_amount', 'changed_dropoff', 'changed_both', 'new']);
            
            // Skip ONLY if it's already billed AND has NO changes (change_type is 'existing')
            // Records with changes (changed_amount, changed_dropoff, changed_both, new) should always be updated
            if ($isAlreadyBilled && $changeType === 'existing') {
                $skipped++;
                continue;
            }

            // Handle confirmations for changes
            // If there are changes, we should process them even if confirmation wasn't explicitly provided
            // (user wants to update, so proceed with changes)
            if (($row['needs_confirmation'] ?? false) && isset($confirmations[$index])) {
                $confirmation = $confirmations[$index];
                if ($confirmation === 'keep_existing') {
                    $skipped++;
                    continue; // Skip this row, keep existing
                }
                // If 'use_new', proceed with update
            } elseif (($row['needs_confirmation'] ?? false)) {
                // If needs confirmation but not provided:
                // - If there are changes, proceed anyway (user wants updates)
                // - If no changes (existing), skip
                if ($hasChanges) {
                    // Proceed with update even without explicit confirmation
                    // User wants changes, so apply them
                } else {
                    // No changes and needs confirmation but not provided - skip
                    continue;
                }
            }

            $status = $row['status'] ?? '';
            $dropName = $row['drop_off_point_name'] ?? null;
            $dropId = $row['drop_off_point_id'] ?? null;
            $isOwnMeans = $row['is_own_means'] ?? false;
            
            // Get amount - handle numeric values properly (may come as string or number from JSON)
            // JSON decode should preserve float values, but handle both cases
            $amount = null;
            if (isset($row['amount']) && $row['amount'] !== null && $row['amount'] !== '') {
                $amountValue = $row['amount'];
                
                // If it's already a numeric type (int or float), use it directly
                if (is_numeric($amountValue)) {
                    $amount = (float) $amountValue;
                    // Ensure amount is not negative
                    if ($amount < 0) {
                        $amount = null;
                    }
                } 
                // If it's a string, try to parse it
                elseif (is_string($amountValue)) {
                    // Remove formatting (commas, currency symbols, etc.)
                    $cleaned = preg_replace('/[^\d.-]/', '', $amountValue);
                    if ($cleaned !== '' && is_numeric($cleaned)) {
                        $amount = (float) $cleaned;
                        // Ensure amount is not negative
                        if ($amount < 0) {
                            $amount = null;
                        }
                    }
                }
            }

            // Resolve drop-off point (unless it's own means)
            if (!$dropId && $dropName && !$isOwnMeans) {
                $key = Str::lower($dropName);
                $selection = $map[$key] ?? null;

                if ($selection === 'create') {
                    $created = TransportFeeService::resolveDropOffPoint($dropName);
                    $dropId = $created?->id;
                    if ($created) {
                        $dropCache[$key] = $created;
                        $dropOffPointsCreated++;
                    }
                } elseif ($selection && is_numeric($selection)) {
                    $dropId = (int) $selection;
                } elseif ($dropCache->has($key)) {
                    $dropId = $dropCache[$key]->id;
                }
            }

            try {
                // Determine if we should skip invoice creation
                // Only create invoice items for rows with status 'ok' AND valid amount > 0
                // Skip invoice for: own means, missing amounts, or any status other than 'ok'
                $shouldSkipInvoice = $isOwnMeans 
                    || $status === 'missing_amount' 
                    || $status === 'own_means' 
                    || $status !== 'ok'  // Only 'ok' status should create invoice items
                    || $amount === null 
                    || $amount <= 0;
                
                // For own means, set amount to 0
                // For missing amounts, also set to 0 but skip invoice
                // For valid amounts (status 'ok'), use the actual amount
                // For 'new' entries, always use the new amount from import
                $finalAmount = $isOwnMeans ? 0 : ($amount ?? 0);
                
                // Ensure new entries use the new amount (not existing amount)
                if (($row['change_type'] ?? '') === 'new' && $amount !== null && $amount > 0) {
                    $finalAmount = $amount;
                }
                
                // Log for debugging (only for status 'ok' to see what's happening)
                if ($status === 'ok') {
                    Log::info('Transport fee import processing', [
                        'student_id' => $studentId,
                        'status' => $status,
                        'raw_amount' => $row['amount'] ?? 'not set',
                        'parsed_amount' => $amount,
                        'final_amount' => $finalAmount,
                        'skip_invoice' => $shouldSkipInvoice,
                    ]);
                }
                
                TransportFeeService::upsertFee([
                    'student_id' => $studentId,
                    'amount' => $finalAmount,
                    'year' => $year,
                    'term' => $term,
                    'drop_off_point_id' => $dropId,
                    'drop_off_point_name' => $isOwnMeans ? 'OWN MEANS' : $dropName,
                    'source' => 'import',
                    'note' => $isOwnMeans ? 'Own means transport (no fee)' : ($amount === null || $status === 'missing_amount' ? 'Imported from transport fee upload - amount missing, drop-off point only' : 'Imported from transport fee upload'),
                    'skip_invoice' => $shouldSkipInvoice,
                ]);
                $createdOrUpdated++;
                
                // Only count amount for valid fees (status 'ok' with amount > 0)
                if ($status === 'ok' && $amount !== null && $amount > 0) {
                    $totalAmount += $amount;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Transport fee import failed', [
                    'student_id' => $studentId ?? null,
                    'status' => $status,
                    'amount' => $amount,
                    'raw_amount' => $row['amount'] ?? 'not set',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Continue processing other rows even if one fails
            }
        }

        // Update import batch with final counts
        $importBatch->update([
            'fees_imported_count' => $createdOrUpdated,
            'drop_off_points_created_count' => $dropOffPointsCreated,
            'total_amount' => $totalAmount,
        ]);

        $message = "{$createdOrUpdated} transport fee(s) imported successfully";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped (already billed or kept existing)";
        }
        if ($dropOffPointsCreated > 0) {
            $message .= ", {$dropOffPointsCreated} drop-off point(s) created";
        }
        if ($failed > 0) {
            $message .= ", {$failed} failed (check logs for details)";
        }
        $message .= " for Term {$term}, {$year}.";

        $alertType = $failed > 0 ? 'warning' : 'success';

        return redirect()
            ->route('finance.transport-fees.index', ['term' => $term, 'year' => $year])
            ->with($alertType, $message)
            ->with('import_batch_id', $importBatch->id);
    }
    
    public function reverseImport(\App\Models\TransportFeeImport $import)
    {
        try {
            if ($import->is_reversed) {
                return back()->with('error', 'This import has already been reversed.');
            }

            $result = TransportFeeService::reverseImport($import);

            return redirect()
                ->route('finance.transport-fees.index', ['term' => $import->term, 'year' => $import->year])
                ->with('success', "Transport fee import #{$import->id} reversed successfully. {$result['items_deleted']} invoice items deleted and {$result['assignments_deleted']} drop-off point assignments removed.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reverse import: ' . $e->getMessage());
        }
    }

    /**
     * Check if a drop-off point name indicates "own means" transport.
     */
    private static function isOwnMeans(?string $dropName): bool
    {
        if (!$dropName) {
            return false;
        }

        $normalized = Str::upper(trim($dropName));
        $ownMeansVariants = ['OWN', 'OWNMEANS', 'OWN MEANS', 'OWN MEAN', 'OWN TRANSPORT'];

        return in_array($normalized, $ownMeansVariants) || 
               Str::startsWith($normalized, 'OWN') && Str::contains($normalized, 'MEAN');
    }

    /**
     * Download a blank import template for transport fees.
     */
    public function template()
    {
        $headers = ['Admission Number', 'Student Name', 'Transport Fee', 'Drop-off Point'];
        $sample = [
            ['ADM001', 'Jane Doe', 3500, 'Gate A'],
            ['ADM002', 'John Doe', 4000, 'Town Pickup'],
        ];

        return Excel::download(new ArrayExport($sample, $headers), 'transport_fees_template.xlsx');
    }

    /**
     * Show import history for transport fees
     */
    public function importHistory(Request $request)
    {
        $year = $request->input('year');
        $term = $request->input('term');
        
        $imports = \App\Models\TransportFeeImport::with(['importedBy', 'reversedBy', 'academicYear', 'term'])
            ->when($year, fn($q) => $q->where('year', $year))
            ->when($term, fn($q) => $q->where('term', $term))
            ->orderBy('imported_at', 'desc')
            ->paginate(20);

        return view('finance.transport_fees.import_history', [
            'imports' => $imports,
            'year' => $year,
            'term' => $term,
        ]);
    }

    /**
     * Show details of a specific import
     */
    public function showImport(\App\Models\TransportFeeImport $import)
    {
        $import->load(['importedBy', 'reversedBy', 'academicYear', 'term']);
        
        // Get transport fees created in this import period
        $transportFees = TransportFee::where('year', $import->year)
            ->where('term', $import->term)
            ->where('created_at', '>=', $import->imported_at->subSeconds(1))
            ->where('created_at', '<=', $import->imported_at->addMinutes(5))
            ->with(['student.classroom', 'dropOffPoint'])
            ->get();

        return view('finance.transport_fees.import_details', [
            'import' => $import,
            'transportFees' => $transportFees,
        ]);
    }

    /**
     * Show import processing view
     */
    public function importView()
    {
        return view('finance.transport_fees.import');
    }
}


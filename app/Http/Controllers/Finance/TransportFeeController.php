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
        
        // Get transport fee imports for this term/year
        $imports = \App\Models\TransportFeeImport::with('importedBy', 'reversedBy')
            ->where('year', $year)
            ->where('term', $term)
            ->orderBy('imported_at', 'desc')
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
            'imports' => $imports,
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
        $total = 0;

        foreach ($sheet as $row) {
            $assoc = [];
            foreach ($headers as $i => $key) {
                $assoc[$key] = $row[$i] ?? null;
            }

            $admission = trim((string) ($assoc['admission_number'] ?? $assoc['admission_no'] ?? $assoc['adm_no'] ?? ''));
            $student = $admission
                ? Student::where('admission_number', $admission)->first()
                : null;

            if (!$student) {
                $studentName = trim((string) ($assoc['student_name'] ?? $assoc['name'] ?? ''));
                if ($studentName !== '') {
                    $student = Student::whereRaw('LOWER(CONCAT(first_name," ",last_name)) = ?', [Str::lower($studentName)])->first();
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
            if (!$student) {
                $status = 'missing_student';
                $message = 'Student not found by admission number';
            } elseif ($isOwnMeans) {
                $status = 'own_means';
                $message = 'Own means transport (no fee)';
            } elseif ($amount === null) {
                $status = 'missing_amount';
                $message = 'Amount is missing or invalid (will create drop-off point only)';
            }

            if ($status === 'ok') {
                $total += $amount;
            }

            $preview[] = [
                'student_id' => $student?->id,
                'student_name' => $student?->full_name ?? ($assoc['student_name'] ?? $assoc['name'] ?? null),
                'admission_number' => $admission ?: ($student?->admission_number ?? null),
                'amount' => $amount,
                'drop_off_point_id' => $matchedDrop?->id,
                'drop_off_point_name' => $dropName,
                'is_own_means' => $isOwnMeans,
                'status' => $status,
                'message' => $message,
            ];
        }

        $missingDropOffs = collect($missingDropOffs)->filter()->unique()->values();

        return view('finance.transport_fees.import_preview', [
            'preview' => $preview,
            'dropOffPoints' => $dropOffPoints,
            'missingDropOffs' => $missingDropOffs,
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
            'year' => 'required|integer',
            'term' => 'required|integer|in:1,2,3',
        ]);

        [$year, $term, $academicYearId] = TransportFeeService::resolveYearAndTerm($request->year, $request->term);
        $map = $request->input('dropoff_map', []);
        $createdOrUpdated = 0;
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

        foreach ($request->rows as $encoded) {
            $row = json_decode(base64_decode($encoded), true);
            
            // Skip rows without valid student
            if (!$row || empty($row['student_id'])) {
                continue;
            }

            // Skip rows where student is not found
            if (($row['status'] ?? '') === 'missing_student') {
                continue;
            }

            $dropName = $row['drop_off_point_name'] ?? null;
            $dropId = $row['drop_off_point_id'] ?? null;
            $isOwnMeans = $row['is_own_means'] ?? false;
            $amount = $row['amount'] ?? null;

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
                // For own means or missing amounts, create drop-off point info but skip invoice
                TransportFeeService::upsertFee([
                    'student_id' => $row['student_id'],
                    'amount' => $isOwnMeans ? 0 : ($amount ?? 0),
                    'year' => $year,
                    'term' => $term,
                    'drop_off_point_id' => $dropId,
                    'drop_off_point_name' => $isOwnMeans ? 'OWN MEANS' : $dropName,
                    'source' => 'import',
                    'note' => $isOwnMeans ? 'Own means transport (no fee)' : ($amount === null ? 'Imported from transport fee upload - amount missing, drop-off point only' : 'Imported from transport fee upload'),
                    'skip_invoice' => $isOwnMeans || $amount === null, // Skip invoice for own means or missing amounts
                ]);
                $createdOrUpdated++;
                if ($amount && $amount > 0) {
                    $totalAmount += $amount;
                }
            } catch (\Throwable $e) {
                Log::warning('Transport fee import failed', [
                    'student_id' => $row['student_id'],
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Update import batch with final counts
        $importBatch->update([
            'fees_imported_count' => $createdOrUpdated,
            'drop_off_points_created_count' => $dropOffPointsCreated,
            'total_amount' => $totalAmount,
        ]);

        return redirect()
            ->route('finance.transport-fees.index', ['term' => $term, 'year' => $year])
            ->with('success', "{$createdOrUpdated} transport fee(s) and drop-off point(s) applied for Term {$term}, {$year}.")
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
}


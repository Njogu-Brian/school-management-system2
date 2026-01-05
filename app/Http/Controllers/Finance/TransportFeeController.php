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
        $feeStudentIds = TransportFee::where('year', $year)->where('term', $term)->pluck('student_id');

        $students = Student::with(['classroom', 'stream', 'dropOffPoint'])
            ->when($classroomId, fn($q) => $q->where('classroom_id', $classroomId))
            ->where(function ($q) use ($feeStudentIds) {
                $q->whereNotNull('drop_off_point_id')
                    ->orWhereNotNull('trip_id')
                    ->orWhereIn('id', $feeStudentIds);
            })
            ->orderBy('first_name')
            ->get();

        $feeMap = TransportFee::where('year', $year)
            ->where('term', $term)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $totalAmount = $feeMap->sum('amount');

        return view('finance.transport_fees.index', [
            'classrooms' => $classrooms,
            'classroomId' => $classroomId,
            'students' => $students,
            'feeMap' => $feeMap,
            'dropOffPoints' => $dropOffPoints,
            'year' => $year,
            'term' => $term,
            'academicYearId' => $academicYearId,
            'totalAmount' => $totalAmount,
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
            if (!array_key_exists('amount', $row)) {
                continue;
            }

            $amount = $row['amount'];
            if ($amount === '' || $amount === null || !is_numeric($amount)) {
                continue;
            }

            $dropOffPointId = $row['drop_off_point_id'] ?? null;
            $dropOffPointName = $row['drop_off_point_name'] ?? null;

            try {
                TransportFeeService::upsertFee([
                    'student_id' => $studentId,
                    'amount' => $amount,
                    'year' => $year,
                    'term' => $term,
                    'drop_off_point_id' => $dropOffPointId ?: null,
                    'drop_off_point_name' => $dropOffPointName,
                    'source' => 'manual',
                    'note' => 'Updated from transport fee class view',
                ]);
                $updated++;
            } catch (\Throwable $e) {
                Log::warning('Transport fee update failed', [
                    'student_id' => $studentId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', "{$updated} transport fee(s) updated for Term {$term}, {$year}.");
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

            $amountField = $assoc['transport_fee'] ?? $assoc['fee'] ?? $assoc['amount'] ?? null;
            $amount = is_numeric($amountField) ? (float) $amountField : null;

            $dropName = $assoc['drop_off_point'] ?? $assoc['dropoff_point'] ?? $assoc['drop_point'] ?? null;
            $dropName = $dropName ? trim((string) $dropName) : null;
            $matchedDrop = null;
            if ($dropName) {
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
            } elseif ($amount === null) {
                $status = 'missing_amount';
                $message = 'Amount is missing or invalid';
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

        [$year, $term] = TransportFeeService::resolveYearAndTerm($request->year, $request->term);
        $map = $request->input('dropoff_map', []);
        $createdOrUpdated = 0;

        $dropCache = DropOffPoint::orderBy('name')->get()->keyBy(function ($p) {
            return Str::lower($p->name);
        });

        foreach ($request->rows as $encoded) {
            $row = json_decode(base64_decode($encoded), true);
            if (!$row || ($row['status'] ?? '') !== 'ok' || empty($row['student_id'])) {
                continue;
            }

            $dropName = $row['drop_off_point_name'] ?? null;
            $dropId = $row['drop_off_point_id'] ?? null;

            if (!$dropId && $dropName) {
                $key = Str::lower($dropName);
                $selection = $map[$key] ?? null;

                if ($selection === 'create') {
                    $created = TransportFeeService::resolveDropOffPoint($dropName);
                    $dropId = $created?->id;
                    if ($created) {
                        $dropCache[$key] = $created;
                    }
                } elseif ($selection && is_numeric($selection)) {
                    $dropId = (int) $selection;
                } elseif ($dropCache->has($key)) {
                    $dropId = $dropCache[$key]->id;
                }
            }

            try {
                TransportFeeService::upsertFee([
                    'student_id' => $row['student_id'],
                    'amount' => $row['amount'],
                    'year' => $year,
                    'term' => $term,
                    'drop_off_point_id' => $dropId,
                    'drop_off_point_name' => $dropName,
                    'source' => 'import',
                    'note' => 'Imported from transport fee upload',
                ]);
                $createdOrUpdated++;
            } catch (\Throwable $e) {
                Log::warning('Transport fee import failed', [
                    'student_id' => $row['student_id'],
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('finance.transport-fees.index', ['term' => $term, 'year' => $year])
            ->with('success', "{$createdOrUpdated} transport fee(s) applied for Term {$term}, {$year}.");
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


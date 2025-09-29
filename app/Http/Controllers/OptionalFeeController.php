<?php

namespace App\Http\Controllers;

use App\Models\Academics\Classroom;
use App\Models\FeeCharge;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OptionalFee;
use App\Models\Student;
use App\Models\Votehead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocumentNumberService;

class OptionalFeeController extends Controller
{
    public function index()
    {
        return $this->loadIndexData();
    }

    public function classView(Request $request)
    {
        return $this->loadIndexData($request, 'class');
    }

    public function studentView(Request $request)
    {
        return $this->loadIndexData($request, 'student');
    }

    private function loadIndexData(Request $request = null, $view = 'class')
    {
        $classrooms = Classroom::all();
        $optionalVoteheads = Votehead::where('is_mandatory', false)->get();
        $allStudents = Student::select('id', 'first_name', 'last_name', 'admission_number')->get()
            ->sortBy(fn($s) => $s->full_name);

        $students = [];
        $student = null;
        $statuses = [];
        $term = $request?->term;
        $year = $request?->year;

        if ($view === 'class' && $request?->filled(['classroom_id', 'term', 'year', 'votehead_id'])) {
            $students = Student::where('classroom_id', $request->classroom_id)->get();
            $studentIds = $students->pluck('id');

            $billed = OptionalFee::where('votehead_id', $request->votehead_id)
                ->where('term', $term)
                ->where('year', $year)
                ->whereIn('student_id', $studentIds)
                ->pluck('status', 'student_id')
                ->toArray();

            foreach ($studentIds as $id) {
                $statuses[$id] = $billed[$id] ?? 'exempt';
            }
        }

        if ($view === 'student' && $request?->filled(['student_id', 'term', 'year'])) {
            $student = Student::find($request->student_id);
            if ($student) {
                $statuses = OptionalFee::where('student_id', $student->id)
                    ->where('term', $term)
                    ->where('year', $year)
                    ->pluck('status', 'votehead_id')
                    ->toArray();
            }
        }

        return view('finance.optional_fees.index', compact(
            'view', 'classrooms', 'optionalVoteheads', 'allStudents',
            'students', 'student', 'statuses', 'term', 'year'
        ));
    }

   public function saveClassBilling(Request $request)
    {
        $request->validate([
            'votehead_id' => 'required|exists:voteheads,id',
            'term' => 'required|in:1,2,3',
            'year' => 'required|integer',
            'students' => 'array',
        ]);

        DB::transaction(function () use ($request) {
            $voteheadId = $request->votehead_id;
            $term = $request->term;
            $year = $request->year;

            $firstStudent = Student::find(array_key_first($request->students));
            $classroomId = $firstStudent->classroom_id;

            $structure = \App\Models\FeeStructure::where('classroom_id', $classroomId)
                ->where('year', $year)
                ->first();

            $amount = \App\Models\FeeCharge::where('fee_structure_id', $structure->id)
                ->where('votehead_id', $voteheadId)
                ->where('term', $term)
                ->value('amount') ?? 0;

            foreach ($request->students as $studentId => $status) {
                if ($status === 'billed') {
                    OptionalFee::updateOrCreate([
                        'student_id' => $studentId,
                        'votehead_id' => $voteheadId,
                        'term' => $term,
                        'year' => $year,
                    ], [
                        'status' => 'billed',
                        'amount' => $amount,
                    ]);

                    $invoice = \App\Models\Invoice::firstOrCreate([
                        'student_id' => $studentId,
                        'term' => $term,
                        'year' => $year,
                    ], [
                        'invoice_number' => \App\Services\DocumentNumberService::generate('invoice', 'INV'),
                        'total' => 0,
                    ]);

                    \App\Models\InvoiceItem::updateOrCreate(
                        [
                            'invoice_id' => $invoice->id,
                            'votehead_id' => $voteheadId,
                        ],
                        [
                            'amount' => $amount,
                        ]
                    );

                    $invoice->update([
                        'total' => $invoice->items()->sum('amount'),
                    ]);
                } else {
                    // ❌ Delete optional fee record
                    OptionalFee::where([
                        'student_id' => $studentId,
                        'votehead_id' => $voteheadId,
                        'term' => $term,
                        'year' => $year,
                    ])->delete();

                    $invoice = \App\Models\Invoice::where([
                        'student_id' => $studentId,
                        'term' => $term,
                        'year' => $year,
                    ])->first();

                    if ($invoice) {
                        $invoice->items()->where('votehead_id', $voteheadId)->delete();

                        if ($invoice->items()->count() === 0) {
                            $invoice->delete();
                        } else {
                            $invoice->update([
                                'total' => $invoice->items()->sum('amount'),
                            ]);
                        }
                    }
                }
            }
        });

        return back()->with('success', 'Optional fees updated for class.');
    }

    public function saveStudentBilling(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'term' => 'required|in:1,2,3',
            'year' => 'required|integer',
            'voteheads' => 'array',
        ]);

        DB::transaction(function () use ($request) {
            $student = \App\Models\Student::find($request->student_id);
            $structure = \App\Models\FeeStructure::where('classroom_id', $student->classroom_id)
                ->where('year', $request->year)
                ->first();

            foreach ($request->voteheads as $voteheadId => $status) {
                $amount = \App\Models\FeeCharge::where('fee_structure_id', $structure->id)
                    ->where('votehead_id', $voteheadId)
                    ->where('term', $request->term)
                    ->value('amount') ?? 0;

                if ($status === 'billed') {
                    OptionalFee::updateOrCreate([
                        'student_id' => $student->id,
                        'votehead_id' => $voteheadId,
                        'term' => $request->term,
                        'year' => $request->year,
                    ], [
                        'status' => 'billed',
                        'amount' => $amount,
                    ]);

                    $invoice = \App\Models\Invoice::firstOrCreate([
                        'student_id' => $student->id,
                        'term' => $request->term,
                        'year' => $request->year,
                    ], [
                        'invoice_number' => \App\Services\DocumentNumberService::generate('invoice', 'INV'),
                        'total' => 0,
                    ]);

                    \App\Models\InvoiceItem::updateOrCreate(
                        [
                            'invoice_id' => $invoice->id,
                            'votehead_id' => $voteheadId,
                        ],
                        [
                            'amount' => $amount,
                        ]
                    );

                    $invoice->update([
                        'total' => $invoice->items()->sum('amount'),
                    ]);
                } else {
                    // ❌ Delete optional fee record
                    OptionalFee::where([
                        'student_id' => $student->id,
                        'votehead_id' => $voteheadId,
                        'term' => $request->term,
                        'year' => $request->year,
                    ])->delete();

                    $invoice = \App\Models\Invoice::where([
                        'student_id' => $student->id,
                        'term' => $request->term,
                        'year' => $request->year,
                    ])->first();

                    if ($invoice) {
                        $invoice->items()->where('votehead_id', $voteheadId)->delete();

                        if ($invoice->items()->count() === 0) {
                            $invoice->delete();
                        } else {
                            $invoice->update([
                                'total' => $invoice->items()->sum('amount'),
                            ]);
                        }
                    }
                }
            }
        });

        return back()->with('success', 'Student optional fees updated.');
    }

}

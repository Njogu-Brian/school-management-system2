<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
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

    private function loadIndexData(Request $request = null, string $view = 'class')
    {
        $classrooms        = Classroom::orderBy('name')->get();
        
        // Exclude transport and balance brought forward voteheads from optional fees
        $transportVoteheadId = \App\Services\TransportFeeService::transportVotehead()->id;
        $balanceBroughtForwardVotehead = Votehead::where('code', 'BAL_BF')->first();
        $balanceBroughtForwardVoteheadId = $balanceBroughtForwardVotehead ? $balanceBroughtForwardVotehead->id : null;
        
        $optionalVoteheads = Votehead::where('is_mandatory', false)
            ->where('id', '!=', $transportVoteheadId)
            ->when($balanceBroughtForwardVoteheadId, function($q) use ($balanceBroughtForwardVoteheadId) {
                return $q->where('id', '!=', $balanceBroughtForwardVoteheadId);
            })
            ->orderBy('name')
            ->get();
        // used by your student search modal (if it needs a preload list)
        $allStudents       = Student::select('id','first_name','last_name','admission_number')
            ->orderBy('first_name')->get();

        // Get current academic year and term for defaults
        $currentYear = \App\Models\AcademicYear::where('is_active', true)->first();
        $currentTerm = \App\Models\Term::where('is_current', true)->first();
        
        $defaultYear = $currentYear ? (int)$currentYear->year : (int)date('Y');
        $defaultTerm = $currentTerm ? (int)preg_replace('/[^0-9]/', '', $currentTerm->name) : 1;

        $students = collect();   // class view list
        $student  = null;        // selected student (student view)
        $statuses = [];          // map for checked radios
        $term     = $request?->term ?? $defaultTerm;
        $year     = $request?->year ?? $defaultYear;

        // -------- CLASS VIEW --------
        if ($view === 'class' && $request?->filled(['classroom_id','term','year','votehead_id'])) {
            $students   = Student::where('classroom_id', $request->classroom_id)->orderBy('first_name')->get();
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

        // -------- STUDENT VIEW --------
        if ($view === 'student' && $request?->filled(['student_id','term','year'])) {
            $student = Student::find($request->student_id);
            if ($student) {
                // key by votehead_id so the blade can do $statuses[$votehead->id]
                $statuses = OptionalFee::where('student_id', $student->id)
                    ->where('term', $term)
                    ->where('year', $year)
                    ->pluck('status', 'votehead_id')
                    ->toArray();
                
                // Load linked activities for this student, term, and year
                $linkedActivities = \App\Models\StudentExtracurricularActivity::where('student_id', $student->id)
                    ->where('billing_term', $term)
                    ->where('billing_year', $year)
                    ->whereNotNull('votehead_id')
                    ->where('auto_bill', true)
                    ->with('votehead')
                    ->get()
                    ->groupBy('votehead_id');
            } else {
                $linkedActivities = collect();
            }
        } else {
            $linkedActivities = collect();
        }

        return view('finance.optional_fees.index', compact(
            'defaultYear', 'defaultTerm',
            'view', 'classrooms', 'optionalVoteheads', 'allStudents',
            'students', 'student', 'statuses', 'term', 'year', 'linkedActivities'
        ));
    }

    public function saveClassBilling(Request $request)
    {
        $request->validate([
            'votehead_id' => 'required|exists:voteheads,id',
            'term'        => 'required|in:1,2,3',
            'year'        => 'required|integer',
            'students'    => 'array', // students[student_id] => billed|exempt
        ]);

        // If nothing was selected, exit gracefully
        if (!$request->filled('students')) {
            return back()->with('success', 'No changes to apply.');
        }

        DB::transaction(function () use ($request) {
            $voteheadId = (int) $request->votehead_id;
            $term       = (int) $request->term;
            $year       = (int) $request->year;

            $firstStudentId = array_key_first($request->students);
            $firstStudent   = Student::find($firstStudentId);
            $classroomId    = $firstStudent?->classroom_id;
            $categoryId     = $firstStudent?->student_category_id;

            // Try to get amount from fee structure for this class and category
            $amount = 0;
            if ($classroomId && $categoryId) {
                $structure = FeeStructure::where('classroom_id', $classroomId)
                    ->where('student_category_id', $categoryId)
                    ->where(function($q) use ($year) {
                        $q->where('year', $year)
                          ->orWhereHas('academicYear', function($aq) use ($year) {
                              $aq->where('year', $year);
                          });
                    })
                    ->first();

                if ($structure) {
                    $amount = (float) (FeeCharge::where('fee_structure_id', $structure->id)
                        ->where('votehead_id', $voteheadId)
                        ->where('term', $term)
                        ->value('amount') ?? 0);
                }
            }
            
            // If amount is still 0, try to get from any fee structure for this votehead and term
            if ($amount == 0 && $classroomId) {
                $structure = FeeStructure::where('classroom_id', $classroomId)
                    ->where(function($q) use ($year) {
                        $q->where('year', $year)
                          ->orWhereHas('academicYear', function($aq) use ($year) {
                              $aq->where('year', $year);
                          });
                    })
                    ->first();

                if ($structure) {
                    $amount = (float) (FeeCharge::where('fee_structure_id', $structure->id)
                        ->where('votehead_id', $voteheadId)
                        ->where('term', $term)
                        ->value('amount') ?? 0);
                }
            }

            foreach ($request->students as $studentId => $status) {
                $status = $status === 'bill' ? 'billed' : $status; // tolerate "bill"
                if ($status === 'billed') {
                    // Only save the OptionalFee record - DO NOT create invoice items yet
                    // Invoice items will be created when posting is committed via Post Pending Fees
                    OptionalFee::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'votehead_id'=> $voteheadId,
                            'term'       => $term,
                            'year'       => $year,
                        ],
                        [
                            'status' => 'billed',
                            'amount' => $amount,
                        ]
                    );
                    
                    // DO NOT create invoice items here - they will be created during posting commit
                    // This ensures optional fees appear in the preview correctly
                } else {
                    // Exempt: remove optional fee + invoice item (and invoice if empty)
                    // Find and delete the model instance (not bulk delete) so observer fires
                    $optionalFee = OptionalFee::where([
                        'student_id' => $studentId,
                        'votehead_id'=> $voteheadId,
                        'term'       => $term,
                        'year'       => $year,
                    ])->first();

                    if ($optionalFee) {
                        // Delete the model instance so the observer fires and handles wallet reversal
                        $optionalFee->delete();
                    }

                    $invoice = Invoice::where([
                        'student_id' => $studentId,
                        'term'       => $term,
                        'year'       => $year,
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
            'term'       => 'required|in:1,2,3',
            'year'       => 'required|integer',
            // expect: statuses[votehead_id] => bill|billed|exempt
            'statuses'   => 'array',
        ]);

        DB::transaction(function () use ($request) {
            $student   = Student::findOrFail($request->student_id);
            $term      = (int) $request->term;
            $year      = (int) $request->year;
            $statuses  = $request->input('statuses', []);

            // Try to get amount from fee structure for this student's class and category
            $structure = null;
            if ($student->classroom_id && $student->student_category_id) {
                $structure = FeeStructure::where('classroom_id', $student->classroom_id)
                    ->where('student_category_id', $student->student_category_id)
                    ->where(function($q) use ($year) {
                        $q->where('year', $year)
                          ->orWhereHas('academicYear', function($aq) use ($year) {
                              $aq->where('year', $year);
                          });
                    })
                    ->first();
            }
            
            // If not found, try without category
            if (!$structure && $student->classroom_id) {
                $structure = FeeStructure::where('classroom_id', $student->classroom_id)
                    ->where(function($q) use ($year) {
                        $q->where('year', $year)
                          ->orWhereHas('academicYear', function($aq) use ($year) {
                              $aq->where('year', $year);
                          });
                    })
                    ->first();
            }

            foreach ($statuses as $voteheadId => $status) {
                $status = $status === 'bill' ? 'billed' : $status; // tolerate "bill"
                $amount = 0;

                if ($structure) {
                    $amount = (float) (FeeCharge::where('fee_structure_id', $structure->id)
                        ->where('votehead_id', $voteheadId)
                        ->where('term', $term)
                        ->value('amount') ?? 0);
                }

                if ($status === 'billed') {
                    // Only save the OptionalFee record - DO NOT create invoice items yet
                    // Invoice items will be created when posting is committed via Post Pending Fees
                    OptionalFee::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'votehead_id'=> $voteheadId,
                            'term'       => $term,
                            'year'       => $year,
                        ],
                        [
                            'status' => 'billed',
                            'amount' => $amount,
                        ]
                    );
                    
                    // DO NOT create invoice items here - they will be created during posting commit
                    // This ensures optional fees appear in the preview correctly
                } else {
                    // Find and delete the model instance (not bulk delete) so observer fires
                    $optionalFee = OptionalFee::where([
                        'student_id' => $student->id,
                        'votehead_id'=> $voteheadId,
                        'term'       => $term,
                        'year'       => $year,
                    ])->first();

                    if ($optionalFee) {
                        // Delete the model instance so the observer fires and handles wallet reversal
                        $optionalFee->delete();
                    }

                    $invoice = Invoice::where([
                        'student_id' => $student->id,
                        'term'       => $term,
                        'year'       => $year,
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

    /**
     * Try the DocumentNumberService; if it fails (e.g. document_counters table missing),
     * fall back to a simple predictable sequence: INV-YYYY-#####.
     */
    private function generateInvoiceNumber(): string
    {
        // Attempt the service if it exists and is healthy
        try {
            if (class_exists(\App\Services\DocumentNumberService::class)) {
                $maybe = \App\Services\DocumentNumberService::generate('invoice', 'INV');
                if (!empty($maybe)) {
                    return $maybe;
                }
            }
        } catch (\Throwable $e) {
            // swallow and fall through to fallback
        }

        // Fallback: use the highest invoice id to build a sequence
        $next = (int) (Invoice::max('id') ?? 0) + 1;
        return 'INV-' . date('Y') . '-' . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }
}

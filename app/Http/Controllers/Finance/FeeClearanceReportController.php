<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\AcademicYear;
use App\Models\PaymentThreshold;
use App\Models\Student;
use App\Models\StudentTermFeeClearance;
use App\Models\Term;
use App\Services\FeeClearanceStatusService;
use App\Services\AutoPaymentPlanService;
use Illuminate\Http\Request;

class FeeClearanceReportController extends Controller
{
    public function __construct(protected FeeClearanceStatusService $service)
    {
    }

    public function index(Request $request)
    {
        $years = AcademicYear::orderByDesc('year')->get();
        $terms = Term::orderByDesc('id')->with('academicYear')->get();

        $term = $this->resolveTerm($request, $terms);

        $classrooms = Classroom::orderBy('name')->get();

        $filters = [
            'term_id' => $term?->id,
            'classroom_id' => (int) $request->get('classroom_id') ?: null,
            'status' => in_array($request->get('status'), ['cleared', 'pending'], true) ? $request->get('status') : null,
            'search' => trim((string) $request->get('search')),
            'reason_code' => $request->get('reason_code') ?: null,
        ];

        $rows = collect();
        $counts = ['cleared' => 0, 'pending' => 0, 'total' => 0];
        $paymentThresholdsCount = 0;

        if ($term) {
            $paymentThresholdsCount = PaymentThreshold::where('term_id', $term->id)->where('is_active', true)->count();
            $this->ensureSnapshotsForTerm($term, $filters['classroom_id']);

            $query = StudentTermFeeClearance::query()
                ->where('term_id', $term->id)
                ->with(['student.classroom', 'student.stream', 'paymentPlan'])
                ->whereHas('student', function ($q) use ($filters) {
                    $q->where('archive', 0)->where('is_alumni', false);
                    if ($filters['classroom_id']) {
                        $q->where('classroom_id', $filters['classroom_id']);
                    }
                    if ($filters['search'] !== '') {
                        $s = '%'.$filters['search'].'%';
                        $q->where(function ($qq) use ($s) {
                            $qq->where('first_name', 'like', $s)
                                ->orWhere('last_name', 'like', $s)
                                ->orWhere('middle_name', 'like', $s)
                                ->orWhere('admission_number', 'like', $s);
                        });
                    }
                });

            if ($filters['status']) {
                $query->where('status', $filters['status']);
            }
            if ($filters['reason_code']) {
                $query->where('reason_code', $filters['reason_code']);
            }

            $counts['cleared'] = (clone $query)->where('status', 'cleared')->count();
            $counts['pending'] = (clone $query)->where('status', 'pending')->count();
            $counts['total'] = $counts['cleared'] + $counts['pending'];

            $rows = $query
                ->orderBy('status')
                ->orderByDesc('computed_at')
                ->paginate(50)
                ->appends($request->query());
        }

        return view('finance.fee_clearance.index', [
            'term' => $term,
            'terms' => $terms,
            'years' => $years,
            'classrooms' => $classrooms,
            'rows' => $rows,
            'counts' => $counts,
            'filters' => $filters,
            'paymentThresholdsCount' => $paymentThresholdsCount,
        ]);
    }

    public function recompute(Request $request)
    {
        $request->validate([
            'term_id' => 'nullable|exists:terms,id',
            'student_id' => 'nullable|exists:students,id',
        ]);

        $term = $request->term_id
            ? Term::find($request->term_id)
            : Term::where('is_current', true)->orderByDesc('id')->first();

        if (!$term) {
            return back()->with('error', 'No term found. Set a current term or pass term_id.');
        }

        $query = Student::where('archive', 0)->where('is_alumni', false);
        if ($request->student_id) {
            $query->where('id', $request->student_id);
        }

        $count = 0;
        $query->chunkById(250, function ($chunk) use ($term, &$count) {
            foreach ($chunk as $student) {
                $this->service->upsertSnapshot($student, $term);
                // If student meets threshold but still has outstanding balances, auto-create/sync a payment plan.
                // Service is safe to call: it returns null when conditions aren't met.
                try {
                    app(AutoPaymentPlanService::class)->maybeCreateAfterPayment($student, null);
                } catch (\Throwable $e) {
                    \Log::warning('Auto payment plan after fee-clearance recompute failed', [
                        'student_id' => $student->id,
                        'term_id' => $term->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                $count++;
            }
        });

        return back()->with('success', "Recomputed clearance for {$count} student(s) in {$term->name}.");
    }

    protected function resolveTerm(Request $request, $terms): ?Term
    {
        $termId = (int) $request->get('term_id');
        if ($termId) {
            $found = $terms->firstWhere('id', $termId);
            if ($found) {
                return $found;
            }
        }
        return Term::where('is_current', true)->orderByDesc('id')->first();
    }

    protected function ensureSnapshotsForTerm(Term $term, ?int $classroomId = null): void
    {
        $studentQuery = Student::where('archive', 0)->where('is_alumni', false);
        if ($classroomId) {
            $studentQuery->where('classroom_id', $classroomId);
        }

        $studentIds = $studentQuery->pluck('id');
        if ($studentIds->isEmpty()) {
            return;
        }

        $existing = StudentTermFeeClearance::where('term_id', $term->id)
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->all();

        $missing = array_diff($studentIds->all(), $existing);
        if (empty($missing)) {
            return;
        }

        Student::whereIn('id', $missing)->chunkById(200, function ($chunk) use ($term) {
            foreach ($chunk as $s) {
                $this->service->upsertSnapshot($s, $term);
            }
        });
    }
}

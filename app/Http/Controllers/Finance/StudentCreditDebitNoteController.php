<?php

namespace App\Http\Controllers\Finance;

use App\Exports\SchoolCreditDebitNotesExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Invoice;
use App\Models\LegacyStatementTerm;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentCreditDebitNoteController extends Controller
{
    public function index()
    {
        $years = AcademicYear::orderByDesc('year')->pluck('year');
        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        $defaultYear = (int) $years->first();
        $terms = $this->termsForYear(null, $defaultYear);
        $currentTerm = Term::where('is_current', true)->first();
        $defaultTerm = $currentTerm?->id ?? $terms->first()?->id;

        return view('finance.student_credit_debit_notes.index', compact('years', 'terms', 'defaultYear', 'defaultTerm'));
    }

    public function show(Request $request, Student $student)
    {
        $student->load(['classroom', 'stream']);

        $year = (int) ($request->get('year') ?: $this->defaultYear($student));
        $term = $request->get('term');

        $creditNotes = $this->fetchCreditNotes($year, $term, $student);
        $debitNotes = $this->fetchDebitNotes($year, $term, $student);

        $voteheadGroups = $this->groupNotesByVotehead($creditNotes, $debitNotes);

        $totalCredits = (float) $creditNotes->sum('amount');
        $totalDebits = (float) $debitNotes->sum('amount');
        $netAdjustment = round($totalDebits - $totalCredits, 2);

        $years = $this->yearsForStudent($student);
        $terms = $this->termsForYear($student, $year);

        if ($request->ajax() && $request->get('get_terms')) {
            return response()->json([
                'terms' => $terms->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
            ]);
        }

        $termLabel = $this->resolveTermLabel($term, $terms);

        return view('finance.student_credit_debit_notes.show', compact(
            'student',
            'year',
            'term',
            'termLabel',
            'years',
            'terms',
            'voteheadGroups',
            'creditNotes',
            'debitNotes',
            'totalCredits',
            'totalDebits',
            'netAdjustment',
        ));
    }

    public function terms(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        $terms = $this->termsForYear(null, $year);

        return response()->json([
            'terms' => $terms->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
        ]);
    }

    public function exportSchool(Request $request): BinaryFileResponse|RedirectResponse
    {
        $request->validate([
            'year' => 'required|integer',
            'term' => 'required',
        ]);

        $year = (int) $request->year;
        $term = $request->term;

        $creditNotes = $this->fetchCreditNotes($year, $term);
        $debitNotes = $this->fetchDebitNotes($year, $term);

        if ($creditNotes->isEmpty() && $debitNotes->isEmpty()) {
            return redirect()
                ->route('finance.student-credit-debit-notes.index')
                ->withInput()
                ->with('error', 'No credit or debit notes found for the selected year and term.');
        }

        [$detailRows, $summaryRows] = $this->buildExportRows($creditNotes, $debitNotes);

        $termSlug = preg_replace('/[^a-z0-9]+/i', '-', $this->resolveTermLabel($term, $this->termsForYear(null, $year)));
        $filename = "credit-debit-notes-{$year}-{$termSlug}-school.xlsx";

        return Excel::download(
            new SchoolCreditDebitNotesExport($detailRows, $summaryRows),
            $filename
        );
    }

    private function fetchCreditNotes(int $year, $term, ?Student $student = null)
    {
        return CreditNote::query()
            ->whereHas('invoiceItem', function (Builder $q) use ($student, $year, $term) {
                $this->excludeSwimmingAttendance($q);
                if ($student) {
                    $this->applyInvoiceScope($q, $student);
                }
                $q->whereHas('invoice', fn (Builder $q2) => $this->applyYearTermScope($q2, $year, $term));
            })
            ->with([
                'invoiceItem.votehead',
                'invoice.student.classroom',
                'invoice.student.stream',
                'invoice.term',
                'invoice.academicYear',
                'issuedBy',
            ])
            ->orderBy('issued_at')
            ->orderBy('id')
            ->get();
    }

    private function fetchDebitNotes(int $year, $term, ?Student $student = null)
    {
        return DebitNote::query()
            ->whereHas('invoiceItem', function (Builder $q) use ($student, $year, $term) {
                $this->excludeSwimmingAttendance($q);
                if ($student) {
                    $this->applyInvoiceScope($q, $student);
                }
                $q->whereHas('invoice', fn (Builder $q2) => $this->applyYearTermScope($q2, $year, $term));
            })
            ->with([
                'invoiceItem.votehead',
                'invoice.student.classroom',
                'invoice.student.stream',
                'invoice.term',
                'invoice.academicYear',
                'issuedBy',
            ])
            ->orderBy('issued_at')
            ->orderBy('id')
            ->get();
    }

    private function buildExportRows($creditNotes, $debitNotes): array
    {
        $detailRows = [];
        $summaryMap = [];

        foreach ($creditNotes as $note) {
            $detailRows[] = $this->detailRow('Credit', $note->credit_note_number, $note);
            $this->accumulateSummary($summaryMap, $note, 'credit', (float) $note->amount);
        }

        foreach ($debitNotes as $note) {
            $detailRows[] = $this->detailRow('Debit', $note->debit_note_number, $note);
            $this->accumulateSummary($summaryMap, $note, 'debit', (float) $note->amount);
        }

        usort($detailRows, function ($a, $b) {
            return [$a[2], $a[6], $a[1]] <=> [$b[2], $b[6], $b[1]];
        });

        $summaryRows = collect($summaryMap)
            ->sortBy(fn ($row) => $row['student'] . '|' . $row['votehead'])
            ->map(fn ($row) => [
                $row['student'],
                $row['admission'],
                $row['class'],
                $row['stream'],
                $row['votehead'],
                number_format($row['credit_total'], 2, '.', ''),
                number_format($row['debit_total'], 2, '.', ''),
                number_format($row['debit_total'] - $row['credit_total'], 2, '.', ''),
            ])
            ->values()
            ->all();

        return [$detailRows, $summaryRows];
    }

    private function detailRow(string $type, ?string $number, $note): array
    {
        $student = $note->invoice?->student;

        return [
            $type,
            $number ?? '',
            $student?->full_name ?? '',
            $student?->admission_number ?? '',
            optional($student?->classroom)->name ?? '',
            optional($student?->stream)->name ?? '',
            $note->invoiceItem?->votehead?->name ?? 'Unknown votehead',
            $note->invoice?->invoice_number ?? '',
            number_format((float) $note->amount, 2, '.', ''),
            $note->reason ?? '',
            $note->issued_at ? $note->issued_at->format('Y-m-d') : '',
            $note->issuedBy?->name ?? '',
        ];
    }

    private function accumulateSummary(array &$summaryMap, $note, string $type, float $amount): void
    {
        $student = $note->invoice?->student;
        $voteheadId = $note->invoiceItem?->votehead_id ?? 0;
        $key = ($student?->id ?? 0) . '|' . $voteheadId;

        if (! isset($summaryMap[$key])) {
            $summaryMap[$key] = [
                'student' => $student?->full_name ?? 'Unknown',
                'admission' => $student?->admission_number ?? '',
                'class' => optional($student?->classroom)->name ?? '',
                'stream' => optional($student?->stream)->name ?? '',
                'votehead' => $note->invoiceItem?->votehead?->name ?? 'Unknown votehead',
                'credit_total' => 0.0,
                'debit_total' => 0.0,
            ];
        }

        if ($type === 'credit') {
            $summaryMap[$key]['credit_total'] += $amount;
        } else {
            $summaryMap[$key]['debit_total'] += $amount;
        }
    }

    private function applyInvoiceScope(Builder $q, Student $student): void
    {
        $q->whereHas('invoice', fn (Builder $q2) => $q2->where('student_id', $student->id));
    }

    private function excludeSwimmingAttendance(Builder $q): void
    {
        $q->where(function (Builder $q2) {
            $q2->whereNull('source')->orWhere('source', '!=', 'swimming_attendance');
        });
    }

    private function applyYearTermScope(Builder $q, int $year, $term): void
    {
        $q->whereNull('reversed_at')
            ->where(function (Builder $q2) {
                $q2->whereNull('status')->orWhere('status', '!=', 'reversed');
            })
            ->where(function (Builder $q2) use ($year) {
                $q2->where('year', $year)
                    ->orWhereHas('academicYear', fn (Builder $q3) => $q3->where('year', $year));
            });

        if ($term === null || $term === '') {
            return;
        }

        $termNumber = $this->resolveTermNumber($term);

        $q->where(function (Builder $q2) use ($term, $termNumber) {
            $q2->whereHas('term', function (Builder $q3) use ($term) {
                $q3->where('id', $term);
                if (is_numeric($term)) {
                    $q3->orWhere('name', 'like', '%Term ' . (int) $term . '%');
                }
            });

            if ($termNumber !== null) {
                $q2->orWhere('term', $termNumber);
            }
        });
    }

    private function groupNotesByVotehead($creditNotes, $debitNotes)
    {
        $rows = collect();

        foreach ($creditNotes as $note) {
            $rows->push([
                'type' => 'credit',
                'note' => $note,
                'votehead_id' => $note->invoiceItem?->votehead_id ?? 0,
            ]);
        }

        foreach ($debitNotes as $note) {
            $rows->push([
                'type' => 'debit',
                'note' => $note,
                'votehead_id' => $note->invoiceItem?->votehead_id ?? 0,
            ]);
        }

        return $rows
            ->groupBy('votehead_id')
            ->map(function ($group, $voteheadId) {
                $firstNote = $group->first()['note'];
                $votehead = $firstNote->invoiceItem?->votehead;
                $creditTotal = (float) $group->where('type', 'credit')->sum(fn ($row) => (float) $row['note']->amount);
                $debitTotal = (float) $group->where('type', 'debit')->sum(fn ($row) => (float) $row['note']->amount);

                return [
                    'votehead_id' => (int) $voteheadId,
                    'votehead_name' => $votehead?->name ?? 'Unknown votehead',
                    'credit_total' => $creditTotal,
                    'debit_total' => $debitTotal,
                    'net_adjustment' => round($debitTotal - $creditTotal, 2),
                    'notes' => $group->sortBy(fn ($row) => $row['note']->issued_at?->timestamp ?? 0)->values(),
                ];
            })
            ->sortBy('votehead_name')
            ->values();
    }

    private function defaultYear(Student $student): int
    {
        $invoiceYears = Invoice::where('student_id', $student->id)
            ->with('academicYear:id,year')
            ->get(['id', 'year', 'academic_year_id'])
            ->map(fn ($invoice) => $invoice->year ?: optional($invoice->academicYear)->year);

        $legacyYears = LegacyStatementTerm::where('student_id', $student->id)->pluck('academic_year');

        return (int) ($invoiceYears->merge($legacyYears)->filter()->max() ?: now()->year);
    }

    private function yearsForStudent(Student $student)
    {
        $invoiceYears = Invoice::where('student_id', $student->id)
            ->whereNotNull('year')
            ->distinct()
            ->pluck('year');

        $legacyYears = LegacyStatementTerm::where('student_id', $student->id)
            ->distinct()
            ->pluck('academic_year');

        $academicYears = AcademicYear::distinct()->pluck('year');

        return $invoiceYears->merge($legacyYears)->merge($academicYears)->unique()->sort()->reverse()->values();
    }

    private function termsForYear(?Student $student, int $year)
    {
        if ($year < 2026) {
            $query = LegacyStatementTerm::query()->where('academic_year', $year);
            if ($student) {
                $query->where('student_id', $student->id);
            }

            return $query->orderBy('term_number')
                ->get()
                ->unique('term_number')
                ->map(fn ($t) => (object) [
                    'id' => 'legacy-' . $t->term_number,
                    'name' => $t->term_name ?: ('Term ' . $t->term_number),
                ])
                ->values();
        }

        $academicYear = AcademicYear::where('year', $year)->first();
        $query = Term::query();

        if ($academicYear) {
            $query->where('academic_year_id', $academicYear->id);
        }

        return $query->orderBy('name')->get();
    }

    private function resolveTermNumber($term): ?int
    {
        if ($term === null || $term === '') {
            return null;
        }

        if (is_string($term) && str_starts_with($term, 'legacy-')) {
            $number = (int) str_replace('legacy-', '', $term);

            return $number > 0 ? $number : null;
        }

        if (is_numeric($term)) {
            $termModel = Term::find($term);
            if ($termModel && preg_match('/Term\s*(\d+)/i', $termModel->name ?? '', $matches)) {
                return (int) $matches[1];
            }

            $asInt = (int) $term;
            if ($asInt >= 1 && $asInt <= 3) {
                return $asInt;
            }
        }

        return null;
    }

    private function resolveTermLabel($term, $terms): string
    {
        if ($term === null || $term === '') {
            return 'All terms';
        }

        $match = $terms->first(fn ($t) => (string) $t->id === (string) $term);

        return $match->name ?? ('Term ' . $term);
    }
}

<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Invoice;
use App\Models\LegacyStatementTerm;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StudentCreditDebitNoteController extends Controller
{
    public function index()
    {
        return view('finance.student_credit_debit_notes.index');
    }

    public function show(Request $request, Student $student)
    {
        $student->load(['classroom', 'stream']);

        $year = (int) ($request->get('year') ?: $this->defaultYear($student));
        $term = $request->get('term');

        $creditNotes = $this->creditNotesForStudent($student, $year, $term);
        $debitNotes = $this->debitNotesForStudent($student, $year, $term);

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

    private function creditNotesForStudent(Student $student, int $year, $term)
    {
        return CreditNote::query()
            ->whereHas('invoiceItem', function (Builder $q) use ($student) {
                $this->applyInvoiceScope($q, $student);
                $this->excludeSwimmingAttendance($q);
            })
            ->whereHas('invoiceItem.invoice', fn (Builder $q) => $this->applyYearTermScope($q, $year, $term))
            ->with(['invoiceItem.votehead', 'invoice.term', 'invoice.academicYear', 'issuedBy'])
            ->orderBy('issued_at')
            ->orderBy('id')
            ->get();
    }

    private function debitNotesForStudent(Student $student, int $year, $term)
    {
        return DebitNote::query()
            ->whereHas('invoiceItem', function (Builder $q) use ($student) {
                $this->applyInvoiceScope($q, $student);
                $this->excludeSwimmingAttendance($q);
            })
            ->whereHas('invoiceItem.invoice', fn (Builder $q) => $this->applyYearTermScope($q, $year, $term))
            ->with(['invoiceItem.votehead', 'invoice.term', 'invoice.academicYear', 'issuedBy'])
            ->orderBy('issued_at')
            ->orderBy('id')
            ->get();
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

    private function termsForYear(Student $student, int $year)
    {
        if ($year < 2026) {
            return LegacyStatementTerm::where('student_id', $student->id)
                ->where('academic_year', $year)
                ->orderBy('term_number')
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

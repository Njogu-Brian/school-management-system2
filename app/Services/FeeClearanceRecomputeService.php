<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Student;
use App\Models\Term;

/**
 * Recomputes StudentTermFeeClearance when finance data changes (payments, invoices, plans).
 */
class FeeClearanceRecomputeService
{
    public function __construct(protected FeeClearanceStatusService $feeClearanceStatusService)
    {
    }

    public function recomputeForInvoice(?Invoice $invoice): void
    {
        if (!$invoice || !$invoice->student_id || !$invoice->term_id) {
            return;
        }
        if (($invoice->status ?? '') === 'reversed') {
            return;
        }
        $student = Student::find($invoice->student_id);
        $term = Term::find($invoice->term_id);
        if ($student && $term) {
            $this->feeClearanceStatusService->upsertSnapshot($student, $term);
        }
    }

    public function recomputeForStudentTerm(?Student $student, ?Term $term): void
    {
        if ($student && $term) {
            $this->feeClearanceStatusService->upsertSnapshot($student, $term);
        }
    }

    /**
     * All distinct terms this student has non-reversed invoices for.
     *
     * @return list<int>
     */
    public function termIdsForStudent(int $studentId): array
    {
        return Invoice::query()
            ->where('student_id', $studentId)
            ->where('status', '!=', 'reversed')
            ->whereNotNull('term_id')
            ->distinct()
            ->pluck('term_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function recomputeAllTermsForStudent(int $studentId): void
    {
        $student = Student::find($studentId);
        if (!$student) {
            return;
        }
        foreach ($this->termIdsForStudent($studentId) as $termId) {
            $term = Term::find($termId);
            if ($term) {
                $this->feeClearanceStatusService->upsertSnapshot($student, $term);
            }
        }
    }
}

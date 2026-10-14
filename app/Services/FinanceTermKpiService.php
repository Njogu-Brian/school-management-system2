<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Term;
use App\Models\Votehead;

/**
 * Term-scoped finance KPIs aligned with web finance dashboard (DashboardController).
 */
class FinanceTermKpiService
{
    public function forCurrentTerm(): array
    {
        $term = Term::query()->where('is_current', true)->first();
        if (! $term) {
            return $this->emptyResult(null);
        }

        return $this->forTerm($term);
    }

    public function forTerm(Term $term): array
    {
        $swimmingVoteheadIds = Votehead::where(function ($q) {
            $q->where('name', 'like', '%swim%')->orWhere('code', 'like', '%SWIM%');
        })->pluck('id')->toArray();

        // Archived and alumni students stay out of every total, even when an
        // old invoice is still unpaid. Student global scope enforces that.
        $termInvoiceIds = Invoice::where('term_id', $term->id)
            ->whereNull('reversed_at')
            ->whereNull('deleted_at')
            ->whereHas('student')
            ->pluck('id');

        $term->loadMissing('academicYear');
        $year = (int) ($term->academicYear->year ?? now()->year);
        $schoolOutstanding = app(StudentFeeStatementService::class)->schoolOutstandingSummary($year);

        if ($termInvoiceIds->isEmpty()) {
            return $this->emptyResult($term, $schoolOutstanding);
        }

        $totalInvoiced = (float) InvoiceItem::whereIn('invoice_id', $termInvoiceIds)
            ->where('status', 'active')
            ->when(! empty($swimmingVoteheadIds), fn ($q) => $q->whereNotIn('votehead_id', $swimmingVoteheadIds))
            ->selectRaw('COALESCE(SUM(amount - COALESCE(discount_amount, 0)), 0) as total')
            ->value('total');

        $feesCollected = (float) PaymentAllocation::whereHas('invoiceItem', function ($q) use ($termInvoiceIds, $swimmingVoteheadIds) {
            $q->whereIn('invoice_id', $termInvoiceIds)->where('status', 'active');
            if (! empty($swimmingVoteheadIds)) {
                $q->whereNotIn('votehead_id', $swimmingVoteheadIds);
            }
        })
            ->whereHas('payment', function ($q) {
                $q->where('reversed', false)
                    ->whereRaw("COALESCE(receipt_number, '') NOT LIKE 'SWIM-%'")
                    ->whereRaw("(COALESCE(narration, '') NOT LIKE '%Swimming%' AND COALESCE(narration, '') NOT LIKE '%(Swimming)%')");
            })
            ->sum('amount');

        $openInvoices = Invoice::whereIn('id', $termInvoiceIds)
            ->whereIn('status', ['unpaid', 'partial']);
        $feesOverdue = (float) (clone $openInvoices)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->sum('balance');

        return [
            'term_id' => $term->id,
            'term_name' => $term->name,
            'academic_year_id' => $term->academic_year_id,
            'finance_scope' => 'year_statement',
            'total_invoiced' => round($totalInvoiced, 2),
            'fees_collected' => round($feesCollected, 2),
            'fees_outstanding' => round((float) $schoolOutstanding['total_outstanding'], 2),
            'students_owing' => (int) $schoolOutstanding['students_owing'],
            'fees_overdue' => round($feesOverdue, 2),
        ];
    }

    protected function emptyResult(?Term $term, ?array $schoolOutstanding = null): array
    {
        return [
            'term_id' => $term?->id,
            'term_name' => $term?->name,
            'academic_year_id' => $term?->academic_year_id,
            'finance_scope' => 'year_statement',
            'total_invoiced' => 0.0,
            'fees_collected' => 0.0,
            'fees_outstanding' => round((float) ($schoolOutstanding['total_outstanding'] ?? 0), 2),
            'students_owing' => (int) ($schoolOutstanding['students_owing'] ?? 0),
            'fees_overdue' => 0.0,
        ];
    }
}

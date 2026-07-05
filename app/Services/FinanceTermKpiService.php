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

        $termInvoiceIds = Invoice::where('term_id', $term->id)
            ->whereNull('reversed_at')
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($termInvoiceIds->isEmpty()) {
            return $this->emptyResult($term);
        }

        $totalInvoiced = (float) InvoiceItem::whereIn('invoice_id', $termInvoiceIds)
            ->where('status', 'active')
            ->when(! empty($swimmingVoteheadIds), fn ($q) => $q->whereNotIn('votehead_id', $swimmingVoteheadIds))
            ->get()
            ->sum(fn ($item) => (float) $item->amount - (float) ($item->discount_amount ?? 0));

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

        $feesOutstanding = (float) Invoice::whereIn('id', $termInvoiceIds)
            ->whereIn('status', ['unpaid', 'partial'])
            ->where(function ($q) {
                $q->whereNull('due_date')
                    ->orWhereDate('due_date', '<=', now()->toDateString());
            })
            ->sum('balance');

        return [
            'term_id' => $term->id,
            'term_name' => $term->name,
            'academic_year_id' => $term->academic_year_id,
            'finance_scope' => 'term',
            'total_invoiced' => round($totalInvoiced, 2),
            'fees_collected' => round($feesCollected, 2),
            'fees_outstanding' => round($feesOutstanding, 2),
        ];
    }

    protected function emptyResult(?Term $term): array
    {
        return [
            'term_id' => $term?->id,
            'term_name' => $term?->name,
            'academic_year_id' => $term?->academic_year_id,
            'finance_scope' => 'term',
            'total_invoiced' => 0.0,
            'fees_collected' => 0.0,
            'fees_outstanding' => 0.0,
        ];
    }
}

<?php

namespace App\Services;

use App\Models\LegacyLedgerPosting;
use Illuminate\Support\Collection;

class LegacyPostingReportService
{
    /**
     * Summary counts and amount totals by target_type for a batch (optionally by class).
     */
    public function summary(int $batchId, ?string $classLabel = null): Collection
    {
        $query = LegacyLedgerPosting::where('batch_id', $batchId)
            ->with(['line.term']);

        if ($classLabel) {
            $query->whereHas('term', fn ($q) => $q->where('class_label', $classLabel));
        }

        return $query->get()
            ->groupBy('target_type')
            ->map(function ($group) {
                $ids = $group->pluck('target_id')->filter();
                $total = 0;
                switch ($group->first()->target_type ?? '') {
                    case 'payment':
                        $total = \App\Models\Payment::whereIn('id', $ids)->sum('amount');
                        break;
                    case 'credit':
                        $total = \App\Models\CreditNote::whereIn('id', $ids)->sum('amount');
                        break;
                    case 'debit':
                        $total = \App\Models\DebitNote::whereIn('id', $ids)->sum('amount');
                        break;
                    case 'invoice':
                        $total = \App\Models\Invoice::whereIn('id', $ids)->sum('total');
                        break;
                    default:
                        $total = 0;
                }

                return [
                    'count' => $group->count(),
                    'amount' => $total,
                ];
            });
    }

    public function payments(int $batchId, ?string $classLabel = null)
    {
        $postings = $this->baseQuery($batchId, 'payment', $classLabel)->pluck('target_id')->filter();
        return \App\Models\Payment::with(['student', 'invoice'])
            ->whereIn('id', $postings)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function credits(int $batchId, ?string $classLabel = null)
    {
        $postings = $this->baseQuery($batchId, 'credit', $classLabel)->pluck('target_id')->filter();
        return \App\Models\CreditNote::with(['invoice.student'])
            ->whereIn('id', $postings)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function debits(int $batchId, ?string $classLabel = null)
    {
        $postings = $this->baseQuery($batchId, 'debit', $classLabel)->pluck('target_id')->filter();
        return \App\Models\DebitNote::with(['invoice.student'])
            ->whereIn('id', $postings)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function discounts(int $batchId, ?string $classLabel = null)
    {
        $postings = $this->baseQuery($batchId, 'credit', $classLabel)
            ->whereHas('line', fn ($q) => $q->where('txn_type', 'discount')
                ->orWhere('narration_raw', 'like', '%discount%'))
            ->pluck('target_id')->filter();

        return \App\Models\CreditNote::with(['invoice.student'])
            ->whereIn('id', $postings)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function invoices(int $batchId, ?string $classLabel = null)
    {
        $postings = $this->baseQuery($batchId, 'invoice', $classLabel)->pluck('target_id')->filter();
        return \App\Models\Invoice::with(['student', 'items'])
            ->whereIn('id', $postings)
            ->orderBy('id', 'desc')
            ->get();
    }

    protected function baseQuery(int $batchId, string $type, ?string $classLabel = null)
    {
        $query = LegacyLedgerPosting::where('batch_id', $batchId)
            ->where('target_type', $type)
            ->with(['line.term']);

        if ($classLabel) {
            $query->whereHas('term', fn ($q) => $q->where('class_label', $classLabel));
        }

        return $query;
    }
}


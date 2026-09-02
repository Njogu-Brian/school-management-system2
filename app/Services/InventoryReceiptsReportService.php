<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Term;
use Carbon\Carbon;

class InventoryReceiptsReportService
{
    /**
     * Totals received in a date range, e.g. "1,000 tissues this term".
     *
     * @return array{
     *     from:Carbon,
     *     to:Carbon,
     *     rows:list<array<string,mixed>>,
     *     grand_total:float
     * }
     */
    public function build(?string $from, ?string $to, ?string $category = null): array
    {
        $range = $this->resolveRange($from, $to);

        $query = InventoryTransaction::query()
            ->with('inventoryItem')
            ->where('type', 'in')
            ->whereBetween('created_at', [$range['from']->copy()->startOfDay(), $range['to']->copy()->endOfDay()]);

        if ($category) {
            $query->whereHas('inventoryItem', fn ($q) => $q->where('category', $category));
        }

        $transactions = $query->get();
        $reversedIds = $this->reversedInboundIds($transactions->pluck('id')->all());
        $transactions = $transactions->reject(fn ($tx) => in_array((int) $tx->id, $reversedIds, true));

        $grouped = $transactions->groupBy('inventory_item_id');
        $rows = [];
        $grand = 0.0;

        foreach ($grouped as $itemId => $txs) {
            /** @var InventoryItem|null $item */
            $item = $txs->first()?->inventoryItem;
            if (! $item) {
                continue;
            }
            $fromLearners = (float) $txs->whereNotNull('student_requirement_id')->sum('quantity');
            $other = (float) $txs->whereNull('student_requirement_id')->sum('quantity');
            $total = $fromLearners + $other;
            $grand += $total;

            $rows[] = [
                'item_id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'unit' => $item->unit ?? 'pcs',
                'from_learners' => $fromLearners,
                'other_receipts' => $other,
                'total_received' => $total,
                'current_stock' => (float) $item->quantity,
            ];
        }

        usort($rows, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return [
            'from' => $range['from'],
            'to' => $range['to'],
            'rows' => $rows,
            'grand_total' => $grand,
            'categories' => InventoryItem::query()->distinct()->orderBy('category')->pluck('category')->filter()->values(),
        ];
    }

    /**
     * @return array{from:Carbon,to:Carbon}
     */
    private function resolveRange(?string $from, ?string $to): array
    {
        $end = $to ? Carbon::parse($to) : now();
        $start = $from ? Carbon::parse($from) : $this->defaultStart($end);

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return ['from' => $start->startOfDay(), 'to' => $end->endOfDay()];
    }

    private function defaultStart(Carbon $end): Carbon
    {
        $term = Term::where('is_current', true)->first();
        if ($term && $term->opening_date) {
            return Carbon::parse($term->opening_date);
        }

        return $end->copy()->startOfMonth();
    }

    /**
     * Inbound learner receipts later marked verify are no longer school stock.
     *
     * @param  list<int|string>  $inboundIds
     * @return list<int>
     */
    private function reversedInboundIds(array $inboundIds): array
    {
        if ($inboundIds === []) {
            return [];
        }

        $refs = array_map(fn ($id) => 'VERIFY-REVERSE-'.$id, $inboundIds);

        return InventoryTransaction::query()
            ->where('type', 'out')
            ->whereIn('reference_number', $refs)
            ->pluck('reference_number')
            ->map(fn ($ref) => (int) str_replace('VERIFY-REVERSE-', '', (string) $ref))
            ->all();
    }
}

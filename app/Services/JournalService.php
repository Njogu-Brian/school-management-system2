<?php

namespace App\Services;

use App\Models\{Journal, InvoiceItem, Invoice};

class JournalService
{
    public static function createAndApply(array $data): Journal
    {
        // expected keys: student_id, votehead_id, year, term, type (credit|debit), amount, reason, effective_date?
        $data['journal_number'] = NumberSeries::journal();

        $j = Journal::create($data);

        $invoice = InvoiceService::ensure($data['student_id'], $data['year'], $data['term']);

        $item = InvoiceItem::firstOrNew([
            'invoice_id'  => $invoice->id,
            'votehead_id' => $data['votehead_id'],
        ]);

        $old = (float)($item->exists ? $item->amount : 0);
        $delta = ($data['type'] === 'debit') ? +$data['amount'] : -$data['amount'];
        $item->amount = $old + $delta;
        $item->status = 'active';
        $item->effective_date = $data['effective_date'] ?? null;
        $item->source = 'journal';
        $item->save();

        $j->update(['invoice_id'=>$invoice->id, 'invoice_item_id'=>$item->id]);

        InvoiceService::recalc($invoice);

        return $j;
    }
}

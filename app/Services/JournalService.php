<?php

namespace App\Services;

use App\Models\{Journal, InvoiceItem, Invoice, CreditNote, DebitNote};
use Illuminate\Support\Facades\DB;

class JournalService
{
    public static function createAndApply(array $data): Journal
    {
        // expected keys: student_id, votehead_id, year, term, type (credit|debit), amount, reason, effective_date?
        return DB::transaction(function () use ($data) {
            // Check if invoice exists first - don't create new invoices for adjustments
            $invoice = Invoice::where('student_id', $data['student_id'])
                ->where('year', $data['year'])
                ->where('term', $data['term'])
                ->first();
            
            if (!$invoice) {
                $student = \App\Models\Student::find($data['student_id']);
                $studentName = $student ? $student->full_name : 'Student';
                throw new \Exception("No existing invoice found for {$studentName} for Year {$data['year']}, Term {$data['term']}. Please create an invoice first before applying adjustments.");
            }
            
            $data['journal_number'] = NumberSeries::journal();

            $j = Journal::create($data);

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

            // Create CreditNote or DebitNote record for display in invoice
            if ($data['type'] === 'credit') {
                CreditNote::create([
                    'invoice_id' => $invoice->id,
                    'invoice_item_id' => $item->id,
                    'amount' => $data['amount'],
                    'reason' => $data['reason'],
                    'notes' => 'Created via Journal: ' . $j->journal_number,
                    'issued_at' => $data['effective_date'] ?? now(),
                    'issued_by' => auth()->id(),
                ]);
            } elseif ($data['type'] === 'debit') {
                DebitNote::create([
                    'invoice_id' => $invoice->id,
                    'invoice_item_id' => $item->id,
                    'amount' => $data['amount'],
                    'reason' => $data['reason'],
                    'notes' => 'Created via Journal: ' . $j->journal_number,
                    'issued_at' => $data['effective_date'] ?? now(),
                    'issued_by' => auth()->id(),
                ]);
            }

            // Recalculate invoice to ensure totals are correct
            InvoiceService::recalc($invoice);
            
            // Refresh the invoice to get updated totals
            $invoice->refresh();

            return $j;
        });
    }
}

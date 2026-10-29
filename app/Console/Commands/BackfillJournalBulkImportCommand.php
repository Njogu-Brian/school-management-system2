<?php

namespace App\Console\Commands;

use App\Models\InvoiceItem;
use App\Models\Journal;
use App\Models\JournalBulkImport;
use App\Models\JournalBulkImportRow;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillJournalBulkImportCommand extends Command
{
    protected $signature = 'journals:backfill-bulk-import
        {--date=2026-10-29 : Created-at date of the applied journals}
        {--force : Recreate even if a backfilled batch already exists}';

    protected $description = 'Create a journal bulk import history batch from already-applied journals';

    public function handle(): int
    {
        $date = (string) $this->option('date');
        $notesMarker = "Backfilled from applied journals on {$date}";

        $existing = JournalBulkImport::where('notes', $notesMarker)->first();
        if ($existing && !$this->option('force')) {
            $this->info("Already backfilled as {$existing->batch_code} (id {$existing->id}). Use --force to recreate.");
            return self::SUCCESS;
        }

        if ($existing && $this->option('force')) {
            $existing->delete();
        }

        $journals = Journal::with(['student', 'votehead'])
            ->whereDate('created_at', $date)
            ->whereIn('reason', [
                '2027 Term 1 tuition credit adjustment',
                '2027 Term 1 drinking water credit',
            ])
            ->orderBy('id')
            ->get();

        if ($journals->isEmpty()) {
            $this->warn("No matching journals found for {$date}.");
            return self::FAILURE;
        }

        $uploaderId = $journals->first()->meta['uploaded_by']
            ?? \App\Models\User::query()->orderBy('id')->value('id');

        $import = JournalBulkImport::create([
            'batch_code' => JournalBulkImport::nextBatchCode(),
            'original_filename' => 'journal_bulk_2027_term1_tuition_drinking_water.csv',
            'uploaded_by' => $uploaderId,
            'status' => 'completed',
            'notes' => $notesMarker,
            'approved_at' => $journals->max('created_at'),
            'created_at' => $journals->min('created_at'),
            'updated_at' => $journals->max('created_at'),
        ]);

        $line = 2;
        foreach ($journals as $journal) {
            $itemAmount = null;
            if ($journal->invoice_item_id) {
                $itemAmount = (float) (InvoiceItem::find($journal->invoice_item_id)?->amount ?? 0);
            } elseif ($journal->invoice_id) {
                $itemAmount = (float) (InvoiceItem::where('invoice_id', $journal->invoice_id)
                    ->where('votehead_id', $journal->votehead_id)
                    ->value('amount') ?? 0);
            }

            $amount = (float) $journal->amount;
            $delta = $journal->type === 'debit' ? $amount : -$amount;
            $newAmount = $itemAmount;
            $beforeAmount = $itemAmount === null ? null : round($itemAmount - $delta, 2);

            JournalBulkImportRow::create([
                'journal_bulk_import_id' => $import->id,
                'uid' => (string) Str::uuid(),
                'line' => $line++,
                'admission_number' => $journal->student?->admission_number,
                'student_id' => $journal->student_id,
                'student_name' => $journal->student?->full_name,
                'votehead_id' => $journal->votehead_id,
                'votehead_name' => $journal->votehead?->name,
                'effective_date' => $journal->effective_date,
                'type' => $journal->type,
                'type_label' => $journal->type === 'credit' ? 'Cr' : 'Dr',
                'year' => $journal->year,
                'term' => $journal->term,
                'reason' => $journal->reason,
                'amount' => $amount,
                'before_amount' => $beforeAmount,
                'new_amount' => $newAmount === null ? null : round($newAmount, 2),
                'invoice_id' => $journal->invoice_id,
                'journal_id' => $journal->id,
                'status' => 'approved',
                'message' => 'Applied as ' . $journal->journal_number,
                'created_at' => $journal->created_at,
                'updated_at' => $journal->updated_at,
            ]);

            $meta = $journal->meta ?? [];
            $meta['bulk_import_id'] = $import->id;
            $meta['bulk_batch_code'] = $import->batch_code;
            $journal->update(['meta' => $meta]);
        }

        $import->refreshCounts();

        $this->info("Backfilled {$journals->count()} journals into {$import->batch_code}.");
        $this->line("Credits: {$import->credit_total} | Debits: {$import->debit_total}");

        return self::SUCCESS;
    }
}

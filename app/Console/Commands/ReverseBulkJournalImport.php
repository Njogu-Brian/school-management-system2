<?php

namespace App\Console\Commands;

use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Journal;
use App\Services\InvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReverseBulkJournalImport extends Command
{
    protected $signature = 'journals:reverse-bulk-import
        {--year=2026 : Invoice year to reverse}
        {--term=2 : Invoice term to reverse}
        {--since= : Only journals created at/after this timestamp (Y-m-d H:i:s)}
        {--until= : Only journals created at/before this timestamp (optional)}
        {--dry-run : Preview without making changes}
        {--force : Skip confirmation prompt}';

    protected $description = 'Reverse a bulk journal credit/debit import for a year/term (restores invoice items and deletes notes).';

    public function handle(): int
    {
        $year = (int) $this->option('year');
        $term = (int) $this->option('term');
        $since = $this->option('since');
        $until = $this->option('until');
        $dryRun = (bool) $this->option('dry-run');

        if (! $since) {
            $this->error('Provide --since with the import start time, e.g. --since="2026-07-28 16:00:00"');

            return self::FAILURE;
        }

        $query = Journal::query()
            ->where('year', $year)
            ->where('term', $term)
            ->where('created_at', '>=', $since)
            ->when($until, fn ($q) => $q->where('created_at', '<=', $until))
            ->with(['invoiceItem', 'invoice', 'student'])
            ->orderBy('id');

        $journals = $query->get();

        if ($journals->isEmpty()) {
            $this->warn("No journals found for {$year} Term {$term} since {$since}.");

            return self::SUCCESS;
        }

        $this->line("Found {$journals->count()} journal(s) to reverse.");
        $this->line('Dry run: '.($dryRun ? 'yes' : 'no'));

        $creditTotal = 0.0;
        $debitTotal = 0.0;
        $invoiceIds = [];

        foreach ($journals as $journal) {
            $student = $journal->student?->full_name ?? ('student #'.$journal->student_id);
            $this->line("  {$journal->journal_number} | {$journal->type} | {$student} | KES ".number_format((float) $journal->amount, 2));

            if ($journal->type === 'credit') {
                $creditTotal += (float) $journal->amount;
            } else {
                $debitTotal += (float) $journal->amount;
            }

            if ($journal->invoice_id) {
                $invoiceIds[$journal->invoice_id] = true;
            }
        }

        $this->newLine();
        $this->info('Totals: credits KES '.number_format($creditTotal, 2).', debits KES '.number_format($debitTotal, 2));
        $this->info('Invoices affected: '.count($invoiceIds));

        if ($dryRun) {
            $this->comment('Dry run only — re-run without --dry-run to reverse.');

            return self::SUCCESS;
        }

        if (! $dryRun && ! $this->option('force') && ! $this->confirm('Reverse all listed journals and restore invoice amounts?', false)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        $reversed = 0;
        $notesDeleted = 0;

        DB::transaction(function () use ($journals, &$reversed, &$notesDeleted) {
            $recalcInvoices = [];

            foreach ($journals as $journal) {
                $item = $journal->invoiceItem;
                if ($item) {
                    if ($journal->type === 'credit') {
                        $item->amount = (float) $item->amount + (float) $journal->amount;
                    } else {
                        $item->amount = max(0, (float) $item->amount - (float) $journal->amount);
                    }
                    $item->save();
                }

                $pattern = '%Journal: '.$journal->journal_number.'%';

                if ($journal->type === 'credit') {
                    $notesDeleted += CreditNote::where('notes', 'like', $pattern)->delete();
                } else {
                    $notesDeleted += DebitNote::where('notes', 'like', $pattern)->delete();
                }

                if ($journal->invoice_id) {
                    $recalcInvoices[$journal->invoice_id] = $journal->invoice;
                }

                $journal->delete();
                $reversed++;
            }

            foreach ($recalcInvoices as $invoice) {
                if ($invoice) {
                    InvoiceService::recalc($invoice);
                }
            }
        });

        $this->info("Reversed {$reversed} journal(s), deleted {$notesDeleted} credit/debit note(s), recalculated ".count($invoiceIds).' invoice(s).');

        return self::SUCCESS;
    }
}

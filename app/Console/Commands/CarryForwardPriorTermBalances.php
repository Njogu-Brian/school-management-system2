<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Console\Command;

class CarryForwardPriorTermBalances extends Command
{
    protected $signature = 'finance:carry-forward-prior-term
                            {--year= : Academic year (2026+)}
                            {--term= : Term number: 2 or 3}
                            {--class= : Optional classroom id}';

    protected $description = 'Apply prior-term balance lines to existing Term 2/3 invoices (same logic as InvoiceService::ensure).';

    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: 0);
        $term = (int) ($this->option('term') ?: 0);
        if ($year < 2026 || ! in_array($term, [2, 3], true)) {
            $this->error('Required: --year=2026 (or later) and --term=2 or --term=3');

            return self::FAILURE;
        }

        $q = Invoice::query()
            ->where('year', $year)
            ->where('term', $term)
            ->where(function ($q2) {
                $q2->whereNull('status')->orWhere('status', '<>', 'reversed');
            });

        if ($this->option('class')) {
            $q->whereHas('student', fn ($s) => $s->where('classroom_id', (int) $this->option('class')));
        }

        $applied = 0;
        foreach ($q->cursor() as $invoice) {
            if (InvoiceService::applyPriorTermCarryForwardIfNeeded($invoice)) {
                $this->line('Added line: ' . ($invoice->invoice_number ?? '#' . $invoice->id));
                $applied++;
            }
        }

        $this->info("Finished. New prior-term lines on {$applied} invoice(s). Others were skipped (already done or no prior balance).");

        return self::SUCCESS;
    }
}

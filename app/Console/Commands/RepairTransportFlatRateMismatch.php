<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TransportFee;
use App\Services\TransportFeeService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class RepairTransportFlatRateMismatch extends Command
{
    protected $signature = 'transport:repair-flat-rate-mismatch
        {--year=2026 : Academic year number}
        {--term=3 : Term number}
        {--restore-from-invoices : Set transport fee amounts from active invoice transport lines}
        {--adjust= : Apply a flat increase or decrease to all transport fees in the term}
        {--direction=increase : Used with --adjust: increase or decrease}
        {--dry-run : Preview changes without saving}';

    protected $description = 'One-time repair: restore transport fees from invoices and/or apply a flat adjustment before Post Pending Fees.';

    public function handle(): int
    {
        $year = (int) $this->option('year');
        $term = (int) $this->option('term');
        $dryRun = (bool) $this->option('dry-run');
        $restore = (bool) $this->option('restore-from-invoices');
        $adjust = $this->option('adjust');
        $direction = strtolower((string) $this->option('direction'));

        if (! in_array($direction, ['increase', 'decrease'], true)) {
            $this->error('--direction must be increase or decrease.');

            return self::FAILURE;
        }

        if ($adjust !== null && $adjust !== '') {
            $adjust = round((float) $adjust, 2);
            if ($adjust < 0) {
                $this->error('--adjust must be zero or positive.');

                return self::FAILURE;
            }
        } else {
            $adjust = null;
        }

        if (! $restore && $adjust === null) {
            $this->warn('No action selected. Use --restore-from-invoices and/or --adjust=500 --direction=increase');
            $this->line('');
            $this->showMismatchSummary($year, $term);

            return self::SUCCESS;
        }

        $this->line("Year/term: {$year} Term {$term}");
        $this->line('Dry run: '.($dryRun ? 'yes' : 'no'));

        if ($restore) {
            $restored = $this->restoreFromInvoices($year, $term, $dryRun);
            $this->info("Restore from invoices: {$restored} row(s)".($dryRun ? ' (preview)' : ' updated'));
        }

        if ($adjust !== null) {
            $adjusted = $this->applyAdjustment($year, $term, $adjust, $direction, $dryRun);
            $this->info("Adjustment ({$direction} {$adjust}): {$adjusted} row(s)".($dryRun ? ' (preview)' : ' updated'));
        }

        if ($dryRun) {
            $this->comment('Dry run only — re-run without --dry-run to apply.');
        } else {
            $this->comment('Done. Preview Post Pending Fees in the UI before committing.');
        }

        return self::SUCCESS;
    }

    private function showMismatchSummary(int $year, int $term): void
    {
        $rows = $this->mismatchRows($year, $term);

        if ($rows->isEmpty()) {
            $this->info('No transport fee / invoice mismatches found for this term.');

            return;
        }

        $this->table(
            ['Student ID', 'Invoice transport', 'Transport fee', 'Diff'],
            $rows->take(20)->map(fn (array $row) => [
                $row['student_id'],
                number_format($row['invoice_amount'], 2),
                number_format($row['fee_amount'], 2),
                number_format($row['invoice_amount'] - $row['fee_amount'], 2),
            ])->all()
        );

        if ($rows->count() > 20) {
            $this->line('... and '.($rows->count() - 20).' more');
        }

        $this->line("Total mismatches: {$rows->count()}");
    }

    private function mismatchRows(int $year, int $term): Collection
    {
        $transportVoteheadId = TransportFeeService::transportVotehead()->id;

        return TransportFee::query()
            ->where('year', $year)
            ->where('term', $term)
            ->get()
            ->map(function (TransportFee $fee) use ($year, $term, $transportVoteheadId) {
                $invoiceAmount = $this->invoiceTransportAmount($fee->student_id, $year, $term, $transportVoteheadId);

                if ($invoiceAmount === null) {
                    return null;
                }

                if (abs((float) $fee->amount - $invoiceAmount) < 0.01) {
                    return null;
                }

                return [
                    'student_id' => $fee->student_id,
                    'fee' => $fee,
                    'invoice_amount' => $invoiceAmount,
                    'fee_amount' => (float) $fee->amount,
                ];
            })
            ->filter()
            ->values();
    }

    private function invoiceTransportAmount(int $studentId, int $year, int $term, int $transportVoteheadId): ?float
    {
        $invoice = Invoice::query()
            ->where('student_id', $studentId)
            ->where('year', $year)
            ->where('term', $term)
            ->first();

        if (! $invoice) {
            return null;
        }

        $item = InvoiceItem::query()
            ->where('invoice_id', $invoice->id)
            ->where('votehead_id', $transportVoteheadId)
            ->where('source', 'transport')
            ->where('status', 'active')
            ->first();

        return $item ? (float) $item->amount : null;
    }

    private function restoreFromInvoices(int $year, int $term, bool $dryRun): int
    {
        $count = 0;
        $transportVoteheadId = TransportFeeService::transportVotehead()->id;

        foreach ($this->mismatchRows($year, $term) as $row) {
            /** @var TransportFee $fee */
            $fee = $row['fee'];
            $targetAmount = $row['invoice_amount'];

            $this->line("  student {$fee->student_id}: {$fee->amount} -> {$targetAmount} (from invoice)");

            if (! $dryRun) {
                TransportFeeService::upsertFee([
                    'student_id' => $fee->student_id,
                    'year' => $year,
                    'term' => $term,
                    'amount' => $targetAmount,
                    'drop_off_point_id' => $fee->drop_off_point_id,
                    'drop_off_point_name' => $fee->drop_off_point_name,
                    'pricing_mode' => $fee->pricing_mode,
                    'pricing_breakdown' => $fee->pricing_breakdown,
                    'source' => 'manual',
                    'note' => 'Restored from invoice transport line after incorrect flat-rate replacement (artisan transport:repair-flat-rate-mismatch).',
                    'skip_invoice' => true,
                ]);
            }

            $count++;
        }

        return $count;
    }

    private function applyAdjustment(int $year, int $term, float $adjust, string $direction, bool $dryRun): int
    {
        $count = 0;
        $isDecrease = $direction === 'decrease';

        TransportFee::query()
            ->where('year', $year)
            ->where('term', $term)
            ->orderBy('id')
            ->chunkById(200, function ($fees) use ($adjust, $isDecrease, $year, $term, $dryRun, &$count) {
                foreach ($fees as $fee) {
                    $current = (float) $fee->amount;
                    $newAmount = $isDecrease
                        ? max(0, round($current - $adjust, 2))
                        : round($current + $adjust, 2);

                    if (abs($newAmount - $current) < 0.01) {
                        continue;
                    }

                    $this->line("  student {$fee->student_id}: {$current} -> {$newAmount}");

                    if (! $dryRun) {
                        TransportFeeService::upsertFee([
                            'student_id' => $fee->student_id,
                            'year' => $year,
                            'term' => $term,
                            'amount' => $newAmount,
                            'drop_off_point_id' => $fee->drop_off_point_id,
                            'drop_off_point_name' => $fee->drop_off_point_name,
                            'pricing_mode' => 'imported',
                            'pricing_breakdown' => $fee->pricing_breakdown,
                            'source' => 'flat_rate_bulk',
                            'note' => "Transport fee {$direction}d by KES {$adjust} via artisan repair (from {$current} to {$newAmount}). Run Post Pending Fees to update invoices.",
                            'skip_invoice' => true,
                        ]);
                    }

                    $count++;
                }
            });

        return $count;
    }
}

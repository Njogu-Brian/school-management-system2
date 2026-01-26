<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class ShowSampleIssues extends Command
{
    protected $signature = 'transactions:sample-issues {--limit=5 : Number of samples to show}';

    protected $description = 'Show sample issues from the database analysis';

    public function handle()
    {
        $limit = (int) $this->option('limit');

        $this->info("Showing {$limit} samples of each issue type...\n");

        // 1. Unlinked Payments
        $this->showUnlinkedPayments($limit);

        // 2. Confirmed Without Payments
        $this->showConfirmedWithoutPayments($limit);

        // 3. Swimming for Fees
        $this->showSwimmingForFees($limit);

        // 4. Other Issues
        $this->showOtherIssues($limit);

        return 0;
    }

    protected function showUnlinkedPayments($limit)
    {
        $this->info("1. UNLINKED PAYMENTS (Sample):");
        $this->line("─────────────────────────────────────────");

        $samples = DB::table('bank_statement_transactions as t')
            ->join('payments as p', function($join) {
                $join->on('p.transaction_code', '=', 't.reference_number')
                     ->orWhere(function($q) {
                         $q->whereColumn('p.transaction_code', 'LIKE', DB::raw("CONCAT(t.reference_number, '-%')"));
                     });
            })
            ->whereNotNull('t.reference_number')
            ->where('t.reference_number', '!=', '')
            ->where('p.reversed', false)
            ->where(function($q) {
                $q->whereNull('t.payment_id')
                  ->orWhereColumn('t.payment_id', '!=', 'p.id');
            })
            ->select([
                't.id as transaction_id',
                't.reference_number',
                't.status',
                't.payment_created',
                't.payment_id as linked_payment_id',
                'p.id as payment_id',
                'p.transaction_code',
                'p.amount',
                't.amount as transaction_amount'
            ])
            ->limit($limit)
            ->get();

        if ($samples->isEmpty()) {
            $this->line("   No samples found");
        } else {
            foreach ($samples as $sample) {
                $this->line("   Transaction #{$sample->transaction_id}:");
                $this->line("      Ref: {$sample->reference_number}");
                $this->line("      Status: {$sample->status}, Payment Created: " . ($sample->payment_created ? 'Yes' : 'No'));
                $this->line("      Linked Payment ID: " . ($sample->linked_payment_id ?? 'NULL'));
                $this->line("      Found Payment ID: {$sample->payment_id} (Code: {$sample->transaction_code})");
                $this->line("      Amounts: Transaction={$sample->transaction_amount}, Payment={$sample->amount}");
                $this->line("");
            }
        }
        $this->newLine();
    }

    protected function showConfirmedWithoutPayments($limit)
    {
        $this->info("2. CONFIRMED WITHOUT PAYMENTS (Sample):");
        $this->line("─────────────────────────────────────────");

        $samples = DB::table('bank_statement_transactions as t')
            ->leftJoin('payments as p', 'p.id', '=', 't.payment_id')
            ->where('t.status', 'confirmed')
            ->where(function($q) {
                $q->where('t.payment_created', true)
                  ->orWhereNotNull('t.payment_id');
            })
            ->where(function($q) {
                $q->whereNull('p.id')
                  ->orWhere('p.reversed', true)
                  ->orWhereNotNull('p.deleted_at');
            })
            ->select([
                't.id',
                't.reference_number',
                't.status',
                't.payment_created',
                't.payment_id',
                't.student_id',
                't.amount',
                't.is_swimming_transaction'
            ])
            ->limit($limit)
            ->get();

        if ($samples->isEmpty()) {
            $this->line("   No samples found");
        } else {
            foreach ($samples as $sample) {
                $this->line("   Transaction #{$sample->id}:");
                $this->line("      Ref: {$sample->reference_number}");
                $this->line("      Status: {$sample->status}, Payment Created: " . ($sample->payment_created ? 'Yes' : 'No'));
                $this->line("      Payment ID: " . ($sample->payment_id ?? 'NULL'));
                $this->line("      Student ID: " . ($sample->student_id ?? 'NULL'));
                $this->line("      Amount: {$sample->amount}");
                $this->line("      Swimming: " . ($sample->is_swimming_transaction ? 'Yes' : 'No'));
                $this->line("");
            }
        }
        $this->newLine();
    }

    protected function showSwimmingForFees($limit)
    {
        $this->info("3. SWIMMING TRANSACTIONS USED FOR FEES (Sample):");
        $this->line("─────────────────────────────────────────");

        $samples = DB::table('bank_statement_transactions as t')
            ->join('payments as p', 'p.id', '=', 't.payment_id')
            ->join('payment_allocations as pa', 'pa.payment_id', '=', 'p.id')
            ->join('invoice_items as ii', 'ii.id', '=', 'pa.invoice_item_id')
            ->join('invoices as inv', 'inv.id', '=', 'ii.invoice_id')
            ->where('t.is_swimming_transaction', true)
            ->where('p.reversed', false)
            ->whereNull('p.deleted_at')
            ->select([
                't.id as transaction_id',
                't.reference_number',
                't.amount as transaction_amount',
                'p.id as payment_id',
                'p.amount as payment_amount',
                'inv.invoice_number',
                'pa.amount as allocation_amount'
            ])
            ->limit($limit)
            ->get();

        if ($samples->isEmpty()) {
            $this->line("   No samples found");
        } else {
            foreach ($samples as $sample) {
                $this->line("   Transaction #{$sample->transaction_id}:");
                $this->line("      Ref: {$sample->reference_number}");
                $this->line("      Payment ID: {$sample->payment_id}");
                $this->line("      Invoice: {$sample->invoice_number}");
                $this->line("      Allocation: {$sample->allocation_amount} (should be in swimming wallet)");
                $this->line("");
            }
        }
        $this->newLine();
    }

    protected function showOtherIssues($limit)
    {
        $this->info("4. OTHER ISSUES (Sample):");
        $this->line("─────────────────────────────────────────");

        // Reversed payment still linked
        $reversed = DB::table('bank_statement_transactions as t')
            ->join('payments as p', 'p.id', '=', 't.payment_id')
            ->where('p.reversed', true)
            ->where('t.payment_created', true)
            ->select([
                't.id',
                't.reference_number',
                't.payment_id',
                'p.reversed_at'
            ])
            ->limit($limit)
            ->get();

        if ($reversed->isNotEmpty()) {
            $this->line("   Reversed Payment Still Linked:");
            foreach ($reversed as $item) {
                $this->line("      Transaction #{$item->id} (Ref: {$item->reference_number})");
                $this->line("         Payment #{$item->payment_id} was reversed at {$item->reversed_at}");
            }
        }

        // Mismatched amounts
        $mismatched = DB::table('bank_statement_transactions as t')
            ->join('payments as p', 'p.id', '=', 't.payment_id')
            ->where('t.payment_created', true)
            ->where('p.reversed', false)
            ->whereNull('p.deleted_at')
            ->whereRaw('ABS(t.amount - p.amount) > 0.01')
            ->select([
                't.id',
                't.reference_number',
                't.amount as transaction_amount',
                'p.amount as payment_amount',
                DB::raw("ABS(t.amount - p.amount) as difference")
            ])
            ->limit($limit)
            ->get();

        if ($mismatched->isNotEmpty()) {
            $this->line("\n   Mismatched Amounts:");
            foreach ($mismatched as $item) {
                $this->line("      Transaction #{$item->id} (Ref: {$item->reference_number})");
                $this->line("         Transaction: {$item->transaction_amount}, Payment: {$item->payment_amount}");
                $this->line("         Difference: {$item->difference}");
            }
        }

        $this->newLine();
    }
}

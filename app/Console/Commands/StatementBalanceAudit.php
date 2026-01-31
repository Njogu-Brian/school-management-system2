<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Services\StudentBalanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class StatementBalanceAudit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:statement-balance-audit
                            {--year= : Academic year to audit (default: current year)}
                            {--term= : Term ID or number to audit}
                            {--export= : Export mismatches to CSV file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit student statement balances against invoice balances for the selected period';

    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        $term = $this->option('term');

        $this->info("🔍 Auditing statement balances for year {$year}" . ($term ? " (term {$term})" : ' (all terms)') . '...');

        $swimmingPaymentIds = $this->getSwimmingPaymentIdsForStatement();
        $mismatches = [];

        $students = Student::where('archive', false)
            ->where('is_alumni', false)
            ->get();

        foreach ($students as $student) {
            $invoicesQuery = Invoice::where('student_id', $student->id)
                ->where(function ($q) use ($year) {
                    $q->where('year', $year)
                        ->orWhereHas('academicYear', function ($q2) use ($year) {
                            $q2->where('year', $year);
                        });
                })
                ->whereNull('reversed_at')
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'reversed');
                })
                ->with(['items.votehead', 'term', 'academicYear']);

            if ($term) {
                $invoicesQuery->whereHas('term', function ($q) use ($term) {
                    $q->where('name', 'like', "%Term {$term}%")
                        ->orWhere('id', $term);
                });
            }

            $invoices = $invoicesQuery->get();
            if ($invoices->isEmpty()) {
                continue;
            }

            $invoiceIds = $invoices->pluck('id')->toArray();
            $hasBalanceBroughtForwardInInvoices = $invoices->flatMap->items->contains(function ($item) {
                $voteheadCode = $item->votehead->code ?? null;
                return ($item->source ?? null) === 'balance_brought_forward' || $voteheadCode === 'BAL_BF';
            });

            $balanceBroughtForward = ($year < 2026 || $hasBalanceBroughtForwardInInvoices)
                ? 0
                : StudentBalanceService::getBalanceBroughtForward($student);

            $totalCharges = $invoices->sum(function ($inv) {
                return $inv->items
                    ->filter(fn($i) => ($i->source ?? null) !== 'swimming_attendance')
                    ->sum('amount');
            });

            $totalDiscounts = $invoices->sum('discount_amount') + $invoices->sum(function ($inv) {
                return $inv->items
                    ->filter(fn($i) => ($i->source ?? null) !== 'swimming_attendance')
                    ->sum('discount_amount');
            });

            $totalPayments = 0;
            if (!empty($invoiceIds)) {
                $totalPayments = (float) PaymentAllocation::whereHas('invoiceItem', function ($q) use ($invoiceIds) {
                    $q->whereIn('invoice_id', $invoiceIds);
                    $q->where(function ($q2) {
                        $q2->whereNull('source')->orWhere('source', '!=', 'swimming_attendance');
                    });
                })->whereHas('payment', function ($q) use ($swimmingPaymentIds) {
                    $q->where('reversed', false);
                    if (!empty($swimmingPaymentIds)) {
                        $q->whereNotIn('id', $swimmingPaymentIds);
                    }
                })->sum('amount');
            }

            $statementBalance = $totalCharges - $totalDiscounts - $totalPayments + $balanceBroughtForward;

            $storedInvoiceBalance = $invoices->sum('balance');
            $expectedBalance = $storedInvoiceBalance + ($hasBalanceBroughtForwardInInvoices ? 0 : $balanceBroughtForward);

            $difference = $statementBalance - $expectedBalance;
            if (abs($difference) > 0.01) {
                $mismatches[] = [
                    'student' => $student->full_name,
                    'admission_number' => $student->admission_number,
                    'statement_balance' => $statementBalance,
                    'invoice_balance' => $expectedBalance,
                    'difference' => $difference,
                ];
            }
        }

        $this->info('✅ Audit complete.');
        $this->line('Mismatches found: ' . count($mismatches));

        if (!empty($mismatches)) {
            $this->newLine();
            $this->warn('Sample mismatches (first 10):');
            foreach (array_slice($mismatches, 0, 10) as $row) {
                $this->line(" - {$row['student']} ({$row['admission_number']}): statement {$row['statement_balance']} vs invoice {$row['invoice_balance']} (diff {$row['difference']})");
            }
        }

        if ($this->option('export')) {
            $this->exportCsv($this->option('export'), $mismatches);
        }

        return empty($mismatches) ? 0 : 1;
    }

    private function exportCsv(string $path, array $rows): void
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, ['Student', 'Admission Number', 'Statement Balance', 'Invoice Balance', 'Difference']);
        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['student'],
                $row['admission_number'],
                number_format($row['statement_balance'], 2, '.', ''),
                number_format($row['invoice_balance'], 2, '.', ''),
                number_format($row['difference'], 2, '.', ''),
            ]);
        }
        fclose($handle);
        $this->info("📄 Exported report to {$path}");
    }

    private function getSwimmingPaymentIdsForStatement(): array
    {
        $ids = collect();
        if (Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction')) {
            $ids = $ids->merge(
                \App\Models\BankStatementTransaction::where('is_swimming_transaction', true)
                    ->whereNotNull('payment_id')
                    ->pluck('payment_id')
            );
        }
        if (Schema::hasColumn('mpesa_c2b_transactions', 'is_swimming_transaction')) {
            $ids = $ids->merge(
                \App\Models\MpesaC2BTransaction::where('is_swimming_transaction', true)
                    ->whereNotNull('payment_id')
                    ->pluck('payment_id')
            );
        }
        return $ids->unique()->filter()->values()->toArray();
    }
}

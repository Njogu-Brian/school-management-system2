<?php

namespace App\Console\Commands;

use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Console\Command;

class ReviewUnallocatedPayments extends Command
{
    protected $signature = 'finance:review-unallocated-payments
                            {--limit=100 : Max number of payments to list}
                            {--csv= : Export to CSV file path}';

    protected $description = 'Review unallocated (non-reversed) payments: check if assigned students have outstanding invoices and report why allocation may be skipped';

    public function handle(): int
    {
        $this->info('Reviewing unallocated payments (reversed payments are excluded)...');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $payments = Payment::where('reversed', false)
            ->where('receipt_number', 'not like', 'SWIM-%')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('unallocated_amount', '>', 0)
                  ->orWhereRaw('amount > COALESCE(allocated_amount, 0)');
            })
            ->with('student:id,first_name,last_name,admission_number')
            ->orderByDesc('payment_date')
            ->limit($limit)
            ->get();

        if ($payments->isEmpty()) {
            $this->info('No unallocated payments found.');
            return 0;
        }

        $rows = [];
        $withOutstanding = 0;
        $withoutOutstanding = 0;

        foreach ($payments as $payment) {
            $unallocated = $payment->unallocated_amount ?? max(0, (float) $payment->amount - (float) ($payment->allocated_amount ?? 0));
            $studentName = $payment->student
                ? trim($payment->student->first_name . ' ' . $payment->student->last_name) . ' (' . ($payment->student->admission_number ?? '') . ')'
                : '—';
            $hasUnpaid = false;
            $reason = '';

            if (!$payment->student_id || !$payment->student) {
                $reason = 'No student associated';
                $withoutOutstanding++;
            } else {
                $unpaidItems = InvoiceItem::whereHas('invoice', function ($q) use ($payment) {
                    $q->where('student_id', $payment->student_id)
                      ->where('status', '!=', 'paid');
                })
                    ->where('status', 'active')
                    ->get()
                    ->filter(function ($item) {
                        return $item->getBalance() > 0;
                    });

                if ($unpaidItems->isNotEmpty()) {
                    $hasUnpaid = true;
                    $withOutstanding++;
                    $totalOutstanding = $unpaidItems->sum(fn ($i) => $i->getBalance());
                    $reason = sprintf(
                        'Has %d unpaid item(s), Ksh %s outstanding — run "Allocate Unallocated Payments" or check invoice item status/balance',
                        $unpaidItems->count(),
                        number_format($totalOutstanding, 2)
                    );
                } else {
                    $withoutOutstanding++;
                    $reason = 'No outstanding invoices (overpayment / carry forward)';
                }
            }

            $rows[] = [
                $payment->receipt_number,
                $studentName,
                number_format($payment->amount, 2),
                number_format($unallocated, 2),
                $hasUnpaid ? 'Yes' : 'No',
                $reason,
            ];
        }

        $this->table(
            ['Receipt', 'Student', 'Amount', 'Unallocated', 'Has outstanding invoices?', 'Reason'],
            $rows
        );

        $this->newLine();
        $this->info("Summary: {$payments->count()} unallocated payment(s) reviewed.");
        $this->line("  — {$withOutstanding} with outstanding invoices (candidates for bulk allocation).");
        $this->line("  — {$withoutOutstanding} with no outstanding invoices (overpayment/carry forward or no student).");
        $this->newLine();
        $this->comment('Reversed payments are never shown as unallocated in the Finance > Payments list (filter by Allocation = Unallocated).');

        if ($path = $this->option('csv')) {
            $this->exportCsv($path, $rows);
            $this->info("Exported to {$path}");
        }

        return 0;
    }

    protected function exportCsv(string $path, array $rows): void
    {
        $headers = ['Receipt', 'Student', 'Amount', 'Unallocated', 'Has outstanding invoices?', 'Reason'];
        $fp = fopen($path, 'w');
        if (!$fp) {
            $this->error("Could not open file: {$path}");
            return;
        }
        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }
}

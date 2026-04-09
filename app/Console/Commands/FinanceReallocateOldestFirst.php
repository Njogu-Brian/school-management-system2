<?php

namespace App\Console\Commands;

use App\Models\{Invoice, Payment, Student};
use App\Services\{InvoiceService, OldestInvoiceFirstAllocator};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FinanceReallocateOldestFirst extends Command
{
    protected $signature = 'finance:payments:reallocate-oldest-first
                            {--year=2026 : Academic year (invoice.year)}
                            {--upToTerm=2 : Reallocate across invoices up to this term}
                            {--student= : Student admission number or ID (optional)}
                            {--dry-run : Show what would be done without making changes}
                            {--output-dir= : Output directory under storage/app (optional)}';

    protected $description = 'Reallocate real payments oldest-invoice-first (no new payments).';

    public function handle(OldestInvoiceFirstAllocator $allocator): int
    {
        $year = (int) $this->option('year');
        $upToTerm = (int) $this->option('upToTerm');
        $dryRun = (bool) $this->option('dry-run');
        $studentOpt = $this->option('student');

        $outputDir = $this->option('output-dir') ?: "finance-migrations/cf_{$year}_t{$upToTerm}";
        $pathAfter = rtrim($outputDir, '/') . '/after.json';

        $student = null;
        if ($studentOpt) {
            $student = is_numeric($studentOpt)
                ? Student::find((int) $studentOpt)
                : Student::where('admission_number', (string) $studentOpt)->first();
            if (!$student) {
                $this->error("Student not found: {$studentOpt}");
                return 1;
            }
        }

        // Only students with invoices in scope
        $studentIds = Invoice::query()
            ->where('year', $year)
            ->where('term', '<=', $upToTerm)
            ->where('status', '!=', 'reversed')
            ->when($student, fn ($q) => $q->where('student_id', $student->id))
            ->distinct()
            ->pluck('student_id')
            ->filter()
            ->values();

        $out = [
            'scope' => [
                'year' => $year,
                'upToTerm' => $upToTerm,
                'student' => $student ? ['id' => $student->id, 'admission_number' => $student->admission_number] : null,
            ],
            'dry_run' => $dryRun,
            'students' => [],
            'generated_at' => now()->toIso8601String(),
        ];

        foreach ($studentIds as $sid) {
            $stu = Student::find($sid);
            if (!$stu) continue;

            $studentLog = [
                'student_id' => $sid,
                'admission_number' => $stu->admission_number,
                'payments_considered' => 0,
                'allocations_deleted' => 0,
                'payments_reallocated' => 0,
                'invoice_ids' => [],
            ];

            DB::transaction(function () use ($allocator, $year, $upToTerm, $dryRun, $stu, &$studentLog) {
                $studentId = (int) $stu->id;
                $invoices = $allocator->collectInvoicesOldestFirst($studentId, $year, $upToTerm);
                $studentLog['invoice_ids'] = $invoices->pluck('id')->all();

                // Real payments only (exclude internal transfers and swimming)
                $payments = Payment::query()
                    ->where('student_id', $studentId)
                    ->where('reversed', false)
                    ->whereRaw("COALESCE(receipt_number, '') NOT LIKE 'SWIM-%'")
                    ->where(function ($q) {
                        $q->whereNull('payment_channel')
                            ->orWhereNotIn('payment_channel', ['term_balance_transfer', 'balance_brought_forward']);
                    })
                    ->where(function ($q) {
                        $q->whereNull('payment_method')
                            ->orWhereRaw('LOWER(payment_method) != ?', ['internal transfer']);
                    })
                    ->where(function ($q) {
                        $q->whereNull('transaction_code')
                            ->orWhereRaw("transaction_code NOT LIKE 'TERM-CF-%'");
                    })
                    ->orderBy('payment_date')
                    ->orderBy('id')
                    ->get();

                $studentLog['payments_considered'] = $payments->count();

                foreach ($payments as $payment) {
                    $existingAllocCount = (int) $payment->allocations()->count();
                    if ($existingAllocCount <= 0) {
                        continue;
                    }

                    if ($dryRun) {
                        $studentLog['payments_reallocated']++;
                        continue;
                    }

                    // Delete allocations then reallocate using oldest invoice first
                    $deleted = (int) $payment->allocations()->delete();
                    $studentLog['allocations_deleted'] += $deleted;
                    $payment->updateAllocationTotals();

                    $allocator->allocatePaymentAcrossInvoices($payment, $invoices);
                    $studentLog['payments_reallocated']++;
                }

                if (!$dryRun) {
                    // Recalc invoices in scope after allocations change
                    foreach ($invoices as $inv) {
                        InvoiceService::recalc($inv);
                    }
                }
            });

            if ($dryRun) {
                $label = $stu->admission_number ?: (string) $stu->id;
                $this->line("{$label}: would reallocate {$studentLog['payments_reallocated']} payment(s)");
            }

            $out['students'][] = $studentLog;
        }

        Storage::disk('local')->put($pathAfter, json_encode($out, JSON_PRETTY_PRINT));
        $this->info("Wrote: storage/app/{$pathAfter}");

        return 0;
    }
}


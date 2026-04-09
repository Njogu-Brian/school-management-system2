<?php

namespace App\Console\Commands;

use App\Models\{Invoice, InvoiceItem, Payment, PaymentAllocation, Student, Votehead};
use App\Services\InvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FinanceCarryForwardReverse extends Command
{
    protected $signature = 'finance:cf:reverse
                            {--year=2026 : Academic year (invoice.year)}
                            {--term=2 : Term number (invoice.term)}
                            {--student= : Student admission number or ID (optional)}
                            {--dry-run : Show what would be done without making changes}
                            {--output-dir= : Output directory under storage/app (optional)}';

    protected $description = 'Reverse Term1→Term2 carry-forward internal transfers (mark transfer payments reversed, remove allocations, remove Term2 carry-forward line).';

    public function handle(): int
    {
        $year = (int) $this->option('year');
        $term = (int) $this->option('term');
        $dryRun = (bool) $this->option('dry-run');
        $studentOpt = $this->option('student');

        $outputDir = $this->option('output-dir') ?: "finance-migrations/cf_{$year}_t{$term}";
        $pathLog = rtrim($outputDir, '/') . '/reverse_log.json';

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

        $txPrefix = "TERM-CF-{$year}-T{$term}-S";
        $priorVoteheadId = Votehead::where('code', 'PRIOR_TERM_ARREARS')->value('id');

        $transferPaymentsQuery = Payment::query()
            ->where(function ($q) use ($txPrefix) {
                $q->where('transaction_code', 'like', $txPrefix . '%')
                    ->orWhere('payment_channel', 'term_balance_transfer')
                    ->orWhereRaw('LOWER(payment_method) = ?', ['internal transfer']);
            })
            ->where('reversed', false);
        if ($student) {
            $transferPaymentsQuery->where('student_id', $student->id);
        }
        $transferPayments = $transferPaymentsQuery->get();

        $studentIds = $transferPayments->pluck('student_id')->filter()->unique()->values();

        $carryStudentIds = InvoiceItem::query()
            ->where(function ($q) use ($priorVoteheadId) {
                $q->where('source', 'prior_term_carryforward');
                if ($priorVoteheadId) {
                    $q->orWhere('votehead_id', $priorVoteheadId);
                }
            })
            ->whereHas('invoice', function ($q) use ($year, $term, $student) {
                $q->where('year', $year)->where('term', $term)->where('status', '!=', 'reversed');
                if ($student) {
                    $q->where('student_id', $student->id);
                }
            })
            ->with('invoice:id,student_id')
            ->get()
            ->pluck('invoice.student_id')
            ->filter()
            ->unique()
            ->values();

        $studentIds = $studentIds->merge($carryStudentIds)->unique()->values();

        $log = [
            'scope' => [
                'year' => $year,
                'term' => $term,
                'student' => $student ? ['id' => $student->id, 'admission_number' => $student->admission_number] : null,
            ],
            'dry_run' => $dryRun,
            'students' => [],
            'summary' => [
                'students_count' => (int) $studentIds->count(),
                'transfer_payments_marked_reversed' => 0,
                'transfer_allocations_deleted' => 0,
                'carry_items_deleted' => 0,
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        foreach ($studentIds as $sid) {
            $stu = Student::find($sid);
            if (!$stu) continue;

            $stuLog = [
                'student_id' => $sid,
                'admission_number' => $stu->admission_number,
                'transfer_payments' => [],
                'allocations_deleted' => 0,
                'carry_items_deleted' => 0,
                'recalced_invoice_ids' => [],
            ];

            $result = DB::transaction(function () use ($year, $term, $txPrefix, $priorVoteheadId, $dryRun, $stu, &$stuLog) {
                $studentId = (int) $stu->id;

                $transferPays = Payment::query()
                    ->where('student_id', $studentId)
                    ->where('reversed', false)
                    ->where(function ($q) use ($txPrefix) {
                        $q->where('transaction_code', 'like', $txPrefix . '%')
                            ->orWhere('payment_channel', 'term_balance_transfer')
                            ->orWhereRaw('LOWER(payment_method) = ?', ['internal transfer']);
                    })
                    ->get();

                $transferPayIds = $transferPays->pluck('id')->all();
                $allocCount = 0;
                if (!empty($transferPayIds)) {
                    $allocCount = (int) PaymentAllocation::query()->whereIn('payment_id', $transferPayIds)->count();
                }

                // Carry-forward items on term invoice(s)
                $termInvoiceIds = Invoice::query()
                    ->where('student_id', $studentId)
                    ->where('year', $year)
                    ->where('term', $term)
                    ->where('status', '!=', 'reversed')
                    ->pluck('id')
                    ->all();

                $carryItems = !empty($termInvoiceIds)
                    ? InvoiceItem::query()
                        ->whereIn('invoice_id', $termInvoiceIds)
                        ->where(function ($q) use ($priorVoteheadId) {
                            $q->where('source', 'prior_term_carryforward');
                            if ($priorVoteheadId) {
                                $q->orWhere('votehead_id', $priorVoteheadId);
                            }
                        })
                        ->get(['id', 'invoice_id'])
                    : collect();

                $carryCount = (int) $carryItems->count();

                $stuLog['transfer_payments'] = $transferPays->map(fn ($p) => [
                    'id' => $p->id,
                    'transaction_code' => $p->transaction_code,
                    'receipt_number' => $p->receipt_number,
                    'amount' => (float) $p->amount,
                ])->all();

                if ($dryRun) {
                    return [
                        'transfer_payments' => $transferPays,
                        'alloc_deleted' => 0,
                        'carry_deleted' => 0,
                        'recalced' => [],
                        'alloc_count' => $allocCount,
                        'carry_count' => $carryCount,
                    ];
                }

                // 1) Delete allocations for transfer payments
                if (!empty($transferPayIds)) {
                    $deleted = PaymentAllocation::query()->whereIn('payment_id', $transferPayIds)->delete();
                    $stuLog['allocations_deleted'] += (int) $deleted;
                }

                // 2) Mark transfer payments reversed
                foreach ($transferPays as $p) {
                    $p->update([
                        'reversed' => true,
                        'reversed_at' => now(),
                        'reversal_reason' => "Reverse Term1→Term{$term} carry-forward internal transfer (year {$year})",
                        'reversed_by' => auth()->id(),
                    ]);
                }

                // 3) Delete carry-forward items on term invoice(s)
                if ($carryItems->isNotEmpty()) {
                    $carryIds = $carryItems->pluck('id')->all();
                    $deleted = InvoiceItem::query()->whereIn('id', $carryIds)->delete();
                    $stuLog['carry_items_deleted'] += (int) $deleted;
                }

                // 4) Recalc affected invoices (all year<=term invoices for that student, plus current term invoice)
                $invoicesToRecalc = Invoice::query()
                    ->where('student_id', $studentId)
                    ->where('year', $year)
                    ->where('term', '<=', $term)
                    ->where('status', '!=', 'reversed')
                    ->get();

                foreach ($invoicesToRecalc as $inv) {
                    InvoiceService::recalc($inv);
                    $stuLog['recalced_invoice_ids'][] = $inv->id;
                }

                return [
                    'transfer_payments' => $transferPays,
                    'alloc_deleted' => $stuLog['allocations_deleted'],
                    'carry_deleted' => $stuLog['carry_items_deleted'],
                    'recalced' => $stuLog['recalced_invoice_ids'],
                    'alloc_count' => $allocCount,
                    'carry_count' => $carryCount,
                ];
            });

            if ($dryRun) {
                $label = $stu->admission_number ?: (string) $stu->id;
                $this->line("{$label}: would reverse " . count($result['transfer_payments']) . " transfer payment(s), delete {$result['alloc_count']} allocation(s), delete {$result['carry_count']} carry item(s)");
            }

            $log['students'][] = $stuLog;
            $log['summary']['transfer_allocations_deleted'] += (int) ($result['alloc_deleted'] ?? 0);
            $log['summary']['carry_items_deleted'] += (int) ($result['carry_deleted'] ?? 0);
            $log['summary']['transfer_payments_marked_reversed'] += (int) count($result['transfer_payments'] ?? []);
        }

        Storage::disk('local')->put($pathLog, json_encode($log, JSON_PRETTY_PRINT));
        $this->info("Wrote: storage/app/{$pathLog}");

        return 0;
    }
}


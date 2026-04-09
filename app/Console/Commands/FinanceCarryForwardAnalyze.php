<?php

namespace App\Console\Commands;

use App\Models\{Invoice, InvoiceItem, Payment, PaymentAllocation, Student, Votehead};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FinanceCarryForwardAnalyze extends Command
{
    protected $signature = 'finance:cf:analyze
                            {--year=2026 : Academic year (invoice.year)}
                            {--term=2 : Term number (invoice.term)}
                            {--student= : Student admission number or ID (optional)}
                            {--output-dir= : Output directory under storage/app (optional)}';

    protected $description = 'Analyze Term1→Term2 carry-forward internal transfers and affected invoices/payments (read-only).';

    public function handle(): int
    {
        $year = (int) $this->option('year');
        $term = (int) $this->option('term');
        $studentOpt = $this->option('student');

        $outputDir = $this->option('output-dir') ?: "finance-migrations/cf_{$year}_t{$term}";
        $pathBefore = rtrim($outputDir, '/') . '/before.json';

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
            ->where('reversed', false)
            ->where(function ($q) use ($txPrefix) {
                $q->where('transaction_code', 'like', $txPrefix . '%')
                    ->orWhere('payment_channel', 'term_balance_transfer')
                    ->orWhereRaw('LOWER(payment_method) = ?', ['internal transfer']);
            });
        if ($student) {
            $transferPaymentsQuery->where('student_id', $student->id);
        }
        $transferPayments = $transferPaymentsQuery->get();

        $studentIds = $transferPayments->pluck('student_id')->filter()->unique()->values();

        // Also include students who have the carry-forward line item (in case transfer payment is missing/reversed already)
        $carryItemStudentIds = InvoiceItem::query()
            ->where('source', 'prior_term_carryforward')
            ->when($priorVoteheadId, fn ($q) => $q->orWhere('votehead_id', $priorVoteheadId))
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

        $studentIds = $studentIds->merge($carryItemStudentIds)->unique()->values();

        $out = [
            'scope' => [
                'year' => $year,
                'term' => $term,
                'student' => $student ? ['id' => $student->id, 'admission_number' => $student->admission_number] : null,
            ],
            'patterns' => [
                'transfer_tx_prefix' => $txPrefix,
                'carry_item_source' => 'prior_term_carryforward',
                'carry_votehead_code' => 'PRIOR_TERM_ARREARS',
                'transfer_payment_channel' => 'term_balance_transfer',
                'transfer_payment_method' => 'Internal transfer',
            ],
            'students' => [],
            'summary' => [
                'students_count' => (int) $studentIds->count(),
                'transfer_payments_count' => (int) $transferPayments->count(),
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        foreach ($studentIds as $sid) {
            $stu = Student::find($sid);
            if (!$stu) continue;

            $term1Invoice = Invoice::query()
                ->where('student_id', $sid)
                ->where('year', $year)
                ->where('term', 1)
                ->where('status', '!=', 'reversed')
                ->first();

            $term2Invoice = Invoice::query()
                ->where('student_id', $sid)
                ->where('year', $year)
                ->where('term', $term)
                ->where('status', '!=', 'reversed')
                ->first();

            if ($term1Invoice) {
                $term1Invoice->recalculate();
            }
            if ($term2Invoice) {
                $term2Invoice->recalculate();
            }

            $transferPays = $transferPayments->where('student_id', $sid)->values();
            $transferPayIds = $transferPays->pluck('id')->all();
            $transferAlloc = $transferPayIds
                ? PaymentAllocation::query()->whereIn('payment_id', $transferPayIds)->get()
                : collect();

            $carryItems = $term2Invoice
                ? InvoiceItem::query()
                    ->where('invoice_id', $term2Invoice->id)
                    ->where(function ($q) use ($priorVoteheadId) {
                        $q->where('source', 'prior_term_carryforward');
                        if ($priorVoteheadId) {
                            $q->orWhere('votehead_id', $priorVoteheadId);
                        }
                    })
                    ->get()
                : collect();

            $carryAmount = (float) $carryItems->sum('amount');
            $transferAmount = (float) $transferPays->sum('amount');

            $out['students'][] = [
                'student' => [
                    'id' => $stu->id,
                    'admission_number' => $stu->admission_number,
                    'name' => $stu->full_name ?? trim(($stu->first_name ?? '') . ' ' . ($stu->last_name ?? '')),
                ],
                'invoices' => [
                    'term1' => $term1Invoice ? [
                        'id' => $term1Invoice->id,
                        'invoice_number' => $term1Invoice->invoice_number,
                        'total' => (float) $term1Invoice->total,
                        'paid_amount' => (float) $term1Invoice->paid_amount,
                        'balance' => (float) $term1Invoice->balance,
                        'status' => $term1Invoice->status,
                    ] : null,
                    'term2' => $term2Invoice ? [
                        'id' => $term2Invoice->id,
                        'invoice_number' => $term2Invoice->invoice_number,
                        'total' => (float) $term2Invoice->total,
                        'paid_amount' => (float) $term2Invoice->paid_amount,
                        'balance' => (float) $term2Invoice->balance,
                        'status' => $term2Invoice->status,
                    ] : null,
                ],
                'carry_forward' => [
                    'term' => $term,
                    'items' => $carryItems->map(fn ($i) => [
                        'id' => $i->id,
                        'votehead_id' => $i->votehead_id,
                        'amount' => (float) $i->amount,
                        'source' => $i->source,
                    ])->all(),
                    'amount' => $carryAmount,
                ],
                'internal_transfers' => [
                    'payments' => $transferPays->map(fn ($p) => [
                        'id' => $p->id,
                        'transaction_code' => $p->transaction_code,
                        'receipt_number' => $p->receipt_number,
                        'amount' => (float) $p->amount,
                        'payment_channel' => $p->payment_channel,
                        'payment_method' => $p->payment_method,
                        'reversed' => (bool) $p->reversed,
                    ])->all(),
                    'allocations' => $transferAlloc->map(fn ($a) => [
                        'id' => $a->id,
                        'payment_id' => $a->payment_id,
                        'invoice_item_id' => $a->invoice_item_id,
                        'amount' => (float) $a->amount,
                    ])->all(),
                    'amount' => $transferAmount,
                ],
                'sanity' => [
                    'transfer_amount_equals_carry_amount' => abs($transferAmount - $carryAmount) <= 0.05,
                    'transfer_amount' => $transferAmount,
                    'carry_amount' => $carryAmount,
                ],
            ];
        }

        Storage::disk('local')->put($pathBefore, json_encode($out, JSON_PRETTY_PRINT));
        $this->info("Wrote: storage/app/{$pathBefore}");
        $this->info("Students: " . count($out['students']));

        return 0;
    }
}


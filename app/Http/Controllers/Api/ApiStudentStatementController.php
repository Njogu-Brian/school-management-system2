<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Services\StudentBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ApiStudentStatementController extends Controller
{
    /**
     * Fee statement summary for mobile (aligned with finance.types StudentStatement).
     */
    public function show(Request $request, int $id)
    {
        $student = Student::with(['classroom', 'stream'])->findOrFail($id);
        $this->authorizeStudentAccess($request, $student);

        $year = (int) $request->input('year', (int) date('Y'));
        $detailed = $request->boolean('detailed', true);

        $invoices = Invoice::where('student_id', $student->id)
            ->where(function ($q) use ($year) {
                $q->where('year', $year)
                    ->orWhereHas('academicYear', fn ($q2) => $q2->where('year', $year));
            })
            ->whereNull('reversed_at')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'reversed');
            })
            ->when($detailed, fn ($q) => $q->with(['items.votehead', 'term']))
            ->orderBy('created_at')
            ->get();

        foreach ($invoices as $inv) {
            if (method_exists($inv, 'recalculate')) {
                $inv->recalculate();
            }
        }

        $invoiceIds = $invoices->pluck('id')->toArray();

        $paymentsQuery = Payment::with($detailed ? ['paymentMethod', 'allocations.invoiceItem.votehead'] : ['paymentMethod'])
            ->where('student_id', $student->id)
            ->whereNull('deleted_at')
            ->where('reversed', false)
            ->orderBy('payment_date');

        if ($year >= 2026 && ! empty($invoiceIds)) {
            $paymentsQuery->whereHas('allocations', function ($q) use ($invoiceIds) {
                $q->whereHas('invoiceItem', fn ($q2) => $q2->whereIn('invoice_id', $invoiceIds));
            });
        } else {
            $paymentsQuery->whereYear('payment_date', $year);
        }

        $payments = $paymentsQuery->get();

        $totalInvoiced = (float) $invoices->sum(fn ($i) => (float) ($i->total ?? 0));
        $totalPaid = (float) $payments->sum('amount');
        $closing = (float) StudentBalanceService::getTotalOutstandingBalance($student);

        $transactions = $detailed
            ? $this->buildDetailedTransactions($student, $year, $invoices, $payments)
            : $this->buildSummaryTransactions($invoices, $payments);

        $fullName = trim(($student->first_name ?? '').' '.($student->middle_name ?? '').' '.($student->last_name ?? ''));

        return response()->json([
            'success' => true,
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'full_name' => $fullName,
                    'admission_number' => $student->admission_number ?? '',
                    'class_name' => ($student->classroom->name ?? '').($student->stream ? ' '.$student->stream->name : ''),
                ],
                'year' => $year,
                'detailed' => $detailed,
                'opening_balance' => 0,
                'total_invoiced' => round($totalInvoiced, 2),
                'total_paid' => round($totalPaid, 2),
                'closing_balance' => round($closing, 2),
                'transactions' => array_values($transactions),
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildSummaryTransactions($invoices, $payments): array
    {
        $transactions = [];

        foreach ($invoices as $inv) {
            $transactions[] = [
                'id' => 1_000_000 + (int) $inv->id,
                'entity_type' => 'invoice',
                'entity_id' => (int) $inv->id,
                'date' => $inv->created_at->format('Y-m-d'),
                'type' => 'invoice',
                'reference' => $inv->invoice_number ?? (string) $inv->id,
                'description' => 'Invoice '.($inv->invoice_number ?? $inv->id),
                'debit' => (float) ($inv->total ?? 0),
                'credit' => 0,
                'balance' => 0,
            ];
        }

        foreach ($payments as $pay) {
            $transactions[] = [
                'id' => 2_000_000 + (int) $pay->id,
                'entity_type' => 'payment',
                'entity_id' => (int) $pay->id,
                'date' => Carbon::parse($pay->payment_date)->format('Y-m-d'),
                'type' => 'payment',
                'reference' => $pay->transaction_code ?? $pay->receipt_number ?? (string) $pay->id,
                'description' => 'Payment — '.($pay->paymentMethod?->name ?? 'Payment'),
                'debit' => 0,
                'credit' => (float) $pay->amount,
                'balance' => 0,
            ];
        }

        return $this->sortAndBalance($transactions);
    }

    /**
     * Votehead-level lines aligned with web statement detail.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildDetailedTransactions(Student $student, int $year, $invoices, $payments): array
    {
        $transactions = [];
        $seq = 0;

        if ($year >= 2026) {
            $legacyBbf = StudentBalanceService::getBalanceBroughtForward($student);
            if ($legacyBbf !== null && abs((float) $legacyBbf) >= 0.01) {
                $bfDate = Carbon::createFromDate($year, 1, 1)->format('Y-m-d');
                $isDebit = (float) $legacyBbf > 0;
                $transactions[] = [
                    'id' => 100_000 + (++$seq),
                    'entity_type' => 'bbf',
                    'entity_id' => null,
                    'date' => $bfDate,
                    'type' => 'Balance Brought Forward',
                    'reference' => 'BBF-'.($year - 1),
                    'description' => $isDebit
                        ? 'Balance brought forward from '.($year - 1)
                        : 'Overpayment brought forward from '.($year - 1),
                    'votehead' => 'Balance Brought Forward',
                    'debit' => $isDebit ? abs((float) $legacyBbf) : 0,
                    'credit' => $isDebit ? 0 : abs((float) $legacyBbf),
                    'balance' => 0,
                ];
            }
        }

        foreach ($invoices as $inv) {
            $termLabel = $inv->term->name ?? 'Term';
            foreach ($inv->items as $item) {
                if (($item->source ?? null) === 'swimming_attendance') {
                    continue;
                }
                if (($item->status ?? 'active') !== 'active') {
                    continue;
                }

                $itemDate = $item->posted_at ?? $item->effective_date ?? $item->created_at ?? $inv->created_at;
                $voteheadName = $item->votehead->name ?? 'Fee';
                $itemAmount = (float) ($item->amount ?? 0);
                $discountAmount = (float) ($item->discount_amount ?? 0);
                $dateStr = Carbon::parse($itemDate)->format('Y-m-d');

                if ($itemAmount > 0) {
                    $transactions[] = [
                        'id' => 1_000_000 + ((int) $inv->id * 100) + (++$seq),
                        'entity_type' => 'invoice_item',
                        'entity_id' => (int) $item->id,
                        'date' => $dateStr,
                        'type' => 'Invoice Item',
                        'reference' => $inv->invoice_number ?? (string) $inv->id,
                        'description' => $voteheadName.' — '.$termLabel.' '.$year,
                        'votehead' => $voteheadName,
                        'debit' => $itemAmount,
                        'credit' => 0,
                        'balance' => 0,
                    ];
                } elseif ($itemAmount < 0) {
                    $transactions[] = [
                        'id' => 1_000_000 + ((int) $inv->id * 100) + (++$seq),
                        'entity_type' => 'invoice_item',
                        'entity_id' => (int) $item->id,
                        'date' => $dateStr,
                        'type' => 'Balance Brought Forward',
                        'reference' => $inv->invoice_number ?? (string) $inv->id,
                        'description' => 'Overpayment — '.$voteheadName,
                        'votehead' => $voteheadName,
                        'debit' => 0,
                        'credit' => abs($itemAmount),
                        'balance' => 0,
                    ];
                }

                if ($discountAmount > 0) {
                    $transactions[] = [
                        'id' => 1_000_000 + ((int) $inv->id * 100) + (++$seq),
                        'entity_type' => 'discount',
                        'entity_id' => (int) $item->id,
                        'date' => $dateStr,
                        'type' => 'Discount',
                        'reference' => $inv->invoice_number ?? (string) $inv->id,
                        'description' => 'Discount — '.$voteheadName,
                        'votehead' => $voteheadName,
                        'debit' => 0,
                        'credit' => $discountAmount,
                        'balance' => 0,
                    ];
                }
            }

            if ((float) ($inv->discount_amount ?? 0) > 0) {
                $transactions[] = [
                    'id' => 1_000_000 + ((int) $inv->id * 100) + (++$seq),
                    'entity_type' => 'invoice',
                    'entity_id' => (int) $inv->id,
                    'date' => $inv->created_at->format('Y-m-d'),
                    'type' => 'Discount',
                    'reference' => $inv->invoice_number ?? (string) $inv->id,
                    'description' => 'Invoice discount — '.($inv->invoice_number ?? $inv->id),
                    'votehead' => 'All Voteheads',
                    'debit' => 0,
                    'credit' => (float) $inv->discount_amount,
                    'balance' => 0,
                ];
            }
        }

        $studentInvoiceIds = $invoices->pluck('id')->flip();

        foreach ($payments as $pay) {
            $method = $pay->paymentMethod?->name ?? 'Payment';
            $ref = $pay->transaction_code ?? $pay->receipt_number ?? (string) $pay->id;
            $dateStr = Carbon::parse($pay->payment_date)->format('Y-m-d');

            if ($pay->allocations->isEmpty()) {
                $transactions[] = [
                    'id' => 2_000_000 + (int) $pay->id,
                    'entity_type' => 'payment',
                    'entity_id' => (int) $pay->id,
                    'date' => $dateStr,
                    'type' => 'Payment',
                    'reference' => $ref,
                    'description' => $method.' (Unallocated)',
                    'votehead' => 'Unallocated',
                    'debit' => 0,
                    'credit' => (float) $pay->amount,
                    'balance' => 0,
                ];

                continue;
            }

            foreach ($pay->allocations as $allocation) {
                $item = $allocation->invoiceItem;
                if (! $item || ! $item->invoice_id || ! $studentInvoiceIds->has($item->invoice_id)) {
                    continue;
                }
                if (($item->source ?? null) === 'swimming_attendance') {
                    continue;
                }
                $voteheadName = $item->votehead->name ?? 'Fee';
                $transactions[] = [
                    'id' => 2_000_000 + ((int) $pay->id * 100) + (++$seq),
                    'entity_type' => 'payment_allocation',
                    'entity_id' => (int) $allocation->id,
                    'date' => $dateStr,
                    'type' => 'Payment',
                    'reference' => $ref,
                    'description' => $method.' — '.$voteheadName,
                    'votehead' => $voteheadName,
                    'debit' => 0,
                    'credit' => (float) ($allocation->amount ?? 0),
                    'balance' => 0,
                ];
            }
        }

        return $this->sortAndBalance($transactions);
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     * @return array<int, array<string, mixed>>
     */
    protected function sortAndBalance(array $transactions): array
    {
        usort($transactions, function ($a, $b) {
            $cmp = strcmp((string) $a['date'], (string) $b['date']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
        });

        $running = 0.0;
        foreach ($transactions as &$row) {
            $running += (float) ($row['credit'] ?? 0) - (float) ($row['debit'] ?? 0);
            $row['balance'] = round($running, 2);
        }
        unset($row);

        return $transactions;
    }

    protected function authorizeStudentAccess(Request $request, Student $student): void
    {
        $user = $request->user();
        if ($user && $user->hasTeacherLikeRole()) {
            $query = Student::where('id', $student->id)->where('archive', 0)->where('is_alumni', false);
            $user->applyTeacherStudentFilter($query);
            if (! $query->exists()) {
                abort(403, 'You do not have access to this student.');
            }
        }
        if ($user && $user->hasAnyRole(['Parent', 'Guardian'])) {
            if (! $user->canAccessStudent((int) $student->id)) {
                abort(403, 'You do not have access to this student.');
            }

            return;
        }

        if ($user && ! $user->hasAnyRole([
            'Super Admin', 'Admin', 'Secretary', 'Finance Officer', 'Accountant',
        ]) && ! $user->hasTeacherLikeRole()) {
            abort(403);
        }
    }
}

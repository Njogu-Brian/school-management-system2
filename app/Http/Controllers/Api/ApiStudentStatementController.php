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

        $invoices = Invoice::where('student_id', $student->id)
            ->where(function ($q) use ($year) {
                $q->where('year', $year)
                    ->orWhereHas('academicYear', fn ($q2) => $q2->where('year', $year));
            })
            ->whereNull('reversed_at')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'reversed');
            })
            ->orderBy('created_at')
            ->get();

        foreach ($invoices as $inv) {
            if (method_exists($inv, 'recalculate')) {
                $inv->recalculate();
            }
        }

        $invoiceIds = $invoices->pluck('id')->toArray();

        $paymentsQuery = Payment::with('paymentMethod')
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

        $transactions = [];

        foreach ($invoices as $inv) {
            $transactions[] = [
                'id' => 1_000_000 + (int) $inv->id,
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
                'date' => Carbon::parse($pay->payment_date)->format('Y-m-d'),
                'type' => 'payment',
                'reference' => $pay->transaction_code ?? $pay->receipt_number ?? (string) $pay->id,
                'description' => 'Payment — '.($pay->paymentMethod?->name ?? 'Payment'),
                'debit' => 0,
                'credit' => (float) $pay->amount,
                'balance' => 0,
            ];
        }

        usort($transactions, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        $running = 0.0;
        foreach ($transactions as &$row) {
            $running += $row['credit'] - $row['debit'];
            $row['balance'] = round($running, 2);
        }
        unset($row);

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
                'opening_balance' => 0,
                'total_invoiced' => round($totalInvoiced, 2),
                'total_paid' => round($totalPaid, 2),
                'closing_balance' => round($closing, 2),
                'transactions' => array_values($transactions),
            ],
        ]);
    }

    protected function authorizeStudentAccess(Request $request, Student $student): void
    {
        $user = $request->user();
        if ($user && $user->hasAnyRole(['Teacher', 'Senior Teacher', 'Supervisor'])) {
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
            'Teacher', 'Senior Teacher', 'Supervisor',
        ])) {
            abort(403);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Services\FinanceTermKpiService;
use App\Services\UnifiedTransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ApiFinanceSummaryController extends Controller
{
    public function show(Request $request)
    {
        $today = Carbon::today();
        $monthStart = Carbon::today()->startOfMonth();

        $paymentBase = Payment::query()->where(function ($q) {
            $q->whereNull('reversed')->orWhere('reversed', false);
        });

        $collectedToday = (float) (clone $paymentBase)
            ->whereDate('payment_date', $today->toDateString())
            ->sum('amount');

        $collectedMonth = (float) (clone $paymentBase)
            ->where('payment_date', '>=', $monthStart)
            ->sum('amount');

        $weekStart = Carbon::today()->startOfWeek();
        $collectedWeek = (float) (clone $paymentBase)
            ->where('payment_date', '>=', $weekStart)
            ->sum('amount');

        $currentTerm = \App\Models\Term::query()->where('is_current', true)->first();
        $collectedTerm = 0.0;
        if ($currentTerm?->opening_date) {
            $termEnd = $currentTerm->closing_date ?? $today;
            $collectedTerm = (float) (clone $paymentBase)
                ->whereBetween('payment_date', [
                    $currentTerm->opening_date->toDateString(),
                    $termEnd->toDateString(),
                ])
                ->sum('amount');
        }

        $invoiceBase = Invoice::query()->whereNull('reversed_at');
        // Active students only (Student global scope excludes archive + alumni).
        $activeInvoiceBase = (clone $invoiceBase)->whereHas('student');

        $totalInvoiced = (float) (clone $activeInvoiceBase)->sum('total');
        $totalPaid = (float) (clone $activeInvoiceBase)->sum('paid_amount');

        // Match web portal + admin dashboard: current-term, due, unpaid/partial.
        $termKpis = app(FinanceTermKpiService::class)->forCurrentTerm();
        $outstandingBalance = (float) ($termKpis['fees_outstanding'] ?? 0);

        // All-time invoice balances — diagnostics (all includes archived/alumni).
        $outstandingBalanceAll = (float) (clone $invoiceBase)->sum('balance');
        $outstandingBalanceActive = (float) (clone $activeInvoiceBase)->sum('balance');

        $pendingInvoices = (clone $activeInvoiceBase)->where('balance', '>', 0)->count();

        $overdueInvoices = (clone $activeInvoiceBase)
            ->where('balance', '>', 0)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today->toDateString())
            ->count();

        $studentsInArrears = (clone $activeInvoiceBase)
            ->where('balance', '>', 0)
            ->distinct('student_id')
            ->count('student_id');

        $pendingReconciliation = app(UnifiedTransactionService::class)
            ->getUnifiedTransactionsQuery(['view' => 'unassigned'])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'collected_today' => round($collectedToday, 2),
                'collected_this_week' => round($collectedWeek, 2),
                'collected_this_month' => round($collectedMonth, 2),
                'collected_this_term' => round($collectedTerm, 2),
                'total_invoiced' => round($totalInvoiced, 2),
                'total_paid' => round($totalPaid, 2),
                'outstanding_balance' => round($outstandingBalance, 2),
                'outstanding_balance_active' => round($outstandingBalanceActive, 2),
                'outstanding_balance_all' => round($outstandingBalanceAll, 2),
                'finance_scope' => $termKpis['finance_scope'] ?? 'term',
                'term_id' => $termKpis['term_id'] ?? null,
                'term_name' => $termKpis['term_name'] ?? null,
                'pending_invoices' => $pendingInvoices,
                'overdue_invoices' => $overdueInvoices,
                'students_in_arrears' => $studentsInArrears,
                'pending_reconciliation' => $pendingReconciliation,
                'active_students' => Student::query()->where('archive', false)->where('is_alumni', false)->count(),
                'as_of' => now()->toIso8601String(),
            ],
        ]);
    }
}

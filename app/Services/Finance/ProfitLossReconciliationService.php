<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseLine;
use App\Models\ExpenseStatementLine;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Term;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Operational P&L reconciliation: compares recorded books against M-Pesa statement
 * evidence and fee subledger for catch-up accounting (missed expenses, mobile loan
 * interest, bad-debt transfers, term fee debtors).
 */
class ProfitLossReconciliationService
{
    /**
     * paybill => [category label, vendor label]
     *
     * @var array<string, array{0:string,1:string}>
     */
    public const MOBILE_LOAN_PAYBILLS = [
        '7787614' => ['Mobile Loan', 'Signalwave Ltd'],
        '589036' => ['Mobile Loan', 'Tingg / Cellulant'],
        '597686' => ['Mobile Loan', 'Tingg / Cellulant'],
        '4135035' => ['Mobile Loan', 'HFM Investments Ltd'],
        '998608' => ['Mobile Loan', 'Branch Microfinance'],
        '851900' => ['Mobile Loan', 'Tala'],
        '979988' => ['Mobile Loan', 'Zenka Digital'],
        '4133807' => ['Mobile Loan', 'Zenka Digital'],
    ];

    /** Narration keywords that indicate a mobile-loan disbursement (money in). */
    private const LOAN_IN_KEYWORDS = [
        'loan', 'fuliza', 'm-shwari', 'mshwari', 'overdraft', 'tala', 'zenka', 'branch',
        'signalwave', 'hfm investment', 'tingg', 'cellulant', 'credit from', 'advance',
        'timiza', 'okash', 'azura', 'mycredit', 'premier credit', 'kcb mpesa', 'aventus',
    ];

    public function analyze(int $year, ?int $termNumber = 3): array
    {
        $dateFrom = Carbon::create($year, 1, 1)->startOfDay();
        $dateTo = Carbon::create($year, 12, 31)->endOfDay();

        $mobileLoans = $this->mobileLoanAnalysis($dateFrom, $dateTo);
        $badDebtTransfers = $this->badDebtTransfers($dateFrom, $dateTo);
        $termDebtors = $this->termDebtors($year, $termNumber);
        $recordedExpenses = $this->recordedExpenses($dateFrom, $dateTo);
        $statementGaps = $this->statementGaps($dateFrom, $dateTo);
        $revenue = $this->revenueSummary($dateFrom, $dateTo);

        $loanAdjustment = round($mobileLoans['totals']['true_cost'] - $mobileLoans['totals']['recorded'], 2);
        $badDebtTotal = round($badDebtTransfers['total'], 2);
        $unbookedConfirmed = round($statementGaps['confirmed_unbooked_total'], 2);

        $adjustedExpenses = round(
            $recordedExpenses['total']
            + $loanAdjustment
            + $badDebtTotal
            + $unbookedConfirmed,
            2
        );

        $cashNet = round($revenue['fees_collected'] - $adjustedExpenses, 2);
        $accrualNet = round(
            ($revenue['fees_invoiced'] + $termDebtors['total_balance'])
            - $adjustedExpenses,
            2
        );

        return [
            'year' => $year,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'revenue' => $revenue,
            'term_debtors' => $termDebtors,
            'recorded_expenses' => $recordedExpenses,
            'mobile_loans' => $mobileLoans,
            'bad_debt_transfers' => $badDebtTransfers,
            'statement_gaps' => $statementGaps,
            'adjustments' => [
                'mobile_loan_cost_gap' => $loanAdjustment,
                'bad_debt_transfers' => $badDebtTotal,
                'confirmed_unbooked_statements' => $unbookedConfirmed,
                'total_expense_adjustments' => round($loanAdjustment + $badDebtTotal + $unbookedConfirmed, 2),
            ],
            'summary' => [
                'recorded_expenses' => $recordedExpenses['total'],
                'adjusted_expenses' => $adjustedExpenses,
                'fees_collected' => $revenue['fees_collected'],
                'cash_basis_net' => $cashNet,
                'fees_invoiced_plus_debtors' => round($revenue['fees_invoiced'] + $termDebtors['total_balance'], 2),
                'accrual_style_net' => $accrualNet,
            ],
        ];
    }

    /**
     * @return array{fees_collected: float, fees_invoiced: float, total_ar: float}
     */
    protected function revenueSummary(Carbon $from, Carbon $to): array
    {
        $feesCollected = (float) Payment::query()
            ->where(function ($q) {
                $q->whereNull('reversed')->orWhere('reversed', false);
            })
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');

        $invoiceQuery = Invoice::query()
            ->whereNull('reversed_at')
            ->whereBetween('created_at', [$from, $to]);

        return [
            'fees_collected' => round($feesCollected, 2),
            'fees_invoiced' => round((float) (clone $invoiceQuery)->sum('total'), 2),
            'total_ar' => round((float) Invoice::query()
                ->whereNull('reversed_at')
                ->where('year', $from->year)
                ->sum('balance'), 2),
        ];
    }

    /**
     * Unpaid invoice balances for a specific term in the year (debtors).
     *
     * @return array{term: ?string, student_count: int, invoice_count: int, total_balance: float, students: Collection}
     */
    protected function termDebtors(int $year, ?int $termNumber): array
    {
        $term = $this->resolveTerm($year, $termNumber);
        if (! $term) {
            return [
                'term' => null,
                'term_number' => $termNumber,
                'student_count' => 0,
                'invoice_count' => 0,
                'total_balance' => 0.0,
                'students' => collect(),
            ];
        }

        $invoices = Invoice::query()
            ->with(['student.classroom'])
            ->where('term_id', $term->id)
            ->whereNull('reversed_at')
            ->where('balance', '>', 0.01)
            ->orderByDesc('balance')
            ->get();

        $students = $invoices->map(fn (Invoice $inv) => [
            'student_id' => $inv->student_id,
            'name' => $inv->student?->full_name,
            'admission' => $inv->student?->admission_number,
            'class' => $inv->student?->classroom?->name,
            'invoiced' => round((float) $inv->total, 2),
            'paid' => round((float) $inv->paid_amount, 2),
            'balance' => round((float) $inv->balance, 2),
        ]);

        return [
            'term' => $term->name,
            'term_number' => $termNumber,
            'student_count' => $students->count(),
            'invoice_count' => $invoices->count(),
            'total_balance' => round((float) $invoices->sum('balance'), 2),
            'students' => $students,
        ];
    }

    /**
     * @return array{total: float, by_category: Collection, line_count: int}
     */
    protected function recordedExpenses(Carbon $from, Carbon $to): array
    {
        $lines = ExpenseLine::query()
            ->join('expenses', 'expenses.id', '=', 'expense_lines.expense_id')
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expense_lines.category_id')
            ->whereIn('expenses.status', [
                Expense::STATUS_SUBMITTED,
                Expense::STATUS_APPROVED,
                Expense::STATUS_PAID,
            ])
            ->whereBetween('expenses.expense_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('expense_categories.name as category, SUM(expense_lines.line_total) as total, COUNT(*) as line_count')
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get();

        return [
            'total' => round((float) $lines->sum('total'), 2),
            'line_count' => (int) $lines->sum('line_count'),
            'by_category' => $lines->map(fn ($r) => [
                'category' => $r->category ?? 'Uncategorised',
                'total' => round((float) $r->total, 2),
                'line_count' => (int) $r->line_count,
            ]),
        ];
    }

    /**
     * Compare M-Pesa money-in (disbursements) vs money-out (repayments) per loan provider.
     *
     * True loan cost = repayments − disbursements (interest + fees).
     * Gap vs books = true_cost − recorded mobile-loan expenses.
     *
     * @return array{providers: Collection, totals: array<string, float>, disbursement_lines: int, repayment_lines: int}
     */
    protected function mobileLoanAnalysis(Carbon $from, Carbon $to): array
    {
        $paybills = array_keys(self::MOBILE_LOAN_PAYBILLS);
        $mobileCategoryId = ExpenseCategory::where('name', 'Mobile Loan')->value('id');

        $statementLines = ExpenseStatementLine::query()
            ->whereBetween('completed_at', [$from, $to])
            ->get();

        $loanLines = $statementLines->filter(function ($line) use ($paybills, $mobileCategoryId) {
            if ($mobileCategoryId && (int) $line->expense_category_id === (int) $mobileCategoryId) {
                return true;
            }
            if ($line->paybill_number && in_array($line->paybill_number, $paybills, true)) {
                return true;
            }
            $narration = strtolower((string) $line->narration);

            return $this->narrationMatchesLoanKeyword($narration);
        });

        $disbursementLines = ExpenseStatementLine::query()
            ->where('direction', 'in')
            ->whereBetween('completed_at', [$from, $to])
            ->where(function ($q) {
                foreach (self::LOAN_IN_KEYWORDS as $kw) {
                    $q->orWhere('narration', 'like', '%' . $kw . '%');
                }
            })
            ->get();

        $recordedByVendor = ExpenseLine::query()
            ->join('expenses', 'expenses.id', '=', 'expense_lines.expense_id')
            ->leftJoin('vendors', 'vendors.id', '=', 'expenses.vendor_id')
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expense_lines.category_id')
            ->whereIn('expenses.status', [
                Expense::STATUS_SUBMITTED,
                Expense::STATUS_APPROVED,
                Expense::STATUS_PAID,
            ])
            ->whereBetween('expenses.expense_date', [$from->toDateString(), $to->toDateString()])
            ->where(function ($q) use ($mobileCategoryId) {
                $q->where('expense_categories.name', 'Mobile Loan');
                if ($mobileCategoryId) {
                    $q->orWhere('expense_lines.category_id', $mobileCategoryId);
                }
                $q->orWhere('expense_categories.code', 'like', 'LOAN%');
            })
            ->selectRaw("COALESCE(vendors.name, expenses.notes, 'Mobile Loan') as vendor, SUM(expense_lines.line_total) as total")
            ->groupByRaw("COALESCE(vendors.name, expenses.notes, 'Mobile Loan')")
            ->pluck('total', 'vendor');

        $providers = collect(self::MOBILE_LOAN_PAYBILLS)->map(function (array $meta, string $paybill) use ($statementLines, $recordedByVendor) {
            [$catLabel, $vendor] = $meta;

            $repayments = (float) $statementLines
                ->where('direction', 'out')
                ->where('paybill_number', $paybill)
                ->where('is_transaction_fee', false)
                ->sum('withdrawn_amount');

            $disbursements = (float) $statementLines
                ->where('direction', 'in')
                ->where('paybill_number', $paybill)
                ->sum('paid_in_amount');

            $trueCost = round(max(0, $repayments - $disbursements), 2);
            $recorded = round((float) ($recordedByVendor[$vendor] ?? $this->fuzzyVendorTotal($recordedByVendor, $vendor)), 2);

            return [
                'paybill' => $paybill,
                'vendor' => $vendor,
                'disbursements' => round($disbursements, 2),
                'repayments' => round($repayments, 2),
                'true_cost' => $trueCost,
                'recorded_expenses' => $recorded,
                'gap' => round($trueCost - $recorded, 2),
                'repayment_lines' => $statementLines
                    ->where('direction', 'out')
                    ->where('paybill_number', $paybill)
                    ->where('is_transaction_fee', false)
                    ->count(),
            ];
        })->values();

        $totalDisbursements = round((float) $disbursementLines->sum('paid_in_amount'), 2);
        $totalRepayments = round((float) $loanLines
            ->where('direction', 'out')
            ->where('is_transaction_fee', false)
            ->sum('withdrawn_amount'), 2);

        $trueCostTotal = round(max(0, $totalRepayments - $totalDisbursements), 2);
        $recordedTotal = round((float) $recordedByVendor->sum(), 2);

        return [
            'providers' => $providers,
            'disbursement_lines' => $disbursementLines->count(),
            'repayment_lines' => $loanLines
                ->where('direction', 'out')
                ->where('is_transaction_fee', false)
                ->count(),
            'unattributed_disbursements' => $totalDisbursements,
            'totals' => [
                'disbursements' => $totalDisbursements,
                'repayments' => $totalRepayments,
                'true_cost' => $trueCostTotal,
                'recorded' => $recordedTotal,
                'gap' => round($trueCostTotal - $recordedTotal, 2),
            ],
        ];
    }

    /**
     * Send-money transfers treated as bad debt (personal / not confirmed as business).
     *
     * @return array{total: float, line_count: int, by_recipient: Collection}
     */
    protected function badDebtTransfers(Carbon $from, Carbon $to): array
    {
        $lines = ExpenseStatementLine::query()
            ->where('transaction_type', ExpenseStatementLine::TYPE_SEND_MONEY)
            ->where('direction', 'out')
            ->where('is_transaction_fee', false)
            ->whereNull('expense_id')
            ->whereBetween('completed_at', [$from, $to])
            ->whereIn('review_status', [
                ExpenseStatementLine::REVIEW_PERSONAL,
                ExpenseStatementLine::REVIEW_PENDING,
            ])
            ->get();

        $byRecipient = $lines->groupBy(fn ($l) => $l->payeeName() ?: 'Unknown')
            ->map(fn (Collection $group, string $name) => [
                'recipient' => $name,
                'lines' => $group->count(),
                'total' => round((float) $group->sum('withdrawn_amount'), 2),
            ])
            ->sortByDesc('total')
            ->values();

        return [
            'total' => round((float) $lines->sum('withdrawn_amount'), 2),
            'line_count' => $lines->count(),
            'by_recipient' => $byRecipient,
        ];
    }

    /**
     * Statement lines confirmed as business but not yet booked into expense vouchers.
     *
     * @return array{confirmed_unbooked_total: float, confirmed_unbooked_count: int, pending_business_count: int}
     */
    protected function statementGaps(Carbon $from, Carbon $to): array
    {
        $confirmedUnbooked = ExpenseStatementLine::query()
            ->where('direction', 'out')
            ->where('review_status', ExpenseStatementLine::REVIEW_CONFIRMED)
            ->whereNull('expense_id')
            ->where('is_transaction_fee', false)
            ->whereBetween('completed_at', [$from, $to]);

        $pending = ExpenseStatementLine::query()
            ->where('direction', 'out')
            ->where('review_status', ExpenseStatementLine::REVIEW_PENDING)
            ->whereNull('expense_id')
            ->where('is_transaction_fee', false)
            ->whereBetween('completed_at', [$from, $to]);

        return [
            'confirmed_unbooked_total' => round((float) (clone $confirmedUnbooked)->sum('withdrawn_amount'), 2),
            'confirmed_unbooked_count' => (clone $confirmedUnbooked)->count(),
            'pending_count' => (clone $pending)->count(),
            'pending_total' => round((float) (clone $pending)->sum('withdrawn_amount'), 2),
        ];
    }

  /**
     * @param  Collection<string, mixed>  $recordedByVendor
     */
    /** Narration keywords for loan repayments / disbursements beyond paybill numbers. */
    private const LOAN_NARRATION_KEYWORDS = [
        'loan', 'fuliza', 'm-shwari', 'mshwari', 'overdraft', 'tala', 'zenka', 'branch',
        'signalwave', 'hfm', 'tingg', 'cellulant', 'timiza', 'okash', 'azura', 'mycredit',
        'premier credit', 'kcb mpesa', 'aventus', 'repayment', 'loan recovery',
    ];

    protected function narrationMatchesLoanKeyword(string $narration): bool
    {
        foreach (self::LOAN_NARRATION_KEYWORDS as $kw) {
            if (str_contains($narration, $kw)) {
                return true;
            }
        }

        return false;
    }

    protected function fuzzyVendorTotal(Collection $recordedByVendor, string $vendor): float
    {
        $needle = strtolower(explode(' ', $vendor)[0]);
        foreach ($recordedByVendor as $name => $total) {
            if (str_contains(strtolower((string) $name), $needle)) {
                return (float) $total;
            }
        }

        return 0.0;
    }

    protected function resolveTerm(int $year, ?int $termNumber): ?Term
    {
        if (! $termNumber) {
            return null;
        }

        return Term::query()
            ->whereHas('academicYear', fn ($q) => $q->where('year', $year))
            ->where(function ($q) use ($termNumber) {
                $q->where('name', 'like', '%Term ' . $termNumber . '%')
                    ->orWhere('name', 'like', '% ' . $termNumber);
            })
            ->first();
    }
}

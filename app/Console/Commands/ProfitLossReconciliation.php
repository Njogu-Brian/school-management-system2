<?php

namespace App\Console\Commands;

use App\Services\Finance\ProfitLossReconciliationService;
use Illuminate\Console\Command;

class ProfitLossReconciliation extends Command
{
    protected $signature = 'finance:profit-loss-reconciliation
        {year? : Calendar year to analyse (default: current year)}
        {--term=3 : Term number for debtor analysis (default: 3)}
        {--json : Output raw JSON instead of tables}';

    protected $description = 'Reconcile profit/loss: recorded expenses vs M-Pesa mobile-loan cost, bad-debt transfers, and term fee debtors';

    public function handle(ProfitLossReconciliationService $service): int
    {
        $year = (int) ($this->argument('year') ?: now()->year);
        $term = (int) $this->option('term');

        $report = $service->analyze($year, $term);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info("Profit / Loss Reconciliation — {$year}");
        $this->line(str_repeat('─', 60));

        $this->table(['Metric', 'Amount (KES)'], [
            ['Fees collected', number_format($report['revenue']['fees_collected'], 2)],
            ['Fees invoiced', number_format($report['revenue']['fees_invoiced'], 2)],
            ['Total AR (year)', number_format($report['revenue']['total_ar'], 2)],
            ['Term ' . $term . ' debtors (' . $report['term_debtors']['student_count'] . ' students)', number_format($report['term_debtors']['total_balance'], 2)],
            ['Recorded expenses', number_format($report['recorded_expenses']['total'], 2)],
        ]);

        $this->newLine();
        $this->comment('Mobile loans (M-Pesa: repayments − disbursements = true cost)');
        $ml = $report['mobile_loans'];
        $providerRows = $ml['providers']->map(fn ($p) => [
            $p['vendor'],
            $p['paybill'],
            number_format($p['disbursements'], 2),
            number_format($p['repayments'], 2),
            number_format($p['true_cost'], 2),
            number_format($p['recorded_expenses'], 2),
            number_format($p['gap'], 2),
        ])->all();

        if ($providerRows) {
            $this->table(
                ['Provider', 'Paybill', 'Received', 'Repaid', 'True cost', 'Recorded', 'Gap'],
                $providerRows
            );
        } else {
            $this->warn('No mobile-loan paybill lines in statement imports for this year. Import M-Pesa PDFs via Finance → Statement Analyzer.');
        }

        $this->line(sprintf(
            'Totals: received KES %s | repaid KES %s | true cost KES %s | recorded KES %s | gap KES %s',
            number_format($ml['totals']['disbursements'], 2),
            number_format($ml['totals']['repayments'], 2),
            number_format($ml['totals']['true_cost'], 2),
            number_format($ml['totals']['recorded'], 2),
            number_format($ml['totals']['gap'], 2),
        ));

        $this->newLine();
        $this->comment('Bad debt — send-money transfers (personal / unconfirmed)');
        $bd = $report['bad_debt_transfers'];
        $this->line(sprintf('%d lines, KES %s total', $bd['line_count'], number_format($bd['total'], 2)));

        $this->newLine();
        $this->comment('Statement gaps (confirmed but not yet booked)');
        $sg = $report['statement_gaps'];
        $this->line(sprintf(
            '%d confirmed-unbooked lines (KES %s) | %d still pending review (KES %s)',
            $sg['confirmed_unbooked_count'],
            number_format($sg['confirmed_unbooked_total'], 2),
            $sg['pending_count'],
            number_format($sg['pending_total'], 2),
        ));

        $this->newLine();
        $this->info('Adjusted summary');
        $s = $report['summary'];
        $this->table(['', 'KES'], [
            ['Recorded expenses', number_format($s['recorded_expenses'], 2)],
            ['+ Expense adjustments', number_format($report['adjustments']['total_expense_adjustments'], 2)],
            ['= Adjusted expenses', number_format($s['adjusted_expenses'], 2)],
            ['Fees collected (cash basis)', number_format($s['fees_collected'], 2)],
            ['Cash-basis net', number_format($s['cash_basis_net'], 2)],
            ['Invoiced + term debtors (accrual-style)', number_format($s['fees_invoiced_plus_debtors'], 2)],
            ['Accrual-style net', number_format($s['accrual_style_net'], 2)],
        ]);

        $this->newLine();
        $this->comment('Next steps: import M-Pesa statements → run finance:categorize-fuliza-payees → book gaps → approve expenses.');

        return self::SUCCESS;
    }
}

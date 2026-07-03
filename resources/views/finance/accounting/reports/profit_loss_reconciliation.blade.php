@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', [
    'title' => 'Profit / Loss Reconciliation',
    'icon' => 'bi bi-clipboard-data',
    'subtitle' => 'Catch-up view: recorded books vs M-Pesa evidence & fee debtors',
  ])

  <form class="row g-2 mb-3" method="GET">
    <div class="col-md-2">
      <label class="form-label small">Year</label>
      <select name="year" class="finance-form-select">
        @for($y = (int) now()->year; $y >= (int) now()->year - 4; $y--)
          <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
        @endfor
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small">Debtor term</label>
      <select name="term" class="finance-form-select">
        @foreach([1, 2, 3] as $t)
          <option value="{{ $t }}" @selected($term == $t)>Term {{ $t }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <button class="btn btn-finance btn-finance-primary w-100">Run</button>
    </div>
  </form>

  <div class="alert alert-info small">
    This is an <strong>operational reconciliation</strong> for catch-up accounting — not a statutory tax return.
    Mobile loan cost = M-Pesa repayments minus disbursements (money in). Send-money transfers marked personal/pending are treated as bad debt.
  </div>

  {{-- Summary --}}
  <div class="finance-card mb-3"><div class="finance-card-body">
    <h6 class="mb-3">Summary — {{ $year }}</h6>
    <div class="row text-center g-3">
      <div class="col-md-3">
        <div class="text-muted small">Fees collected</div>
        <div class="fs-5">KES {{ number_format($report['revenue']['fees_collected'], 2) }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Term {{ $term }} debtors</div>
        <div class="fs-5">KES {{ number_format($report['term_debtors']['total_balance'], 2) }}</div>
        <div class="text-muted small">{{ $report['term_debtors']['student_count'] }} students</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Recorded expenses</div>
        <div class="fs-5">KES {{ number_format($report['summary']['recorded_expenses'], 2) }}</div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Adjusted net (cash basis)</div>
        <div class="fs-5 {{ $report['summary']['cash_basis_net'] >= 0 ? 'text-success' : 'text-danger' }}">
          KES {{ number_format($report['summary']['cash_basis_net'], 2) }}
        </div>
      </div>
    </div>
  </div></div>

  {{-- Adjustments --}}
  <div class="finance-card mb-3"><div class="finance-card-body">
    <h6 class="mb-3">Expense adjustments</h6>
    <table class="finance-table table-sm">
      <tbody>
        <tr>
          <td>Mobile loan cost gap (true cost − recorded)</td>
          <td class="text-end">KES {{ number_format($report['adjustments']['mobile_loan_cost_gap'], 2) }}</td>
        </tr>
        <tr>
          <td>Bad debt — unbooked send-money transfers</td>
          <td class="text-end">KES {{ number_format($report['adjustments']['bad_debt_transfers'], 2) }}</td>
        </tr>
        <tr>
          <td>Confirmed statement lines not yet booked</td>
          <td class="text-end">KES {{ number_format($report['adjustments']['confirmed_unbooked_statements'], 2) }}</td>
        </tr>
        <tr class="fw-bold">
          <td>Total adjustments</td>
          <td class="text-end">KES {{ number_format($report['adjustments']['total_expense_adjustments'], 2) }}</td>
        </tr>
        <tr class="fw-bold border-top">
          <td>Adjusted expenses</td>
          <td class="text-end">KES {{ number_format($report['summary']['adjusted_expenses'], 2) }}</td>
        </tr>
      </tbody>
    </table>
  </div></div>

  {{-- Mobile loans --}}
  <div class="finance-card mb-3"><div class="finance-card-body">
    <h6 class="mb-2">Mobile loans (M-Pesa statement)</h6>
    <p class="text-muted small mb-3">
      True cost = repayments to loan paybills − loan disbursements (money in).
      Gap shows how much more expense to book vs what is already in the system.
    </p>
    @if($report['mobile_loans']['providers']->isNotEmpty())
      <div class="finance-table-wrapper">
        <table class="finance-table table-sm">
          <thead>
            <tr>
              <th>Provider</th><th>Paybill</th>
              <th class="text-end">Received</th><th class="text-end">Repaid</th>
              <th class="text-end">True cost</th><th class="text-end">Recorded</th><th class="text-end">Gap</th>
            </tr>
          </thead>
          <tbody>
            @foreach($report['mobile_loans']['providers'] as $p)
              <tr>
                <td>{{ $p['vendor'] }}</td>
                <td>{{ $p['paybill'] }}</td>
                <td class="text-end">{{ number_format($p['disbursements'], 2) }}</td>
                <td class="text-end">{{ number_format($p['repayments'], 2) }}</td>
                <td class="text-end">{{ number_format($p['true_cost'], 2) }}</td>
                <td class="text-end">{{ number_format($p['recorded_expenses'], 2) }}</td>
                <td class="text-end {{ $p['gap'] > 0 ? 'text-danger' : '' }}">{{ number_format($p['gap'], 2) }}</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr class="fw-bold">
              <td colspan="2">Totals</td>
              <td class="text-end">{{ number_format($report['mobile_loans']['totals']['disbursements'], 2) }}</td>
              <td class="text-end">{{ number_format($report['mobile_loans']['totals']['repayments'], 2) }}</td>
              <td class="text-end">{{ number_format($report['mobile_loans']['totals']['true_cost'], 2) }}</td>
              <td class="text-end">{{ number_format($report['mobile_loans']['totals']['recorded'], 2) }}</td>
              <td class="text-end">{{ number_format($report['mobile_loans']['totals']['gap'], 2) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    @else
      <p class="text-muted mb-0">No mobile-loan lines found. Import M-Pesa PDFs under <a href="{{ route('finance.expense-statements.index') }}">Statement Analyzer</a>.</p>
    @endif
  </div></div>

  {{-- Bad debt transfers --}}
  <div class="finance-card mb-3"><div class="finance-card-body">
    <h6 class="mb-2">Bad debt — send-money transfers</h6>
    <p class="text-muted small">{{ $report['bad_debt_transfers']['line_count'] }} lines · KES {{ number_format($report['bad_debt_transfers']['total'], 2) }}</p>
    @if($report['bad_debt_transfers']['by_recipient']->isNotEmpty())
      <div class="finance-table-wrapper" style="max-height: 280px; overflow-y: auto;">
        <table class="finance-table table-sm">
          <thead><tr><th>Recipient</th><th class="text-end">Lines</th><th class="text-end">Amount</th></tr></thead>
          <tbody>
            @foreach($report['bad_debt_transfers']['by_recipient']->take(30) as $row)
              <tr>
                <td>{{ $row['recipient'] }}</td>
                <td class="text-end">{{ $row['lines'] }}</td>
                <td class="text-end">{{ number_format($row['total'], 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div></div>

  {{-- Term debtors --}}
  <div class="finance-card mb-3"><div class="finance-card-body">
    <h6 class="mb-2">Term {{ $term }} fee debtors @if($report['term_debtors']['term'])<span class="text-muted fw-normal">({{ $report['term_debtors']['term'] }})</span>@endif</h6>
    @if($report['term_debtors']['students']->isNotEmpty())
      <div class="finance-table-wrapper" style="max-height: 320px; overflow-y: auto;">
        <table class="finance-table table-sm">
          <thead>
            <tr><th>Student</th><th>Class</th><th class="text-end">Invoiced</th><th class="text-end">Paid</th><th class="text-end">Balance</th></tr>
          </thead>
          <tbody>
            @foreach($report['term_debtors']['students'] as $s)
              <tr>
                <td>{{ $s['name'] }} <span class="text-muted small">{{ $s['admission'] }}</span></td>
                <td>{{ $s['class'] ?? '—' }}</td>
                <td class="text-end">{{ number_format($s['invoiced'], 2) }}</td>
                <td class="text-end">{{ number_format($s['paid'], 2) }}</td>
                <td class="text-end fw-semibold">{{ number_format($s['balance'], 2) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <p class="text-muted mb-0">No unpaid Term {{ $term }} invoices for {{ $year }}.</p>
    @endif
  </div></div>

  <div class="finance-card"><div class="finance-card-body small text-muted">
    <strong>Workflow:</strong>
    1) Import M-Pesa statements →
    2) <code>php artisan finance:categorize-fuliza-payees --apply</code> →
    3) Review &amp; approve in Statement Analyzer →
    4) Book missing expenses →
  5) Re-run this report.
  </div></div>
</div></div>
@endsection

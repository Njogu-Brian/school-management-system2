@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Profit & Loss', 'icon' => 'bi bi-graph-up'])

  <form class="row g-2 mb-3" method="GET">
    <div class="col-md-3"><input type="date" name="date_from" class="finance-form-control" value="{{ $dateFrom }}"></div>
    <div class="col-md-3"><input type="date" name="date_to" class="finance-form-control" value="{{ $dateTo }}"></div>
    <div class="col-md-2"><button class="btn btn-primary">Filter</button></div>
  </form>

  <div class="finance-card mb-3"><div class="finance-card-body">
    <div class="row text-center">
      <div class="col-md-4"><div class="text-muted small">Revenue</div><div class="fs-4">KES {{ number_format($report['total_revenue'], 2) }}</div></div>
      <div class="col-md-4"><div class="text-muted small">Expenses</div><div class="fs-4">KES {{ number_format($report['total_expenses'], 2) }}</div></div>
      <div class="col-md-4"><div class="text-muted small">Net Profit</div><div class="fs-4 {{ $report['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">KES {{ number_format($report['net_profit'], 2) }}</div></div>
    </div>
  </div></div>

  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Code</th><th>Account</th><th>Type</th><th class="text-end">Amount</th></tr></thead>
      <tbody>
        @foreach($report['lines'] as $line)
          <tr>
            <td>{{ $line['code'] }}</td>
            <td>{{ $line['name'] }}</td>
            <td>{{ ucfirst($line['account_type']) }}</td>
            <td class="text-end">{{ number_format(abs($line['balance']), 2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div></div>
@endsection

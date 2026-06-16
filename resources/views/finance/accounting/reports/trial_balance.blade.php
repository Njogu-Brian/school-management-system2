@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Trial Balance', 'icon' => 'bi bi-list-columns'])

  <form class="row g-2 mb-3" method="GET">
    <div class="col-md-3"><input type="date" name="date_from" class="finance-form-control" value="{{ $dateFrom }}"></div>
    <div class="col-md-3"><input type="date" name="date_to" class="finance-form-control" value="{{ $dateTo }}"></div>
    <div class="col-md-2"><button class="btn btn-primary">Filter</button></div>
  </form>

  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Code</th><th>Account</th><th>Type</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
      <tbody>
        @foreach($rows as $row)
          <tr>
            <td>{{ $row['code'] }}</td>
            <td>{{ $row['name'] }}</td>
            <td>{{ ucfirst($row['account_type']) }}</td>
            <td class="text-end">{{ number_format($row['debit'], 2) }}</td>
            <td class="text-end">{{ number_format($row['credit'], 2) }}</td>
            <td class="text-end">{{ number_format($row['balance'], 2) }}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3" class="text-end">Totals</th>
          <th class="text-end">{{ number_format($totalDebit, 2) }}</th>
          <th class="text-end">{{ number_format($totalCredit, 2) }}</th>
          <th></th>
        </tr>
      </tfoot>
    </table>
  </div>
</div></div>
@endsection

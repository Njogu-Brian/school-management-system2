@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Balance Sheet', 'icon' => 'bi bi-bank'])

  <form class="row g-2 mb-3" method="GET">
    <div class="col-md-3"><input type="date" name="as_of" class="finance-form-control" value="{{ $asOf }}"></div>
    <div class="col-md-2"><button class="btn btn-primary">As of date</button></div>
  </form>

  <div class="row g-3">
    <div class="col-md-4">
      <div class="finance-card h-100"><div class="finance-card-body">
        <h6>Assets</h6>
        @foreach($report['assets'] as $row)
          <div class="d-flex justify-content-between"><span>{{ $row['code'] }} {{ $row['name'] }}</span><span>{{ number_format($row['balance'], 2) }}</span></div>
        @endforeach
        <hr><strong class="d-flex justify-content-between"><span>Total Assets</span><span>{{ number_format($report['total_assets'], 2) }}</span></strong>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="finance-card h-100"><div class="finance-card-body">
        <h6>Liabilities</h6>
        @foreach($report['liabilities'] as $row)
          <div class="d-flex justify-content-between"><span>{{ $row['code'] }} {{ $row['name'] }}</span><span>{{ number_format(-$row['balance'], 2) }}</span></div>
        @endforeach
        <hr><strong class="d-flex justify-content-between"><span>Total Liabilities</span><span>{{ number_format($report['total_liabilities'], 2) }}</span></strong>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="finance-card h-100"><div class="finance-card-body">
        <h6>Equity</h6>
        @foreach($report['equity'] as $row)
          <div class="d-flex justify-content-between"><span>{{ $row['code'] }} {{ $row['name'] }}</span><span>{{ number_format(-$row['balance'], 2) }}</span></div>
        @endforeach
        <div class="d-flex justify-content-between"><span>Current year profit</span><span>{{ number_format($report['current_year_profit'], 2) }}</span></div>
        <hr><strong class="d-flex justify-content-between"><span>Total Equity</span><span>{{ number_format($report['total_equity'], 2) }}</span></strong>
      </div></div>
    </div>
  </div>
</div></div>
@endsection

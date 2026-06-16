@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Fiscal Periods', 'icon' => 'bi bi-calendar-range', 'subtitle' => 'Define accounting years and close periods'])

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="finance-card mb-3"><div class="finance-card-body">
    <form method="POST" action="{{ route('finance.fiscal-periods.store') }}" class="row g-2">@csrf
      <div class="col-md-4"><input class="finance-form-control" name="name" placeholder="e.g. FY 2026" required></div>
      <div class="col-md-3"><input type="date" class="finance-form-control" name="start_date" required></div>
      <div class="col-md-3"><input type="date" class="finance-form-control" name="end_date" required></div>
      <div class="col-md-2"><button class="btn btn-primary w-100">Add Period</button></div>
    </form>
  </div></div>

  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Name</th><th>Start</th><th>End</th><th>Status</th><th>Closed</th><th></th></tr></thead>
      <tbody>
        @foreach($periods as $period)
          <tr>
            <td>{{ $period->name }}</td>
            <td>{{ $period->start_date->format('d M Y') }}</td>
            <td>{{ $period->end_date->format('d M Y') }}</td>
            <td>{{ ucfirst($period->status) }}</td>
            <td>{{ optional($period->closed_at)->format('d M Y') ?? '—' }}</td>
            <td>
              @if($period->isOpen())
                <form method="POST" action="{{ route('finance.fiscal-periods.close', $period) }}" class="d-inline">@csrf
                  <button class="btn btn-sm btn-outline-warning" onclick="return confirm('Close this fiscal period?')">Close period</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div></div>
@endsection

@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Budgets', 'icon' => 'bi bi-bar-chart'])

  <div class="finance-card mb-3"><div class="finance-card-body">
    <form method="POST" action="{{ route('finance.budgets.store') }}" class="row g-2">@csrf
      <div class="col-md-4">
        <select name="fiscal_period_id" class="finance-form-select" required>
          <option value="">Fiscal period</option>
          @foreach($periods as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-6"><input class="finance-form-control" name="name" placeholder="Budget name e.g. 2026 Operating Budget" required></div>
      <div class="col-md-2"><button class="btn btn-primary w-100">Create</button></div>
    </form>
  </div></div>

  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Name</th><th>Period</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @foreach($budgets as $budget)
          <tr>
            <td>{{ $budget->name }}</td>
            <td>{{ $budget->fiscalPeriod->name }}</td>
            <td>{{ ucfirst($budget->status) }}</td>
            <td><a href="{{ route('finance.budgets.show', $budget) }}">Manage</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  {{ $budgets->links() }}
</div></div>
@endsection

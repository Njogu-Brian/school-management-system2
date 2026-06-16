@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => $budget->name, 'icon' => 'bi bi-bar-chart', 'subtitle' => 'Budget vs actual for ' . $budget->fiscalPeriod->name])

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="finance-card mb-3"><div class="finance-card-body">
    <h6>Add / update budget line</h6>
    <form method="POST" action="{{ route('finance.budgets.lines.store', $budget) }}" class="row g-2">@csrf
      <div class="col-md-6">
        <select name="account_id" class="finance-form-select" required>
          <option value="">Expense account</option>
          @foreach($expenseAccounts as $account)<option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-4"><input type="number" step="0.01" min="0" name="budget_amount" class="finance-form-control" placeholder="Budget amount" required></div>
      <div class="col-md-2"><button class="btn btn-primary w-100">Save</button></div>
    </form>
  </div></div>

  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Account</th><th class="text-end">Budget</th><th class="text-end">Actual</th><th class="text-end">Variance</th><th class="text-end">% Used</th></tr></thead>
      <tbody>
        @forelse($comparison as $row)
          <tr>
            <td>{{ $row['account']->code }} — {{ $row['account']->name }}</td>
            <td class="text-end">{{ number_format($row['budget'], 2) }}</td>
            <td class="text-end">{{ number_format($row['actual'], 2) }}</td>
            <td class="text-end {{ $row['variance'] < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($row['variance'], 2) }}</td>
            <td class="text-end">{{ $row['pct_used'] !== null ? $row['pct_used'].'%' : '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-muted">Add budget lines above to start tracking.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div></div>
@endsection

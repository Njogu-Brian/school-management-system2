@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', [
    'title' => 'Petty Cash Funds',
    'icon' => 'bi bi-cash-coin',
    'subtitle' => 'Imprest accounts and custodians',
    'actions' => '<a href="'.route('finance.petty-cash-funds.create').'" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus"></i> New Fund</a>'
  ])

  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Code</th><th>Name</th><th>GL Account</th><th>Custodian</th><th>Imprest</th><th>Balance</th><th>Status</th></tr></thead>
      <tbody>
        @forelse($funds as $fund)
          <tr>
            <td>{{ $fund->code }}</td>
            <td>{{ $fund->name }}</td>
            <td>{{ $fund->account->code }} — {{ $fund->account->name }}</td>
            <td>{{ optional($fund->custodian)->name ?? '—' }}</td>
            <td>{{ number_format($fund->imprest_amount, 2) }}</td>
            <td>{{ number_format($fund->postedBalance(), 2) }}</td>
            <td>{{ $fund->is_active ? 'Active' : 'Inactive' }}</td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-muted">No petty cash funds configured.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div></div>
@endsection

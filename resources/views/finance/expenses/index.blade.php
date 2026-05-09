@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
      'title' => 'Expenses',
      'icon' => 'bi bi-wallet2',
      'subtitle' => 'Track school operating expenses and approvals',
      'actions' => '<a href="' . route('finance.expenses.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> New Expense</a>'
    ])

    <div class="finance-card mb-3">
      <div class="finance-card-body">
        <form method="GET" class="row g-2">
          <div class="col-md-4"><input name="status" value="{{ request('status') }}" class="finance-form-control" placeholder="Status"></div>
          <div class="col-md-4">
            <select name="vendor_id" class="finance-form-select">
              <option value="">All Vendors</option>
              @foreach($vendors as $vendor)
                <option value="{{ $vendor->id }}" @selected((string)request('vendor_id') === (string)$vendor->id)>{{ $vendor->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4"><button class="btn btn-finance btn-finance-primary w-100">Filter</button></div>
        </form>
      </div>
    </div>

    <div class="finance-table-wrapper">
      <table class="finance-table">
        <thead><tr><th>No</th><th>Date</th><th>Vendor</th><th>Status</th><th>Total</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($expenses as $expense)
          <tr>
            <td>{{ $expense->expense_no }}</td>
            <td>{{ optional($expense->expense_date)->format('Y-m-d') }}</td>
            <td>{{ optional($expense->vendor)->name ?? 'Direct' }}</td>
            <td>{{ ucfirst($expense->status) }}</td>
            <td>{{ number_format((float)$expense->total, 2) }}</td>
            <td><a href="{{ route('finance.expenses.show', $expense) }}" class="btn btn-sm btn-info">View</a></td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center">No expenses found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-3">{{ $expenses->links() }}</div>
  </div>
</div>
@endsection

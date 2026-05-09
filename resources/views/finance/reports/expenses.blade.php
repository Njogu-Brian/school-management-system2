@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Expense Reports', 'icon' => 'bi bi-graph-up', 'subtitle' => 'Category, vendor, and voucher analytics', 'actions' => '<a href="' . route('finance.expenses.reports.export-csv', request()->query()) . '" class="btn btn-finance btn-finance-outline">CSV</a> <a href="' . route('finance.expenses.reports.export-pdf', request()->query()) . '" class="btn btn-finance btn-finance-primary">PDF</a>'])

  <div class="finance-card mb-3"><div class="finance-card-body">
    <form method="GET" class="row g-2">
      <div class="col-md-3"><input type="date" name="from_date" value="{{ request('from_date') }}" class="finance-form-control"></div>
      <div class="col-md-3"><input type="date" name="to_date" value="{{ request('to_date') }}" class="finance-form-control"></div>
      <div class="col-md-3"><input name="status" value="{{ request('status') }}" placeholder="Status" class="finance-form-control"></div>
      <div class="col-md-3"><button class="btn btn-primary w-100">Filter</button></div>
    </form>
  </div></div>

  <div class="row g-3 mb-3">
    <div class="col-md-6"><div class="finance-card"><div class="finance-card-body"><h6>By Category</h6>@foreach($categorySummary as $row)<div>{{ $row->category_name }}: {{ number_format((float)$row->total_amount,2) }}</div>@endforeach</div></div></div>
    <div class="col-md-6"><div class="finance-card"><div class="finance-card-body"><h6>By Vendor</h6>@foreach($vendorSummary as $row)<div>{{ $row->vendor_name }}: {{ number_format((float)$row->total_amount,2) }}</div>@endforeach</div></div></div>
  </div>

  <div class="finance-table-wrapper">
    <table class="finance-table"><thead><tr><th>No</th><th>Date</th><th>Status</th><th>Vendor</th><th>Total</th></tr></thead><tbody>
      @foreach($expenses as $expense)
      <tr><td>{{ $expense->expense_no }}</td><td>{{ optional($expense->expense_date)->format('Y-m-d') }}</td><td>{{ ucfirst($expense->status) }}</td><td>{{ optional($expense->vendor)->name }}</td><td>{{ number_format((float)$expense->total,2) }}</td></tr>
      @endforeach
    </tbody></table>
  </div>
  {{ $expenses->links() }}
</div></div>
@endsection

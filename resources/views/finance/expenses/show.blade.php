@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Expense ' . $expense->expense_no, 'icon' => 'bi bi-receipt', 'subtitle' => 'Expense lifecycle and voucher processing'])

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="finance-card mb-3"><div class="finance-card-body">
    <div class="row">
      <div class="col-md-3"><strong>Status:</strong> {{ ucfirst($expense->status) }}</div>
      <div class="col-md-3"><strong>Vendor:</strong> {{ optional($expense->vendor)->name ?? 'Direct' }}</div>
      <div class="col-md-3"><strong>Total:</strong> {{ number_format((float)$expense->total, 2) }}</div>
      <div class="col-md-3"><strong>Requested by:</strong> {{ optional($expense->requester)->name }}</div>
    </div>
  </div></div>

  <div class="finance-card mb-3"><div class="finance-card-body">
    <h6>Lines</h6>
    <table class="finance-table">
      <thead><tr><th>Category</th><th>Description</th><th>Qty</th><th>Unit Cost</th><th>Total</th></tr></thead>
      <tbody>
      @foreach($expense->lines as $line)
      <tr><td>{{ $line->category->name ?? '-' }}</td><td>{{ $line->description }}</td><td>{{ $line->qty }}</td><td>{{ number_format((float)$line->unit_cost, 2) }}</td><td>{{ number_format((float)$line->line_total, 2) }}</td></tr>
      @endforeach
      </tbody>
    </table>
  </div></div>

  @can('submit', $expense)
    <form method="POST" action="{{ route('finance.expenses.submit', $expense) }}" class="d-inline">@csrf<button class="btn btn-primary">Submit for Approval</button></form>
  @endcan
  @can('approve', $expense)
    <form method="POST" action="{{ route('finance.expenses.approvals.store', $expense) }}" class="d-inline">@csrf
      <input type="hidden" name="decision" value="approved"><button class="btn btn-success">Approve</button>
    </form>
    <form method="POST" action="{{ route('finance.expenses.approvals.store', $expense) }}" class="d-inline">@csrf
      <input type="hidden" name="decision" value="rejected"><button class="btn btn-danger">Reject</button>
    </form>
  @endcan
  @can('pay', $expense)
    <form method="POST" action="{{ route('finance.payment-vouchers.store') }}" class="d-inline">@csrf
      <input type="hidden" name="expense_id" value="{{ $expense->id }}">
      <button class="btn btn-warning">Generate Voucher</button>
    </form>
  @endcan

</div></div>
@endsection

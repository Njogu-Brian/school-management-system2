@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Voucher ' . $voucher->voucher_no, 'icon' => 'bi bi-receipt-cutoff', 'subtitle' => 'Pay approved expenses'])
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="finance-card mb-3"><div class="finance-card-body">
    <div><strong>Expense:</strong> {{ $voucher->expense->expense_no ?? '-' }}</div>
    <div><strong>Payee:</strong> {{ $voucher->payee }}</div>
    <div><strong>Status:</strong> {{ ucfirst($voucher->status) }}</div>
    <div><strong>Amount:</strong> {{ number_format((float)$voucher->amount, 2) }}</div>
  </div></div>

  @if($voucher->status !== 'paid')
  <div class="finance-card mb-3"><div class="finance-card-body">
    <form method="POST" action="{{ route('finance.payment-vouchers.pay', $voucher) }}" class="row g-2">
      @csrf
      <div class="col-md-3"><input class="finance-form-control" name="reference_no" placeholder="Reference"></div>
      <div class="col-md-3"><input class="finance-form-control" name="account_source" placeholder="Bank/Cash Source"></div>
      <div class="col-md-3"><input class="finance-form-control" type="number" step="0.01" name="amount" value="{{ $voucher->amount }}" required></div>
      <div class="col-md-3"><button class="btn btn-success w-100">Record Payment</button></div>
    </form>
  </div></div>
  @endif

  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Paid At</th><th>Reference</th><th>Source</th><th>Amount</th><th>By</th></tr></thead>
      <tbody>
      @forelse($voucher->payments as $payment)
      <tr><td>{{ optional($payment->paid_at)->format('Y-m-d H:i') }}</td><td>{{ $payment->reference_no }}</td><td>{{ $payment->account_source }}</td><td>{{ number_format((float)$payment->amount, 2) }}</td><td>{{ optional($payment->recorder)->name }}</td></tr>
      @empty
      <tr><td colspan="5" class="text-center">No payments yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div></div>
@endsection

@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Voucher ' . $voucher->voucher_no, 'icon' => 'bi bi-receipt-cutoff', 'subtitle' => 'Pay approved expenses'])
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="finance-card mb-3"><div class="finance-card-body">
    <div><strong>Expense:</strong> {{ $voucher->expense->expense_no ?? '-' }}</div>
    <div><strong>Payee:</strong> {{ $voucher->payee }}</div>
    <div><strong>Status:</strong> {{ ucfirst($voucher->status) }}</div>
    <div><strong>Amount:</strong> KES {{ number_format((float)$voucher->amount, 2) }}</div>
  </div></div>

  @if($voucher->status !== 'paid')
  <div class="finance-card mb-3"><div class="finance-card-body">
    <h6 class="mb-3">Record Payment</h6>
    <form method="POST" action="{{ route('finance.payment-vouchers.pay', $voucher) }}" class="row g-2">
      @csrf
      <div class="col-md-3"><input class="finance-form-control" name="reference_no" placeholder="Bank ref / cheque no"></div>
      <div class="col-md-3">
        <select class="finance-form-select" name="bank_account_id">
          <option value="">Pay from bank account…</option>
          @foreach($bankAccounts as $bank)
            <option value="{{ $bank->id }}">{{ $bank->name }} @if($bank->account)({{ $bank->account->code }})@endif</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <select class="finance-form-select" name="account_id">
          <option value="">Or GL cash account…</option>
          @foreach($cashAccounts as $acct)
            <option value="{{ $acct->id }}">{{ $acct->code }} — {{ $acct->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2"><input class="finance-form-control" type="number" step="0.01" name="amount" value="{{ $voucher->amount }}" required></div>
      <div class="col-md-1"><button class="btn btn-success w-100">Pay</button></div>
    </form>
    <p class="small text-muted mt-2 mb-0">Choose the bank account or cash GL account that funds are paid from. This drives the credit side of the journal entry.</p>
  </div></div>
  @endif

  @if($voucher->journalEntry)
  <div class="finance-card mb-3"><div class="finance-card-body">
    <h6>Posted Journal: <a href="{{ route('finance.journal-entries.show', $voucher->journalEntry) }}">{{ $voucher->journalEntry->entry_no }}</a></h6>
  </div></div>
  @endif

  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Paid At</th><th>Reference</th><th>Source</th><th>Amount</th><th>By</th></tr></thead>
      <tbody>
      @forelse($voucher->payments as $payment)
      <tr><td>{{ optional($payment->paid_at)->format('Y-m-d H:i') }}</td><td>{{ $payment->reference_no }}</td><td>{{ optional($payment->account)->code ?? $payment->account_source }}</td><td>{{ number_format((float)$payment->amount, 2) }}</td><td>{{ optional($payment->recorder)->name }}</td></tr>
      @empty
      <tr><td colspan="5" class="text-center">No payments yet.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div></div>
@endsection

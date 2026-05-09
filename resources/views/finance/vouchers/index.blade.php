@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Payment Vouchers', 'icon' => 'bi bi-file-earmark-text', 'subtitle' => 'Voucher register and settlement'])
  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Voucher</th><th>Expense</th><th>Status</th><th>Amount</th><th>Paid</th><th></th></tr></thead>
      <tbody>
      @foreach($vouchers as $voucher)
      <tr>
        <td>{{ $voucher->voucher_no }}</td>
        <td>{{ $voucher->expense->expense_no ?? '-' }}</td>
        <td>{{ ucfirst($voucher->status) }}</td>
        <td>{{ number_format((float)$voucher->amount, 2) }}</td>
        <td>{{ number_format((float)$voucher->payments->sum('amount'), 2) }}</td>
        <td><a href="{{ route('finance.payment-vouchers.show', $voucher) }}" class="btn btn-sm btn-info">View</a></td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  {{ $vouchers->links() }}
</div></div>
@endsection

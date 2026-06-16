@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', [
    'title' => 'Petty Cash Vouchers',
    'icon' => 'bi bi-receipt-cutoff',
    'subtitle' => 'Disbursements and replenishments',
    'actions' => '<a href="'.route('finance.petty-cash-vouchers.create').'" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus"></i> New Voucher</a>'
  ])

  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Voucher No</th><th>Date</th><th>Fund</th><th>Type</th><th>Payee</th><th class="text-end">Amount</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @forelse($vouchers as $voucher)
          <tr>
            <td>{{ $voucher->voucher_no }}</td>
            <td>{{ $voucher->voucher_date->format('d M Y') }}</td>
            <td>{{ $voucher->fund->name }}</td>
            <td>{{ \App\Models\PettyCashVoucher::types()[$voucher->voucher_type] ?? $voucher->voucher_type }}</td>
            <td>{{ $voucher->payee ?? '—' }}</td>
            <td class="text-end">{{ number_format($voucher->amount, 2) }}</td>
            <td>{{ ucfirst($voucher->status) }}</td>
            <td><a href="{{ route('finance.petty-cash-vouchers.show', $voucher) }}">View</a></td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-muted">No petty cash vouchers yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $vouchers->links() }}
</div></div>
@endsection

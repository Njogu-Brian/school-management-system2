@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Petty Cash Voucher ' . $voucher->voucher_no, 'icon' => 'bi bi-receipt-cutoff'])

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <div class="finance-card mb-3"><div class="finance-card-body">
    <div class="row">
      <div class="col-md-6">
        <p><strong>Fund:</strong> {{ $voucher->fund->name }}</p>
        <p><strong>Type:</strong> {{ \App\Models\PettyCashVoucher::types()[$voucher->voucher_type] ?? $voucher->voucher_type }}</p>
        <p><strong>Date:</strong> {{ $voucher->voucher_date->format('d M Y') }}</p>
        <p><strong>Payee:</strong> {{ $voucher->payee ?? '—' }}</p>
      </div>
      <div class="col-md-6">
        <p><strong>Amount:</strong> KES {{ number_format($voucher->amount, 2) }}</p>
        <p><strong>Status:</strong> {{ ucfirst($voucher->status) }}</p>
        <p><strong>Category:</strong> {{ optional($voucher->expenseCategory)->breadcrumb() ?? '—' }}</p>
        <p><strong>Description:</strong> {{ $voucher->description }}</p>
      </div>
    </div>

    @if($voucher->status !== 'posted')
      <div class="d-flex gap-2 mt-3">
        @if($voucher->status === 'draft')
          <form method="POST" action="{{ route('finance.petty-cash-vouchers.approve', $voucher) }}">@csrf
            <button class="btn btn-outline-primary">Approve</button>
          </form>
        @endif
        <form method="POST" action="{{ route('finance.petty-cash-vouchers.post', $voucher) }}">@csrf
          <button class="btn btn-primary">Post to Ledger</button>
        </form>
      </div>
    @endif
  </div></div>

  @if($voucher->journalEntry)
    <div class="finance-card"><div class="finance-card-body">
      <h6>Posted Journal: {{ $voucher->journalEntry->entry_no }}</h6>
      <table class="finance-table mt-2">
        <thead><tr><th>Account</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
        <tbody>
          @foreach($voucher->journalEntry->lines as $line)
            <tr>
              <td>{{ $line->account->code }} — {{ $line->account->name }}</td>
              <td class="text-end">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
              <td class="text-end">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div></div>
  @endif
</div></div>
@endsection

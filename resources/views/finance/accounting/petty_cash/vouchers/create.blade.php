@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'New Petty Cash Voucher', 'icon' => 'bi bi-receipt-cutoff'])

  <div class="finance-card"><div class="finance-card-body">
    <form method="POST" action="{{ route('finance.petty-cash-vouchers.store') }}" class="row g-3">@csrf
      <div class="col-md-4"><label class="form-label">Petty Cash Fund</label>
        <select class="finance-form-select" name="petty_cash_fund_id" required>
          @foreach($funds as $fund)<option value="{{ $fund->id }}">{{ $fund->name }} ({{ $fund->account->code }})</option>@endforeach
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Type</label>
        <select class="finance-form-select" name="voucher_type" required>
          <option value="disbursement">Disbursement</option>
          <option value="replenishment">Replenishment</option>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Date</label><input type="date" class="finance-form-control" name="voucher_date" value="{{ date('Y-m-d') }}" required></div>
      <div class="col-md-4"><label class="form-label">Payee</label><input class="finance-form-control" name="payee"></div>
      <div class="col-md-4"><label class="form-label">Expense Category</label>
        <select class="finance-form-select" name="expense_category_id"><option value="">—</option>
          @foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->breadcrumb() }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Expense GL Account (override)</label>
        <select class="finance-form-select" name="account_id"><option value="">Use category default</option>
          @foreach($expenseAccounts as $account)<option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Amount</label><input type="number" step="0.01" min="0.01" class="finance-form-control" name="amount" required></div>
      <div class="col-12"><label class="form-label">Description</label><textarea class="finance-form-control" name="description" rows="2" required></textarea></div>
      <div class="col-12"><button class="btn btn-primary">Create Voucher</button></div>
    </form>
  </div></div>
</div></div>
@endsection

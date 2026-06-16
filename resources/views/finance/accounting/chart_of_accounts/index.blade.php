@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Chart of Accounts', 'icon' => 'bi bi-diagram-3', 'subtitle' => 'Main account types and sub-accounts for double-entry posting'])

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

  <div class="finance-card mb-3"><div class="finance-card-body">
    <h6 class="mb-3">Add Account</h6>
    <form method="POST" action="{{ route('finance.chart-of-accounts.store') }}" class="row g-2">@csrf
      <div class="col-md-2">
        <input class="finance-form-control" name="code" placeholder="Code (auto)">
      </div>
      <div class="col-md-3">
        <input class="finance-form-control" name="name" placeholder="Account name" required>
      </div>
      <div class="col-md-2">
        <select class="finance-form-select" name="account_type" required>
          @foreach($types as $key => $label)
            <option value="{{ $key }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <select class="finance-form-select" name="parent_id">
          <option value="">No parent (top level)</option>
          @foreach($roots->flatMap(fn($r) => collect([$r])->merge($r->children)) as $acct)
            <option value="{{ $acct->id }}">{{ $acct->code }} — {{ $acct->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-1">
        <select class="finance-form-select" name="is_postable">
          <option value="1">Postable</option>
          <option value="0">Header</option>
        </select>
      </div>
      <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
    </form>
  </div></div>

  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Balance</th><th>Status</th></tr></thead>
      <tbody>
        @foreach($roots as $account)
          @include('finance.accounting.chart_of_accounts._row', ['account' => $account, 'depth' => 0])
        @endforeach
      </tbody>
    </table>
  </div>
</div></div>
@endsection

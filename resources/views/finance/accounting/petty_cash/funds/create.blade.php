@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'New Petty Cash Fund', 'icon' => 'bi bi-cash-coin'])

  <div class="finance-card"><div class="finance-card-body">
    <form method="POST" action="{{ route('finance.petty-cash-funds.store') }}" class="row g-3">@csrf
      <div class="col-md-3"><label class="form-label">Code</label><input class="finance-form-control" name="code" required placeholder="PC-OPS"></div>
      <div class="col-md-5"><label class="form-label">Name</label><input class="finance-form-control" name="name" required></div>
      <div class="col-md-4"><label class="form-label">GL Account</label>
        <select class="finance-form-select" name="account_id" required>
          @foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Custodian</label>
        <select class="finance-form-select" name="custodian_id"><option value="">—</option>
          @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">Imprest Amount</label><input type="number" step="0.01" class="finance-form-control" name="imprest_amount" required></div>
      <div class="col-12"><label class="form-label">Notes</label><textarea class="finance-form-control" name="notes" rows="2"></textarea></div>
      <div class="col-12"><button class="btn btn-primary">Create Fund</button></div>
    </form>
  </div></div>
</div></div>
@endsection

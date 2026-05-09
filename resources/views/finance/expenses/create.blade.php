@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Create Expense', 'icon' => 'bi bi-plus-circle', 'subtitle' => 'Capture a new expense draft'])

  <form method="POST" action="{{ route('finance.expenses.store') }}" class="finance-card">
    @csrf
    <div class="finance-card-body">
      @include('finance.expenses.partials.form', ['expense' => null])
      <button class="btn btn-finance btn-finance-primary">Save Draft</button>
    </div>
  </form>
</div></div>
@endsection

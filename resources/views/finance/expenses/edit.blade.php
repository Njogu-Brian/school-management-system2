@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Edit Expense', 'icon' => 'bi bi-pencil', 'subtitle' => 'Update draft expense'])

  <form method="POST" action="{{ route('finance.expenses.update', $expense) }}" class="finance-card">
    @csrf
    @method('PUT')
    <div class="finance-card-body">
      @include('finance.expenses.partials.form', ['expense' => $expense])
      <button class="btn btn-finance btn-finance-primary">Update Draft</button>
    </div>
  </form>
</div></div>
@endsection

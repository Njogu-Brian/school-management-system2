@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Bank Accounts',
        'icon' => 'bi bi-bank',
        'subtitle' => 'Manage bank accounts for payment processing',
        'actions' => '<a href="' . route('finance.bank-accounts.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Add Bank Account</a>'
    ])

    @include('finance.payment_settings.partials.bank-accounts')
  </div>
</div>
@endsection

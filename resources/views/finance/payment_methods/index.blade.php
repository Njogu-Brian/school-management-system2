@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Payment Methods',
        'icon' => 'bi bi-credit-card',
        'subtitle' => 'Manage payment methods and link to bank accounts',
        'actions' => '<a href="' . route('finance.payment-methods.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Add Payment Method</a>'
    ])

    @include('finance.payment_settings.partials.payment-methods')
  </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @php
        $tab = $tab ?? request('tab', 'bank-accounts');
        if (!in_array($tab, ['bank-accounts', 'payment-methods', 'legacy-imports'], true)) {
            $tab = 'bank-accounts';
        }

        $headerActions = match ($tab) {
            'bank-accounts' => '<a href="' . route('finance.bank-accounts.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Add Bank Account</a>',
            'payment-methods' => '<a href="' . route('finance.payment-methods.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Add Payment Method</a>',
            default => '',
        };
    @endphp

    @include('finance.partials.header', [
        'title' => 'Payment Settings',
        'icon' => 'bi bi-gear',
        'subtitle' => 'Bank accounts, payment methods, and legacy imports',
        'actions' => $headerActions,
    ])

    <ul class="nav nav-pills mb-4 finance-animate" id="paymentSettingsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'bank-accounts' ? 'active' : '' }}"
               href="{{ route('finance.payment-settings.index', ['tab' => 'bank-accounts']) }}">
                <i class="bi bi-bank"></i> Bank Accounts
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'payment-methods' ? 'active' : '' }}"
               href="{{ route('finance.payment-settings.index', ['tab' => 'payment-methods']) }}">
                <i class="bi bi-credit-card"></i> Payment Methods
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link {{ $tab === 'legacy-imports' ? 'active' : '' }}"
               href="{{ route('finance.payment-settings.index', ['tab' => 'legacy-imports']) }}">
                <i class="bi bi-upload"></i> Legacy Imports
            </a>
        </li>
    </ul>

    <div class="tab-content">
        @if($tab === 'bank-accounts')
            @include('finance.payment_settings.partials.bank-accounts')
        @elseif($tab === 'payment-methods')
            @include('finance.payment_settings.partials.payment-methods')
        @else
            @include('finance.payment_settings.partials.legacy-imports')
        @endif
    </div>
  </div>
</div>
@endsection

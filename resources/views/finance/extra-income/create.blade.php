@extends('layouts.app')

@section('content')
<div class="finance-page">
    <div class="finance-shell">
        @include('finance.partials.header', [
            'title' => 'Add extra income',
            'icon' => 'bi bi-plus-circle',
            'subtitle' => 'Schedule a trip, fun day, swimming charge, or other activity fee.',
        ])

        @include('finance.invoices.partials.alerts')

        <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
            <div class="finance-card-body p-4">
                <form method="POST" action="{{ route('finance.extra-income.store') }}">
                    @csrf
                    @include('finance.extra-income.form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

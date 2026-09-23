@extends('layouts.app')

@section('content')
<div class="finance-page">
    <div class="finance-shell">
        @include('finance.partials.header', [
            'title' => 'Edit extra income',
            'icon' => 'bi bi-pencil',
            'subtitle' => $item->name,
        ])

        @include('finance.invoices.partials.alerts')

        <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
            <div class="finance-card-body p-4">
                <form method="POST" action="{{ route('finance.extra-income.update', $item) }}">
                    @csrf
                    @method('PUT')
                    @include('finance.extra-income.form')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

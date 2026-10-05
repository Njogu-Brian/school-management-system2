@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Prompt Parent to Pay (STK Push)',
        'icon' => 'bi bi-phone',
        'subtitle' => 'Send M-PESA STK Push payment request to parents',
        'actions' => '<a href="' . route('finance.mpesa.prompt', ['tab' => 'dashboard']) . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>'
    ])

    @include('finance.mpesa.partials.prompt-tab')
@endsection

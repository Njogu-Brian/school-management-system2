@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'M-PESA Payment Dashboard',
        'icon' => 'bi bi-phone',
        'subtitle' => 'Monitor M-PESA transactions and payment links',
        'actions' => '<a href="' . route('finance.mpesa.prompt', ['tab' => 'prompt']) . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-send"></i> Prompt Payment</a><a href="' . route('finance.mpesa.links.create') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-link-45deg"></i> Create Link</a>'
    ])

    @include('finance.mpesa.partials.dashboard-tab')
@endsection

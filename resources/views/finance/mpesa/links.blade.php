@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Payment Links',
        'icon' => 'bi bi-link-45deg',
        'subtitle' => 'Manage and monitor payment links',
        'actions' => '<a href="' . route('finance.mpesa.links.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Create Link</a><a href="' . route('finance.mpesa.prompt', ['tab' => 'dashboard']) . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>'
    ])

    @include('finance.mpesa.partials.links-tab')
@endsection

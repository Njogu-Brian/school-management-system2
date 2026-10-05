@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Legacy Finance Imports',
        'icon' => 'bi bi-file-earmark-arrow-up',
        'subtitle' => 'Upload legacy PDF statements for a class and review parsed batches',
        'actions' => ''
    ])

    @include('finance.payment_settings.partials.legacy-imports')
@endsection

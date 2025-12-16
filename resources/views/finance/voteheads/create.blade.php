@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Create Votehead',
        'icon' => 'bi bi-plus-circle',
        'subtitle' => 'Add a new fee category'
    ])

    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <i class="bi bi-file-earmark-text me-2"></i> Votehead Information
        </div>
        <div class="finance-card-body">
            <form method="POST" action="{{ route('finance.voteheads.store') }}">
                @csrf
                @include('finance.voteheads.form')
            </form>
        </div>
    </div>
</div>
@endsection

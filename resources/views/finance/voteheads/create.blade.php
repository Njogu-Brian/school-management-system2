@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Create Votehead',
        'icon' => 'bi bi-plus-circle',
        'subtitle' => 'Add a new fee category'
    ])

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mt-3">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-text"></i>
            <span>Votehead Information</span>
        </div>
        <div class="finance-card-body p-4">
            <form method="POST" action="{{ route('finance.voteheads.store') }}">
                @csrf
                @include('finance.voteheads.form')
            </form>
        </div>
    </div>
@endsection

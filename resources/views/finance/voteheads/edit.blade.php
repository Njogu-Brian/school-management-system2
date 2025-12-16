@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Edit Votehead',
        'icon' => 'bi bi-pencil',
        'subtitle' => 'Update votehead details'
    ])

    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <i class="bi bi-file-earmark-text me-2"></i> Votehead Information
        </div>
        <div class="finance-card-body">
            <form method="POST" action="{{ route('finance.voteheads.update', $votehead->id) }}">
                @csrf
                @method('PUT')
                @include('finance.voteheads.form')
            </form>
        </div>
    </div>
</div>
@endsection

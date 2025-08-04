@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-3">Add Votehead</h4>
    <form action="{{ route('voteheads.store') }}" method="POST">
        @include('finance.voteheads.form')
    </form>
</div>
@endsection

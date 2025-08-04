@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Create Votehead</h3>

    <form method="POST" action="{{ route('voteheads.store') }}">
        @csrf

        @include('finance.voteheads.form')
    </form>
</div>
@endsection

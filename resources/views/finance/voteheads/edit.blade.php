@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Edit Votehead</h3>

    <form method="POST" action="{{ route('finance.voteheads.update', $votehead->id) }}">
        @csrf
        @method('PUT')

        @include('finance.voteheads.form')
    </form>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-3">Edit Votehead</h4>
    <form action="{{ route('voteheads.update', $votehead) }}" method="POST">
        @method('PUT')
        @include('finance.voteheads.form')
    </form>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add Behaviour Category</h1>

    <form action="{{ route('academics.behaviours.store') }}" method="POST">
        @csrf
        @include('academics.behaviours.partials.form',['behaviour'=>null])
        <button class="btn btn-success">Save</button>
    </form>
</div>
@endsection

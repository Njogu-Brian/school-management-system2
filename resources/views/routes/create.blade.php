@extends('layouts.app')

@section('content')
<h1>Add Route</h1>

<form action="{{ route('routes.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="name">Route Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="area">Area</label>
        <input type="text" name="area" class="form-control">
    </div>
    <button class="btn btn-primary">Save</button>
</form>
@endsection

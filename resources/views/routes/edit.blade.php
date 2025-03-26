@extends('layouts.app')

@section('content')
<h1>Edit Route</h1>

<form action="{{ route('routes.update', $route) }}" method="POST">
    @csrf @method('PUT')
    <div class="mb-3">
        <label for="name">Route Name</label>
        <input type="text" name="name" class="form-control" value="{{ $route->name }}" required>
    </div>
    <div class="mb-3">
        <label for="area">Area</label>
        <input type="text" name="area" class="form-control" value="{{ $route->area }}">
    </div>
    <button class="btn btn-primary">Update</button>
</form>
@endsection

@extends('layouts.app')

@section('content')
<h1>Add Drop-Off Point</h1>

<form action="{{ route('dropoffpoints.store') }}" method="POST">
    @csrf

    <div class="mb-3">
        <label for="name">Drop-Off Point Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="route_id">Assign to Route</label>
        <select name="route_id" class="form-control" required>
            @foreach($routes as $route)
                <option value="{{ $route->id }}">{{ $route->name }} ({{ $route->area }})</option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Add Drop-Off Point</button>
</form>
@endsection

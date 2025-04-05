@extends('layouts.app')

@section('content')
<h1>Edit Drop-Off Point</h1>

<form action="{{ route('dropoffpoints.update', $dropOffPoint->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label for="name">Drop-Off Point Name</label>
        <input type="text" name="name" class="form-control" value="{{ $dropOffPoint->name }}" required>
    </div>

    <div class="mb-3">
        <label for="route_id">Assign to Route</label>
        <select name="route_id" class="form-control" required>
            @foreach($routes as $route)
                <option value="{{ $route->id }}" {{ $dropOffPoint->route_id == $route->id ? 'selected' : '' }}>
                    {{ $route->name }} ({{ $route->area }})
                </option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Update Drop-Off Point</button>
</form>
@endsection

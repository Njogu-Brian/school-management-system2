@extends('layouts.app')

@section('content')
<h1>Add Drop-Off Point</h1>

<form action="{{ route('transport.dropoffpoints.store') }}" method="POST" class="card p-3">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label">Drop-Off Point Name</label>
        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label for="route_id" class="form-label">Assign to Route</label>
        <select name="route_id" id="route_id" class="form-select" required>
            <option value="">Select Route</option>
            @foreach($routes as $route)
                <option value="{{ $route->id }}" {{ old('route_id') == $route->id ? 'selected' : '' }}>
                    {{ $route->name }}{{ $route->area ? ' ('.$route->area.')' : '' }}
                </option>
            @endforeach
        </select>
        @error('route_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Add Drop-Off Point</button>
        <a href="{{ route('transport.dropoffpoints.index') }}" class="btn btn-light">Cancel</a>
    </div>
</form>
@endsection

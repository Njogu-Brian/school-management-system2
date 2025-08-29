@extends('layouts.app')

@section('content')
<h1>Edit Route</h1>

<form action="{{ route('transport.routes.update', $route) }}" method="POST" class="card p-3">
    @csrf @method('PUT')

    <div class="mb-3">
        <label for="name" class="form-label">Route Name</label>
        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $route->name) }}" required>
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label for="area" class="form-label">Area</label>
        <input type="text" name="area" id="area" class="form-control" value="{{ old('area', $route->area) }}">
    </div>

    <div class="mb-3">
        <label for="vehicle_ids" class="form-label">Assign Vehicles</label>
        <select name="vehicle_ids[]" id="vehicle_ids" class="form-control" multiple>
            @foreach ($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}"
                    @if($route->vehicles->contains($vehicle->id)) selected @endif>
                    {{ $vehicle->vehicle_number }} (Driver: {{ $vehicle->driver_name ?? 'Unassigned' }})
                </option>
            @endforeach
        </select>
        <small class="text-muted">Hold Ctrl (or Cmd on Mac) to select multiple vehicles.</small>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary">Update</button>
        <a href="{{ route('transport.routes.index') }}" class="btn btn-light">Cancel</a>
    </div>
</form>
@endsection

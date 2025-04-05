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
        <div class="mb-3">
        <label for="vehicle_ids">Assign Vehicles</label>
        <select name="vehicle_ids[]" class="form-control" multiple>
            @foreach ($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}"
                    @if(isset($route) && $route->vehicles->contains($vehicle->id)) selected @endif>
                    {{ $vehicle->vehicle_number }} (Driver: {{ $vehicle->driver_name ?? 'Unassigned' }})
                </option>
            @endforeach
        </select>
        <small class="text-muted">Hold Ctrl (or Cmd on Mac) to select multiple vehicles.</small>
    </div>

    <button class="btn btn-primary">Save</button>
</form>
@endsection

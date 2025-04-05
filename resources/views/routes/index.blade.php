@extends('layouts.app')

@section('content')
<h1>Routes Management</h1>

<!-- Add New Route -->
<a href="{{ route('routes.create') }}" class="btn btn-success mb-3">Add New Route</a>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<!-- Routes List -->
<h3>All Routes</h3>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Route Name</th>
            <th>Area</th>
            <th>Assigned Vehicles</th>
            <th>Drop-Off Points</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($routes as $route)
            <tr>
                <td>{{ $route->name }}</td>
                <td>{{ $route->area }}</td>
                <td>
                    @forelse ($route->vehicles as $vehicle)
                        {{ $vehicle->vehicle_number }} (Driver: {{ $vehicle->driver_name ?? 'Unassigned' }})<br>
                    @empty
                        <span class="text-muted">No Vehicle Assigned</span>
                    @endforelse
                </td>
                <td>
                    @forelse ($route->dropOffPoints as $point)
                        {{ $point->name }} <br>
                    @empty
                        <span class="text-muted">No Drop-Off Points Assigned</span>
                    @endforelse
                </td>
                <td>
                    <a href="{{ route('routes.edit', $route) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form action="{{ route('routes.destroy', $route) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this route?')">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<hr>

<!-- Assign Student to Route, Trip, and Drop-Off Point -->
<h3>Assign Student to Route</h3>
<form action="{{ route('transport.assign.student') }}" method="POST">
    @csrf

    <!-- Student Selection -->
    <div class="mb-3">
        <label for="student_id">Select Student</label>
        <select name="student_id" class="form-control" required>
            @foreach ($students as $student)
                <option value="{{ $student->id }}">
                    {{ $student->full_name }} (Class: {{ $student->classroom->name ?? 'N/A' }})
                </option>
            @endforeach
        </select>
    </div>

    <!-- Route Selection -->
    <div class="mb-3">
        <label for="route_id">Select Route</label>
        <select name="route_id" class="form-control" id="route-select" required>
            @foreach ($routes as $route)
                <option value="{{ $route->id }}">
                    {{ $route->name }} - {{ $route->area }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Vehicle Selection (Filtered by Route) -->
    <div class="mb-3">
        <label for="vehicle_id">Select Vehicle (optional)</label>
        <select name="vehicle_id" class="form-control" id="vehicle-select">
            <option value="">-- None --</option>
            @foreach ($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}" data-route="{{ $vehicle->route_id }}">
                    {{ $vehicle->vehicle_number }} - Driver: {{ $vehicle->driver_name ?? 'Unassigned' }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Trip Selection -->
    <div class="mb-3">
        <label for="trip_id">Select Trip</label>
        <select name="trip_id" class="form-control">
            @foreach ($trips as $trip)
                <option value="{{ $trip->id }}">
                    {{ $trip->name }} ({{ $trip->type }})
                </option>
            @endforeach
        </select>
    </div>

    <!-- Drop-Off Point Selection -->
    <div class="mb-3">
        <label for="drop_off_point_id">Select Drop-Off Point</label>
        <select name="drop_off_point_id" class="form-control" id="drop-off-point-select">
            <option value="">-- Select Drop-Off Point --</option>
            @foreach ($dropOffPoints as $point)
                <option value="{{ $point->id }}" data-route="{{ $point->route_id }}">
                    {{ $point->name }}
                </option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Assign Student</button>
</form>

@endsection

<!-- Filtering Logic for Vehicles and Drop-Off Points -->
<script>
document.getElementById('route-select').addEventListener('change', function () {
    const selectedRouteId = this.value;

    // Filter Vehicles
    const vehicleSelect = document.getElementById('vehicle-select');
    Array.from(vehicleSelect.options).forEach(option => {
        option.style.display = option.getAttribute('data-route') === selectedRouteId || option.value === "" 
            ? 'block' : 'none';
    });

    // Filter Drop-Off Points
    const dropOffSelect = document.getElementById('drop-off-point-select');
    Array.from(dropOffSelect.options).forEach(option => {
        option.style.display = option.getAttribute('data-route') === selectedRouteId || option.value === ""
            ? 'block' : 'none';
    });
});
</script>

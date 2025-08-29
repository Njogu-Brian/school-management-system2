@extends('layouts.app')

@section('content')
<h1>Routes Management</h1>

<a href="{{ route('transport.routes.create') }}" class="btn btn-success mb-3">Add New Route</a>

@if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif
@if ($errors->any())
  <div class="alert alert-danger">
      <strong>There were some problems:</strong>
      <ul class="mb-0">
          @foreach ($errors->all() as $err)
              <li>{{ $err }}</li>
          @endforeach
      </ul>
  </div>
@endif

<h3>All Routes</h3>
<table class="table table-bordered align-middle">
    <thead>
        <tr>
            <th>Route Name</th>
            <th>Area</th>
            <th>Assigned Vehicles</th>
            <th>Drop-Off Points</th>
            <th style="width:200px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($routes as $route)
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
                    <a href="{{ route('transport.routes.edit', $route) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form action="{{ route('transport.routes.destroy', $route) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this route?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted">No routes found</td></tr>
        @endforelse
    </tbody>
</table>

<hr>

<h3>Assign Student to Route</h3>
<form action="{{ route('transport.assign.student') }}" method="POST" class="card p-3">
    @csrf

    <div class="mb-3">
        <label for="student_id" class="form-label">Select Student</label>
        <select name="student_id" id="student_id" class="form-control" required>
            @foreach ($students as $student)
                <option value="{{ $student->id }}">
                    {{ $student->full_name }} (Class: {{ $student->classroom->name ?? 'N/A' }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="route_id" class="form-label">Select Route</label>
        <select name="route_id" id="route-select" class="form-control" required>
            @foreach ($routes as $route)
                <option value="{{ $route->id }}">{{ $route->name }} - {{ $route->area }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="vehicle_id" class="form-label">Select Vehicle (optional)</label>
        <select name="vehicle_id" id="vehicle-select" class="form-control">
            <option value="">-- None --</option>
            @foreach ($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}" data-route="{{ $vehicle->route_id }}">
                    {{ $vehicle->vehicle_number }} - Driver: {{ $vehicle->driver_name ?? 'Unassigned' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="trip_id" class="form-label">Select Trip</label>
        <select name="trip_id" id="trip_id" class="form-control">
            @foreach ($trips as $trip)
                <option value="{{ $trip->id }}">{{ $trip->name }} ({{ $trip->type }})</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="drop_off_point_id" class="form-label">Select Drop-Off Point</label>
        <select name="drop_off_point_id" id="drop-off-point-select" class="form-control">
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

@push('scripts')
<script>
(function() {
    const routeSelect   = document.getElementById('route-select');
    const vehicleSelect = document.getElementById('vehicle-select');
    const dropOffSelect = document.getElementById('drop-off-point-select');

    function filterByRoute() {
        const selectedRouteId = routeSelect.value;

        // Vehicles
        Array.from(vehicleSelect.options).forEach(opt => {
            opt.style.display = (!opt.value || opt.getAttribute('data-route') === selectedRouteId) ? 'block' : 'none';
        });

        // Drop-Off Points
        Array.from(dropOffSelect.options).forEach(opt => {
            opt.style.display = (!opt.value || opt.getAttribute('data-route') === selectedRouteId) ? 'block' : 'none';
        });
    }

    if (routeSelect && vehicleSelect && dropOffSelect) {
        routeSelect.addEventListener('change', filterByRoute);
        // run once on load
        filterByRoute();
    }
})();
</script>
@endpush

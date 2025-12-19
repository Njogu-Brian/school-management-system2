@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header">
            <div>
                <p class="eyebrow text-muted mb-1">Transport</p>
                <h1 class="mb-1">Routes</h1>
                <p class="text-muted mb-0">Manage routes, vehicles, and drop-off points.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('transport.routes.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> Add Route
                </a>
            </div>
        </div>

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

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Routes</h5>
                <span class="input-chip">{{ $routes->count() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="overflow-x:auto;">
                    <table class="table table-modern mb-0 align-middle" style="min-width: 100%;">
                        <thead class="table-light">
                            <tr>
                                <th>Route</th>
                                <th>Area</th>
                                <th>Assigned Vehicles</th>
                                <th>Drop-Off Points</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($routes as $route)
                                <tr>
                                    <td class="fw-semibold text-wrap">{{ $route->name }}</td>
                                    <td class="text-wrap">{{ $route->area }}</td>
                                    <td class="text-wrap">
                                        @forelse ($route->vehicles as $vehicle)
                                            <div class="text-muted small">
                                                {{ $vehicle->vehicle_number }}
                                                <span class="input-chip ms-1">{{ $vehicle->driver_name ?? 'Unassigned' }}</span>
                                            </div>
                                        @empty
                                            <span class="text-muted">No Vehicle Assigned</span>
                                        @endforelse
                                    </td>
                                    <td class="text-wrap">
                                        @forelse ($route->dropOffPoints as $point)
                                            <span class="pill-badge me-1 mb-1 d-inline-block">{{ $point->name }}</span>
                                        @empty
                                            <span class="text-muted">No Drop-Off Points Assigned</span>
                                        @endforelse
                                    </td>
                                    <td class="text-end d-flex justify-content-end gap-2">
                                        <a href="{{ route('transport.routes.edit', $route) }}" class="btn btn-sm btn-ghost-strong">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('transport.routes.destroy', $route) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this route?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-ghost-strong text-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No routes found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="settings-card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Assign Student to Route</h5>
                <span class="text-muted small">Link students with routes, vehicles, and trips.</span>
            </div>
            <div class="card-body">
                <form action="{{ route('transport.assign.student') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label for="student_id" class="form-label fw-semibold">Select Student</label>
                        <select name="student_id" id="student_id" class="form-select" required>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">
                                    {{ $student->full_name ?? ($student->first_name.' '.$student->last_name) }}
                                    @if($student->classrooms?->name)<span class="input-chip ms-1">{{ $student->classrooms->name }}</span>@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="route_id" class="form-label fw-semibold">Select Route</label>
                        <select name="route_id" id="route-select" class="form-select" required>
                            @foreach ($routes as $route)
                                <option value="{{ $route->id }}">{{ $route->name }} - {{ $route->area }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="vehicle_id" class="form-label fw-semibold">Select Vehicle (optional)</label>
                        <select name="vehicle_id" id="vehicle-select" class="form-select">
                            <option value="">-- None --</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" data-route="{{ $vehicle->route_id }}">
                                    {{ $vehicle->vehicle_number }} - Driver: {{ $vehicle->driver_name ?? 'Unassigned' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="trip_id" class="form-label fw-semibold">Select Trip</label>
                        <select name="trip_id" id="trip_id" class="form-select">
                            @foreach ($trips as $trip)
                                <option value="{{ $trip->id }}">{{ $trip->name }} ({{ $trip->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="drop_off_point_id" class="form-label fw-semibold">Drop-Off Point</label>
                        <select name="drop_off_point_id" id="drop-off-point-select" class="form-select">
                            <option value="">-- Select Drop-Off Point --</option>
                            @foreach ($dropOffPoints as $point)
                                <option value="{{ $point->id }}" data-route="{{ $point->route_id }}">
                                    {{ $point->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-settings-primary">Assign Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
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

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Assign Student to Route</h1>

    <form action="{{ route('student_assignments.store') }}" method="POST">
        @csrf

        <!-- Student Selection -->
        <div class="mb-3">
            <label for="student_id">Select Student</label>
            <select name="student_id" class="form-control" required>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }} - Class: {{ $student->classroom->name ?? 'N/A' }}</option>
                @endforeach
            </select>
        </div>

        <!-- Route Selection -->
        <div class="mb-3">
            <label for="route_id">Select Route</label>
            <select name="route_id" class="form-control" id="route-select" required>
                @foreach ($routes as $route)
                    <option value="{{ $route->id }}">{{ $route->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Trip Selection (Filtered Based on Route) -->
        <div class="mb-3">
            <label for="trip_id">Select Trip</label>
            <select name="trip_id" class="form-control" id="trip-select">
                @foreach ($trips as $trip)
                    <option value="{{ $trip->id }}" data-route="{{ $trip->route_id }}">{{ $trip->trip_name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Drop-Off Point Selection (Filtered Based on Route) -->
        <div class="mb-3">
            <label for="drop_off_point_id">Select Drop-Off Point</label>
            <select name="drop_off_point_id" class="form-control" id="drop-off-point-select">
                @foreach ($dropOffPoints as $point)
                    <option value="{{ $point->id }}" data-route="{{ $point->route_id }}">{{ $point->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Assign Student</button>
    </form>
</div>
@endsection

<script>
document.getElementById('route-select').addEventListener('change', function() {
    const selectedRouteId = this.value;

    // Filter Trips
    const tripSelect = document.getElementById('trip-select');
    Array.from(tripSelect.options).forEach(option => {
        option.style.display = option.getAttribute('data-route') === selectedRouteId ? 'block' : 'none';
    });

    // Filter Drop-Off Points
    const dropOffSelect = document.getElementById('drop-off-point-select');
    Array.from(dropOffSelect.options).forEach(option => {
        option.style.display = option.getAttribute('data-route') === selectedRouteId ? 'block' : 'none';
    });
});
</script>

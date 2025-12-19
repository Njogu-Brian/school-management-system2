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
                <h1 class="mb-1">Edit Assignment</h1>
                <p class="text-muted mb-0">Update student routing, trip, and drop-off.</p>
            </div>
            <a href="{{ route('transport.student-assignments.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('transport.student-assignments.update', $assignment->id) }}" method="POST" class="row g-3">
                    @csrf @method('PUT')
                    <div class="col-md-6">
                        <label for="student_id" class="form-label fw-semibold">Select Student</label>
                        <select name="student_id" class="form-select">
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" {{ $assignment->student_id == $student->id ? 'selected' : '' }}>
                                    {{ $student->first_name }} {{ $student->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="route_id" class="form-label fw-semibold">Select Route</label>
                        <select name="route_id" class="form-select" id="route-select">
                            @foreach ($routes as $route)
                                <option value="{{ $route->id }}" {{ $assignment->route_id == $route->id ? 'selected' : '' }}>
                                    {{ $route->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="trip_id" class="form-label fw-semibold">Select Trip</label>
                        <select name="trip_id" class="form-select" id="trip-select">
                            @foreach ($trips as $trip)
                                <option value="{{ $trip->id }}" data-route="{{ $trip->route_id }}"
                                    {{ $assignment->trip_id == $trip->id ? 'selected' : '' }}>
                                    {{ $trip->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="drop_off_point_id" class="form-label fw-semibold">Select Drop-Off Point</label>
                        <select name="drop_off_point_id" class="form-select" id="drop-off-point-select">
                            @foreach ($dropOffPoints as $point)
                                <option value="{{ $point->id }}" data-route="{{ $point->route_id }}"
                                    {{ $assignment->drop_off_point_id == $point->id ? 'selected' : '' }}>
                                    {{ $point->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('transport.student-assignments.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">Update Assignment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('route-select').addEventListener('change', function() {
    const selectedRouteId = this.value;
    const tripSelect = document.getElementById('trip-select');
    Array.from(tripSelect.options).forEach(opt => {
        opt.style.display = (opt.getAttribute('data-route') === selectedRouteId) ? 'block' : 'none';
    });
    const dropSelect = document.getElementById('drop-off-point-select');
    Array.from(dropSelect.options).forEach(opt => {
        opt.style.display = (opt.getAttribute('data-route') === selectedRouteId) ? 'block' : 'none';
    });
});
</script>
@endpush

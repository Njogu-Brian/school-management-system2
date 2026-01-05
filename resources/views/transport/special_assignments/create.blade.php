@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Transport / Special Assignments</div>
                <h1>Create Special Assignment</h1>
                <p>Create a special transport assignment for students or vehicles.</p>
            </div>
            <a href="{{ route('transport.special-assignments.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        @if(session('error'))
            <div class="alert alert-danger mt-3">{{ session('error') }}</div>
        @endif

        <div class="settings-card mt-3">
            <div class="card-body">
                <form action="{{ route('transport.special-assignments.store') }}" method="POST" class="row g-3">
                    @csrf

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Assignment Type <span class="text-danger">*</span></label>
                        <select name="assignment_type" id="assignment_type" class="form-select" required onchange="toggleFields()">
                            <option value="student_specific" {{ old('assignment_type') == 'student_specific' ? 'selected' : '' }}>Student Specific</option>
                            <option value="vehicle_wide" {{ old('assignment_type') == 'vehicle_wide' ? 'selected' : '' }}>Vehicle Wide</option>
                        </select>
                        <small class="form-text text-muted">Student specific: for individual students. Vehicle wide: applies to all students on the vehicle.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Transport Mode <span class="text-danger">*</span></label>
                        <select name="transport_mode" id="transport_mode" class="form-select" required onchange="toggleFields()">
                            <option value="vehicle" {{ old('transport_mode') == 'vehicle' ? 'selected' : '' }}>Vehicle</option>
                            <option value="trip" {{ old('transport_mode') == 'trip' ? 'selected' : '' }}>Trip</option>
                            <option value="own_means" {{ old('transport_mode') == 'own_means' ? 'selected' : '' }}>Own Means</option>
                        </select>
                    </div>

                    <div class="col-md-6" id="student_field">
                        <label class="form-label fw-semibold">Student</label>
                        @include('partials.student_live_search', [
                            'hiddenInputId' => 'student_id',
                            'displayInputId' => 'studentFilterSearchSpecialAssignmentCreate',
                            'resultsId' => 'studentFilterResultsSpecialAssignmentCreate',
                            'placeholder' => 'Type name or admission #',
                            'initialLabel' => old('student_id') ? (optional(\App\Models\Student::find(old('student_id')))->full_name . ' (' . optional(\App\Models\Student::find(old('student_id')))->admission_number . ')') : ''
                        ])
                        <small class="form-text text-muted">Required for student-specific assignments</small>
                    </div>

                    <div class="col-md-6" id="vehicle_field">
                        <label class="form-label fw-semibold">Vehicle</label>
                        <select name="vehicle_id" id="vehicle_id" class="form-select">
                            <option value="">-- Select Vehicle --</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->vehicle_number }} - {{ $vehicle->driver_name ?? 'No Driver' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Required when transport mode is "Vehicle"</small>
                    </div>

                    <div class="col-md-6" id="trip_field">
                        <label class="form-label fw-semibold">Trip</label>
                        <select name="trip_id" id="trip_id" class="form-select">
                            <option value="">-- Select Trip --</option>
                            @foreach ($trips as $trip)
                                <option value="{{ $trip->id }}" {{ old('trip_id') == $trip->id ? 'selected' : '' }}>
                                    {{ $trip->trip_name ?? $trip->name }} 
                                    @if($trip->vehicle)
                                        ({{ $trip->vehicle->vehicle_number }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Required when transport mode is "Trip"</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Drop-Off Point</label>
                        <select name="drop_off_point_id" id="drop_off_point_id" class="form-select">
                            <option value="">-- Select Drop-Off Point --</option>
                            @foreach ($dropOffPoints as $point)
                                <option value="{{ $point->id }}" {{ old('drop_off_point_id') == $point->id ? 'selected' : '' }}>
                                    {{ $point->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-select" value="{{ old('start_date') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">End Date</label>
                        <input type="date" name="end_date" class="form-select" value="{{ old('end_date') }}">
                        <small class="form-text text-muted">Leave empty for indefinite assignment</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="Provide a reason for this special assignment...">{{ old('reason') }}</textarea>
                        <small class="form-text text-muted">This assignment will require approval before becoming active.</small>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('transport.special-assignments.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check-circle"></i> Create Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleFields() {
    const assignmentType = document.getElementById('assignment_type').value;
    const transportMode = document.getElementById('transport_mode').value;
    const studentField = document.getElementById('student_field');
    const vehicleField = document.getElementById('vehicle_field');
    const tripField = document.getElementById('trip_field');
    const vehicleSelect = document.getElementById('vehicle_id');
    const tripSelect = document.getElementById('trip_id');

    // Show/hide student field based on assignment type
    if (assignmentType === 'student_specific') {
        studentField.style.display = 'block';
        const studentInput = studentField.querySelector('input[type="hidden"][name="student_id"]');
        if (studentInput) studentInput.setAttribute('required', 'required');
    } else {
        studentField.style.display = 'none';
        const studentInput = studentField.querySelector('input[type="hidden"][name="student_id"]');
        if (studentInput) studentInput.removeAttribute('required');
    }

    // Show/hide vehicle/trip fields based on transport mode
    if (transportMode === 'vehicle') {
        vehicleField.style.display = 'block';
        tripField.style.display = 'none';
        vehicleSelect.setAttribute('required', 'required');
        tripSelect.removeAttribute('required');
    } else if (transportMode === 'trip') {
        vehicleField.style.display = 'none';
        tripField.style.display = 'block';
        vehicleSelect.removeAttribute('required');
        tripSelect.setAttribute('required', 'required');
    } else {
        vehicleField.style.display = 'none';
        tripField.style.display = 'none';
        vehicleSelect.removeAttribute('required');
        tripSelect.removeAttribute('required');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleFields();
});
</script>
@endpush


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
                <p class="text-muted mb-0">Update student trips. Drop-off points are set during transport fee import.</p>
            </div>
            <a href="{{ route('transport.student-assignments.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('transport.student-assignments.update', $student_assignment->id) }}" method="POST" class="row g-3">
                    @csrf @method('PUT')
                    <div class="col-md-12">
                        <label for="student_id" class="form-label fw-semibold">Select Student <span class="text-danger">*</span></label>
                        <select name="student_id" id="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" {{ old('student_id', $student_assignment->student_id) == $student->id ? 'selected' : '' }}>
                                    {{ $student->full_name }} - {{ $student->admission_number }} ({{ optional($student->classroom)->name ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Note: Drop-off point is set during transport fee import in Finance → Transport Fees</small>
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="text-uppercase text-muted mb-3">Morning Trip</h6>
                    </div>

                    <div class="col-md-12">
                        <label for="morning_trip_id" class="form-label fw-semibold">Morning Trip</label>
                        <select name="morning_trip_id" id="morning_trip_id" class="form-select">
                            <option value="">Select Morning Trip (Optional)</option>
                            @foreach ($trips as $trip)
                                <option value="{{ $trip->id }}" {{ old('morning_trip_id', $student_assignment->morning_trip_id) == $trip->id ? 'selected' : '' }}>
                                    {{ optional($trip->vehicle)->vehicle_number ?? 'N/A' }} - {{ $trip->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="text-uppercase text-muted mb-3">Evening Trip</h6>
                    </div>

                    <div class="col-md-12">
                        <label for="evening_trip_id" class="form-label fw-semibold">Evening Trip</label>
                        <select name="evening_trip_id" id="evening_trip_id" class="form-select">
                            <option value="">Select Evening Trip (Optional)</option>
                            @foreach ($trips as $trip)
                                <option value="{{ $trip->id }}" {{ old('evening_trip_id', $student_assignment->evening_trip_id) == $trip->id ? 'selected' : '' }}>
                                    {{ optional($trip->vehicle)->vehicle_number ?? 'N/A' }} - {{ $trip->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('transport.student-assignments.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">Update Assignment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

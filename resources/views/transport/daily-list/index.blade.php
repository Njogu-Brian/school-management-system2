@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">
                    <a href="{{ route('transport.index') }}">Transport</a> / Daily List
                </div>
                <h1>Daily Transport List</h1>
                <p>View and download transport lists for present students.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('transport.daily-list.download') }}?date={{ $date }}&vehicle_id={{ $vehicleId }}&classroom_id={{ $classroomId }}" 
                   class="btn btn-ghost-strong">
                    <i class="bi bi-download"></i> Download Excel
                </a>
                <a href="{{ route('transport.daily-list.print') }}?date={{ $date }}&vehicle_id={{ $vehicleId }}&classroom_id={{ $classroomId }}" 
                   class="btn btn-ghost-strong" target="_blank">
                    <i class="bi bi-printer"></i> Print All
                </a>
                <a href="{{ route('transport.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="settings-card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-funnel me-2"></i> Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('transport.daily-list.index') }}">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" value="{{ $date }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Vehicle (Optional)</label>
                            <select name="vehicle_id" class="form-select">
                                <option value="">All Vehicles</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ $vehicleId == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->vehicle_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Class (Optional)</label>
                            <select name="classroom_id" class="form-select">
                                <option value="">All Classes</option>
                                @foreach($classrooms as $classroom)
                                    <option value="{{ $classroom->id }}" {{ $classroomId == $classroom->id ? 'selected' : '' }}>
                                        {{ $classroom->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-search"></i> Apply Filters
                        </button>
                        <a href="{{ route('transport.daily-list.index') }}" class="btn btn-ghost-strong">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Summary --}}
        <div class="settings-card mt-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Date: <strong>{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</strong></h6>
                    </div>
                    <div class="col-md-6 text-end">
                        <h6>Total Present Students with Transport: <strong>{{ $students->count() }}</strong></h6>
                    </div>
                </div>
            </div>
        </div>

        {{-- Students by Vehicle --}}
        @foreach($studentsByVehicle as $vehicleNumber => $vehicleStudents)
        <div class="settings-card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-truck me-2"></i> Vehicle: {{ $vehicleNumber }}
                    <span class="badge bg-primary ms-2">{{ $vehicleStudents->count() }} students</span>
                </h5>
                <a href="{{ route('transport.daily-list.print-vehicle', ['vehicle' => $vehicleStudents->first()->assignments->first()->eveningTrip->vehicle->id, 'date' => $date]) }}" 
                   class="btn btn-sm btn-ghost-strong" target="_blank">
                    <i class="bi bi-printer"></i> Print Vehicle List
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Admission No</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Trip</th>
                                <th>Drop-off Point</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vehicleStudents as $index => $student)
                            @php
                                $assignment = $student->assignments->first();
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $student->admission_number }}</td>
                                <td>{{ $student->full_name }}</td>
                                <td>{{ $student->classroom?->name ?? 'N/A' }}</td>
                                <td>{{ $assignment?->eveningTrip?->trip_name ?? 'N/A' }}</td>
                                <td>{{ $assignment?->eveningDropOffPoint?->name ?? 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach

        @if($students->count() === 0)
        <div class="settings-card mt-3">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h5 class="mt-3">No Students Found</h5>
                <p class="text-muted">No present students with transport assignments for the selected date and filters.</p>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection


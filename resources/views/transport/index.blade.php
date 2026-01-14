@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Transport</div>
                <h1>Transport Management</h1>
                <p>Manage vehicles, trips, and student assignments.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @if(auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Driver']))
                    <a href="{{ route('transport.import.form') }}" class="btn btn-settings-primary"><i class="bi bi-upload"></i> Import Assignments</a>
                @endif
                <a href="{{ route('transport.daily-list.index') }}" class="btn btn-settings-primary"><i class="bi bi-list-check"></i> Daily List</a>
                @if(auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Driver']))
                    <a href="{{ route('transport.vehicles.create') }}" class="btn btn-ghost-strong"><i class="bi bi-truck"></i> Add Vehicle</a>
                    <a href="{{ route('transport.trips.create') }}" class="btn btn-ghost-strong"><i class="bi bi-map"></i> Add Trip</a>
                @endif
                <a href="{{ route('transport.special-assignments.create') }}" class="btn btn-ghost-strong"><i class="bi bi-star"></i> Special Assignment</a>
                <a href="{{ route('transport.driver-change-requests.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-repeat"></i> Change Requests</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger mt-3">{{ session('error') }}</div>
        @endif

        {{-- Alerts Section --}}
        @if($tripsWithoutDrivers > 0 || $studentsWithoutAssignments > 0 || $activeSpecialAssignments > 0)
        <div class="settings-card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle text-warning me-2"></i> Alerts</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @if($tripsWithoutDrivers > 0)
                    <div class="col-md-4">
                        <div class="alert alert-warning mb-0">
                            <strong>{{ $tripsWithoutDrivers }}</strong> trip(s) without assigned drivers
                            <a href="{{ route('transport.trips.index') }}" class="alert-link">View trips <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                    @endif
                    @if($studentsWithoutAssignments > 0)
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0">
                            <strong>{{ $studentsWithoutAssignments }}</strong> student(s) without transport assignments
                        </div>
                    </div>
                    @endif
                    @if($activeSpecialAssignments > 0)
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0">
                            <strong>{{ $activeSpecialAssignments }}</strong> active special assignment(s)
                            <a href="{{ route('transport.special-assignments.index') }}" class="alert-link">View assignments <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Statistics Cards --}}
        <div class="row g-3 mt-3">
            <div class="col-md-3">
                <div class="settings-card">
                    <div class="card-body">
                        <div class="text-muted small">Total Vehicles</div>
                        <div class="fw-bold fs-4">{{ $vehicles->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="settings-card">
                    <div class="card-body">
                        <div class="text-muted small">Active Trips</div>
                        <div class="fw-bold fs-4">{{ $activeTrips->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="settings-card">
                    <div class="card-body">
                        <div class="text-muted small">Student Assignments</div>
                        <div class="fw-bold fs-4">{{ $assignments }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Trips --}}
        <div class="settings-card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Active Trips (with Drivers)</h5>
                <a href="{{ route('transport.trips.index') }}" class="btn btn-sm btn-ghost-strong">View All <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="card-body">
                @if($activeTrips->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>Trip Name</th>
                                    <th>Vehicle</th>
                                    <th>Driver</th>
                                    <th>Direction</th>
                                    <th>Day</th>
                                    <th>Students</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeTrips as $trip)
                                <tr>
                                    <td>{{ $trip->trip_name ?? $trip->name }}</td>
                                    <td>{{ $trip->vehicle->vehicle_number ?? '—' }}</td>
                                    <td>
                                        @if($trip->driver)
                                            {{ $trip->driver->first_name }} {{ $trip->driver->last_name }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($trip->direction)
                                            <span class="badge bg-info">{{ ucfirst($trip->direction) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($trip->day_of_week)
                                            @php
                                                $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
                                            @endphp
                                            {{ $days[$trip->day_of_week] ?? '—' }}
                                        @else
                                            <span class="text-muted">All Days</span>
                                        @endif
                                    </td>
                                    <td>{{ $trip->assignments->count() ?? 0 }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('transport.trips.edit', $trip) }}" class="btn btn-sm btn-ghost-strong">Edit</a>
                                        <a href="{{ route('transport.trip-attendance.create', ['trip' => $trip->id, 'date' => now()->toDateString()]) }}" class="btn btn-sm btn-ghost-strong">Attendance</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">No active trips with assigned drivers.</p>
                @endif
            </div>
        </div>

        {{-- Vehicles List --}}
        <div class="settings-card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Vehicles</h5>
                <a href="{{ route('transport.vehicles.create') }}" class="btn btn-sm btn-ghost-strong">Add Vehicle <i class="bi bi-plus"></i></a>
            </div>
            <div class="card-body">
                @if($vehicles->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>Vehicle Number</th>
                                    <th>Make/Model</th>
                                    <th>Type</th>
                                    <th>Capacity</th>
                                    <th>Driver Name</th>
                                    <th>Trips</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vehicles as $vehicle)
                                <tr>
                                    <td><strong>{{ $vehicle->vehicle_number }}</strong></td>
                                    <td>{{ $vehicle->make ?? '—' }} {{ $vehicle->model ?? '' }}</td>
                                    <td>{{ $vehicle->type ?? '—' }}</td>
                                    <td>{{ $vehicle->capacity ?? '—' }}</td>
                                    <td>{{ $vehicle->driver_name ?? '—' }}</td>
                                    <td>{{ $vehicle->trips->count() ?? 0 }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('transport.vehicles.edit', $vehicle) }}" class="btn btn-sm btn-ghost-strong">Edit</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">No vehicles registered. <a href="{{ route('transport.vehicles.create') }}">Add one</a></p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

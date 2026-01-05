@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Driver</div>
                <h1>My Trips</h1>
                <p>View your assigned trips and students.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <form method="GET" action="{{ route('driver.index') }}" class="d-flex gap-2">
                    <input type="date" name="date" value="{{ $selected_date }}" class="form-control" onchange="this.form.submit()">
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger mt-3">{{ session('error') }}</div>
        @endif

        @if($selected_date !== $today)
            <div class="alert alert-info mt-3">
                Viewing trips for <strong>{{ \Carbon\Carbon::parse($selected_date)->format('F d, Y') }}</strong>. 
                <a href="{{ route('driver.index', ['date' => $today]) }}" class="alert-link">View Today</a>
            </div>
        @endif

        @if($trips->count() > 0)
            <div class="row g-3 mt-3">
                @foreach($trips as $tripData)
                    @php $trip = $tripData['trip']; @endphp
                    <div class="col-md-6">
                        <div class="settings-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">{{ $trip->trip_name ?? $trip->name }}</h5>
                                    <small class="text-muted">
                                        @if($trip->direction)
                                            {{ ucfirst($trip->direction) }}
                                        @endif
                                        @if($trip->vehicle)
                                            · {{ $trip->vehicle->vehicle_number }}
                                        @endif
                                    </small>
                                </div>
                                <a href="{{ route('driver.trips.show', $trip) }}?date={{ $selected_date }}" class="btn btn-sm btn-settings-primary">View Details</a>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="text-muted small">Students Assigned</div>
                                    <div class="fw-bold fs-4">{{ $tripData['student_count'] }}</div>
                                </div>
                                
                                @if($tripData['students_by_class']->count() > 0)
                                    <div class="small">
                                        <strong>By Class:</strong>
                                        <ul class="mb-0 mt-1">
                                            @foreach($tripData['students_by_class'] as $className => $students)
                                                <li>{{ $className }}: {{ $students->count() }} student(s)</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if($trip->stops->count() > 0)
                                    <div class="mt-3">
                                        <div class="text-muted small mb-1">Stops</div>
                                        <div class="small">
                                            @foreach($trip->stops->take(3) as $stop)
                                                <div>{{ $stop->dropOffPoint->name ?? '—' }} @if($stop->estimated_time) ({{ \Carbon\Carbon::parse($stop->estimated_time)->format('H:i') }}) @endif</div>
                                            @endforeach
                                            @if($trip->stops->count() > 3)
                                                <div class="text-muted">... and {{ $trip->stops->count() - 3 }} more</div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="settings-card mt-3">
                <div class="card-body text-center py-5">
                    <i class="bi bi-calendar-x fs-1 text-muted"></i>
                    <h5 class="mt-3">No Trips Assigned</h5>
                    <p class="text-muted mb-0">You don't have any trips assigned for {{ \Carbon\Carbon::parse($selected_date)->format('F d, Y') }}.</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection



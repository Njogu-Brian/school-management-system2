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
                <h1 class="mb-1">Bulk Assign Trips</h1>
                <p class="text-muted mb-0">Assign trips to multiple students by class. Drop-off points are set during transport fee import.</p>
            </div>
            <a href="{{ route('transport.student-assignments.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <!-- Filter Form -->
        <div class="settings-card">
            <div class="card-body">
                <form method="GET" action="{{ route('transport.student-assignments.bulk-assign') }}" class="row g-3">
                    <div class="col-md-5">
                        <label for="classroom_id" class="form-label fw-semibold">Classroom <span class="text-danger">*</span></label>
                        <select name="classroom_id" id="classroom_id" class="form-select" required onchange="this.form.submit()">
                            <option value="">Select Classroom</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" {{ $selectedClassroomId == $classroom->id ? 'selected' : '' }}>
                                    {{ $classroom->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="stream_id" class="form-label fw-semibold">Stream (Optional)</label>
                        <select name="stream_id" id="stream_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Streams</option>
                            @if($selectedClassroomId)
                                @foreach ($streams->where('classroom_id', $selectedClassroomId) as $stream)
                                    <option value="{{ $stream->id }}" {{ $selectedStreamId == $stream->id ? 'selected' : '' }}>
                                        {{ $stream->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-settings-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if($students->isNotEmpty())
            <form method="POST" action="{{ route('transport.student-assignments.bulk-assign.store') }}" id="bulkAssignForm">
                @csrf
                
                <div class="settings-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Students ({{ $students->count() }}) - Assign Trips</h5>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-save"></i> Save Assignments
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30px;">#</th>
                                        <th>Student</th>
                                        <th>Drop-Off Point</th>
                                        <th>Morning Trip</th>
                                        <th>Evening Trip</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($students as $index => $student)
                                        @php
                                            $assignment = $assignments[$student->id] ?? null;
                                            $dropOffPoint = $student->dropOffPoint;
                                            $dropOffPointName = $dropOffPoint ? $dropOffPoint->name : ($student->drop_off_point_other ?? null);
                                            
                                            // Detect "own means" - check if drop-off point name contains "own" or "means" (case insensitive)
                                            $isOwnMeans = $dropOffPointName && (
                                                stripos($dropOffPointName, 'own') !== false || 
                                                stripos($dropOffPointName, 'means') !== false ||
                                                strtolower(trim($dropOffPointName)) === 'own means'
                                            );
                                            
                                            // Determine display logic:
                                            // - If "own means", show both morning and evening (can be set to own means or trips)
                                            // - If has regular drop-off point, show both morning and evening
                                            // - Special case: if has morning drop-off but evening is own means (not handled here - same drop-off for both)
                                            $showMorning = true;
                                            $showEvening = true;
                                        @endphp
                                        <tr>
                                            <td class="text-muted">{{ $index + 1 }}</td>
                                            <td class="fw-semibold">
                                                {{ $student->first_name }} {{ $student->last_name }}
                                                <br>
                                                <small class="text-muted">{{ $student->admission_number }} | {{ optional($student->stream)->name ?? 'No Stream' }}</small>
                                            </td>
                                            <td>
                                                @if($dropOffPointName)
                                                    <div>{{ $dropOffPointName }}</div>
                                                    @if($isOwnMeans)
                                                        <span class="badge bg-secondary" style="font-size: 0.7rem;">Own Means</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Not set</span>
                                                    <br>
                                                    <small class="text-muted">Set in Transport Fees</small>
                                                @endif
                                            </td>
                                            <td>
                                                <input type="hidden" name="assignments[{{ $student->id }}][student_id]" value="{{ $student->id }}">
                                                @if($showMorning)
                                                    <select name="assignments[{{ $student->id }}][morning_trip_id]" class="form-select form-select-sm">
                                                        <option value="">— Select —</option>
                                                        @if($isOwnMeans)
                                                            <option value="" {{ !$assignment || !$assignment->morning_trip_id ? 'selected' : '' }}>Own Means</option>
                                                        @endif
                                                        @foreach ($trips as $trip)
                                                            <option value="{{ $trip->id }}" {{ $assignment && $assignment->morning_trip_id == $trip->id ? 'selected' : '' }}>
                                                                {{ optional($trip->vehicle)->vehicle_number ?? 'N/A' }} - {{ $trip->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($showEvening)
                                                    <select name="assignments[{{ $student->id }}][evening_trip_id]" class="form-select form-select-sm">
                                                        <option value="">— Select —</option>
                                                        @if($isOwnMeans)
                                                            <option value="" {{ !$assignment || !$assignment->evening_trip_id ? 'selected' : '' }}>Own Means</option>
                                                        @endif
                                                        @foreach ($trips as $trip)
                                                            <option value="{{ $trip->id }}" {{ $assignment && $assignment->evening_trip_id == $trip->id ? 'selected' : '' }}>
                                                                {{ optional($trip->vehicle)->vehicle_number ?? 'N/A' }} - {{ $trip->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        @elseif($selectedClassroomId)
            <div class="settings-card">
                <div class="card-body text-center py-5">
                    <p class="text-muted">No students found with drop-off points for the selected criteria.</p>
                    <small class="text-muted">Students need to have a drop-off point set during transport fee import.</small>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

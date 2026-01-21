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
                <h1>Special Assignments</h1>
                <p>Manage special transport assignments for students.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('transport.special-assignments.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> Create Assignment
                </a>
                <a href="{{ url('/transport') }}" class="btn btn-ghost-strong">Back to Transport</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger mt-3">{{ session('error') }}</div>
        @endif

        {{-- Filters --}}
        <div class="settings-card mt-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Student</label>
                        @include('partials.student_live_search', [
                            'hiddenInputId' => 'student_id',
                            'displayInputId' => 'studentFilterSearchSpecialAssignments',
                            'resultsId' => 'studentFilterResultsSpecialAssignments',
                            'placeholder' => 'Type name or admission #',
                            'initialLabel' => request('student_id') ? (optional(\App\Models\Student::find(request('student_id')))->full_name . ' (' . optional(\App\Models\Student::find(request('student_id')))->admission_number . ')') : ''
                        ])
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <a href="{{ route('transport.special-assignments.index') }}" class="btn btn-ghost-strong w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Assignments Table --}}
        <div class="settings-card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Special Assignments</h5>
                <span class="input-chip">{{ $assignments->total() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Assignment Type</th>
                                <th>Transport Mode</th>
                                <th>Vehicle/Trip</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assignments as $assignment)
                                <tr>
                                    <td>
                                        @if($assignment->student)
                                            <strong>{{ $assignment->student->full_name }}</strong>
                                            <br><small class="text-muted">{{ $assignment->student->admission_number }}</small>
                                        @else
                                            <span class="text-muted">Vehicle-wide</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $assignment->assignment_type)) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $assignment->transport_mode)) }}</span>
                                    </td>
                                    <td>
                                        @if($assignment->transport_mode === 'vehicle' && $assignment->vehicle)
                                            {{ $assignment->vehicle->vehicle_number }}
                                        @elseif($assignment->transport_mode === 'trip' && $assignment->trip)
                                            {{ $assignment->trip->trip_name ?? $assignment->trip->name }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $assignment->start_date->format('M d, Y') }}</td>
                                    <td>{{ $assignment->end_date ? $assignment->end_date->format('M d, Y') : '—' }}</td>
                                    <td>
                                        @if($assignment->status === 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($assignment->status === 'active')
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Cancelled</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($assignment->status === 'pending' && auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Secretary']))
                                            <form action="{{ route('transport.special-assignments.approve', $assignment) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                            </form>
                                            <form action="{{ route('transport.special-assignments.reject', $assignment) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this assignment?')">Reject</button>
                                            </form>
                                        @endif
                                        @if(in_array($assignment->status, ['active', 'pending']) && auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Secretary']))
                                            <form action="{{ route('transport.special-assignments.cancel', $assignment) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Cancel this assignment?')">Cancel</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No special assignments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($assignments->hasPages())
                <div class="card-body border-top">
                    {{ $assignments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


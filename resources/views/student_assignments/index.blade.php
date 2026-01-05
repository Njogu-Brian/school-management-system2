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
                <h1 class="mb-1">Student Assignments</h1>
                <p class="text-muted mb-0">Manage which students are assigned to trips. Drop-off points are set during transport fee import.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('transport.student-assignments.bulk-assign') }}" class="btn btn-settings-primary">
                    <i class="bi bi-people"></i> Bulk Assign
                </a>
                <a href="{{ route('transport.student-assignments.create') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-plus-circle"></i> Assign Student
                </a>
            </div>
        </div>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Assignments</h5>
                <span class="input-chip">{{ $assignments->count() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Drop-Off Point</th>
                                <th>Morning Trip</th>
                                <th>Evening Trip</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assignments as $assignment)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $assignment->student->full_name ?? ($assignment->student->first_name.' '.$assignment->student->last_name) }}
                                        <br>
                                        <small class="text-muted">{{ $assignment->student->admission_number ?? 'N/A' }} | {{ optional($assignment->student->classroom)->name ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        @if($assignment->student->dropOffPoint)
                                            {{ $assignment->student->dropOffPoint->name }}
                                        @elseif($assignment->student->drop_off_point_other)
                                            {{ $assignment->student->drop_off_point_other }}
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($assignment->morningTrip)
                                            <span class="badge bg-primary">{{ optional($assignment->morningTrip->vehicle)->vehicle_number ?? 'N/A' }} - {{ $assignment->morningTrip->name }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($assignment->eveningTrip)
                                            <span class="badge bg-info">{{ optional($assignment->eveningTrip->vehicle)->vehicle_number ?? 'N/A' }} - {{ $assignment->eveningTrip->name }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-end d-flex justify-content-end gap-2">
                                        <a href="{{ route('transport.student-assignments.edit', $assignment->id) }}" class="btn btn-sm btn-ghost-strong">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('transport.student-assignments.destroy', $assignment->id) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this assignment?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-ghost-strong text-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No assignments found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

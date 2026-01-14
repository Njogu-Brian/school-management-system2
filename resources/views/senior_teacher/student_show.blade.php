@extends('layouts.app')

@push('styles')
    @include('senior_teacher.partials.styles')
@endpush

@section('content')
<div class="senior-teacher-page">
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-person me-2"></i>Student Details</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('senior_teacher.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('senior_teacher.students.index') }}">Students</a></li>
                            <li class="breadcrumb-item active">{{ $student->full_name }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('senior_teacher.students.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Student Info --}}
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="avatar-circle bg-primary text-white mx-auto mb-3" style="width: 100px; height: 100px; font-size: 36px;">
                        {{ strtoupper(substr($student->first_name, 0, 1)) }}{{ strtoupper(substr($student->last_name, 0, 1)) }}
                    </div>
                    <h4>{{ $student->full_name }}</h4>
                    <p class="text-muted mb-3">{{ $student->admission_number }}</p>
                    <span class="badge {{ $student->status === 'Active' ? 'bg-success' : 'bg-danger' }}">
                        {{ $student->status }}
                    </span>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Date of Birth</label>
                        <p class="mb-0">{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('M j, Y') : 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Gender</label>
                        <p class="mb-0">{{ $student->gender ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Class</label>
                        <p class="mb-0">{{ $student->classroom->name ?? 'N/A' }}</p>
                    </div>
                    @if($student->stream)
                        <div class="mb-3">
                            <label class="text-muted small">Stream</label>
                            <p class="mb-0">{{ $student->stream->name }}</p>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="text-muted small">Email</label>
                        <p class="mb-0">{{ $student->email ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted small">Phone</label>
                        <p class="mb-0">{{ $student->phone ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            {{-- Parent/Guardian Info --}}
            @if($student->parent)
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-people me-2"></i>Parent/Guardian</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small">Name</label>
                            <p class="mb-0"><strong>{{ $student->parent->primary_contact_name ?? 'N/A' }}</strong></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Phone</label>
                            <p class="mb-0">{{ $student->parent->primary_contact_phone ?? 'N/A' }}</p>
                        </div>
                        <div class="mb-0">
                            <label class="text-muted small">Email</label>
                            <p class="mb-0">{{ $student->parent->primary_contact_email ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Details Tabs --}}
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#attendance">
                                <i class="bi bi-calendar-check me-1"></i>Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#behaviour">
                                <i class="bi bi-exclamation-triangle me-1"></i>Behaviour
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#fees">
                                <i class="bi bi-currency-exchange me-1"></i>Fee Balance
                            </a>
                        </li>
                        @if($student->transportAssignments && $student->transportAssignments->isNotEmpty())
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#transport">
                                    <i class="bi bi-bus-front me-1"></i>Transport
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        {{-- Attendance Tab --}}
                        <div class="tab-pane fade show active" id="attendance">
                            <h5 class="mb-3">Recent Attendance (Last 30 Days)</h5>
                            @if($recentAttendance->isEmpty())
                                <p class="text-muted">No attendance records found.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Reason</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($recentAttendance as $attendance)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($attendance->date)->format('M j, Y') }}</td>
                                                    <td>
                                                        <span class="badge 
                                                            {{ $attendance->status === 'Present' ? 'bg-success' : '' }}
                                                            {{ $attendance->status === 'Absent' ? 'bg-danger' : '' }}
                                                            {{ $attendance->status === 'Late' ? 'bg-warning' : '' }}">
                                                            {{ $attendance->status }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $attendance->reason ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        {{-- Behaviour Tab --}}
                        <div class="tab-pane fade" id="behaviour">
                            <h5 class="mb-3">Recent Behaviour Records</h5>
                            @if($recentBehaviours->isEmpty())
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>No behaviour incidents recorded.
                                </div>
                            @else
                                <div class="list-group">
                                    @foreach($recentBehaviours as $behaviour)
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6>
                                                        {{ $behaviour->behaviourCategory->name ?? 'N/A' }}
                                                        <span class="badge {{ $behaviour->type === 'Positive' ? 'bg-success' : 'bg-danger' }}">
                                                            {{ $behaviour->type }}
                                                        </span>
                                                    </h6>
                                                    <p class="mb-1">{{ $behaviour->description }}</p>
                                                    <small class="text-muted">
                                                        {{ \Carbon\Carbon::parse($behaviour->incident_date)->format('M j, Y') }} 
                                                        by {{ $behaviour->staff->full_name ?? 'N/A' }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Fees Tab --}}
                        <div class="tab-pane fade" id="fees">
                            <h5 class="mb-3">Fee Balance Summary</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted">Total Invoiced</h6>
                                            <h4 class="mb-0">KES {{ number_format($feeBalance['total_invoiced'], 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-success bg-opacity-10">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted">Total Paid</h6>
                                            <h4 class="mb-0 text-success">KES {{ number_format($feeBalance['total_paid'], 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-danger bg-opacity-10">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted">Balance</h6>
                                            <h4 class="mb-0 text-danger">KES {{ number_format($feeBalance['balance'], 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Note:</strong> You can view fee balances but cannot collect fees or issue invoices. Contact the finance department for fee collection.
                            </div>
                        </div>

                        {{-- Transport Tab --}}
                        @if($student->transportAssignments && $student->transportAssignments->isNotEmpty())
                            <div class="tab-pane fade" id="transport">
                                <h5 class="mb-3">Transport Details</h5>
                                @foreach($student->transportAssignments as $assignment)
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6>Route: {{ $assignment->route->name ?? 'N/A' }}</h6>
                                            <p class="mb-1"><strong>Drop-off Point:</strong> {{ $assignment->dropOffPoint->name ?? 'N/A' }}</p>
                                            <p class="mb-0"><strong>Vehicle:</strong> {{ $assignment->route->vehicle->registration_number ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@push('styles')
    @include('senior_teacher.partials.styles')
@endpush
@endsection


@extends('layouts.app')

@push('styles')
    @include('senior_teacher.partials.styles')
@endpush

@section('content')
<div class="senior-teacher-page">
    <div class="container-fluid px-4">
        <div class="st-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2><i class="bi bi-building me-3"></i>Supervised Classrooms</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('senior_teacher.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Supervised Classrooms</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        @if($classrooms->isEmpty())
            <div class="st-card">
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h4 class="mb-3">No Supervised Classrooms</h4>
                    <p class="text-muted mb-4">You haven't been assigned any classrooms to supervise yet.</p>
                    <a href="{{ route('senior_teacher.dashboard') }}" class="btn btn-st-primary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        @else
            <div class="st-card">
                <div class="st-card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-building-fill me-2"></i>Supervised Classrooms by Stream</span>
                </div>
                <div class="st-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    <th>Students</th>
                                    <th>Assigned Teachers</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($streamRows as $row)
                                    <tr>
                                        <td><strong>{{ $row->classroom->name }}</strong></td>
                                        <td>{{ $row->stream ? $row->stream->name : '—' }}</td>
                                        <td><span class="badge bg-info">{{ $row->student_count }} students</span></td>
                                        <td>
                                            @if($row->classroom->teachers->isNotEmpty())
                                                @foreach($row->classroom->teachers->take(2) as $teacher)
                                                    <span class="badge bg-light text-dark border">{{ $teacher->name }}</span>
                                                @endforeach
                                                @if($row->classroom->teachers->count() > 2)
                                                    <span class="badge bg-light text-dark border">+{{ $row->classroom->teachers->count() - 2 }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('senior_teacher.students.index') }}?classroom_id={{ $row->classroom->id }}{{ $row->stream ? '&stream_id='.$row->stream->id : '' }}" 
                                                   class="btn btn-outline-primary" title="View Students">
                                                    <i class="bi bi-people"></i>
                                                </a>
                                                <a href="{{ route('attendance.mark.form') }}?class={{ $row->classroom->id }}{{ $row->stream ? '&stream='.$row->stream->id : '' }}" 
                                                   class="btn btn-outline-success" title="Mark Attendance">
                                                    <i class="bi bi-calendar-check"></i>
                                                </a>
                                                <a href="{{ route('senior_teacher.fee_balances') }}?classroom_id={{ $row->classroom->id }}{{ $row->stream ? '&stream_id='.$row->stream->id : '' }}" 
                                                   class="btn btn-outline-info" title="Fee Balances">
                                                    <i class="bi bi-currency-exchange"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

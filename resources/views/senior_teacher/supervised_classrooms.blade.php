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
            <div class="row g-4">
                @foreach($classrooms as $classroom)
                    <div class="col-md-6 col-lg-4">
                        <div class="st-card h-100">
                            <div class="st-card-header">
                                <i class="bi bi-building-fill me-2"></i>{{ $classroom->name }}
                            </div>
                            <div class="st-card-body">
                                <div class="mb-4">
                                    <div class="st-info-item">
                                        <span class="text-muted"><i class="bi bi-people me-2"></i>Students</span>
                                        <span class="st-badge st-badge-info">{{ $classroom->students_count }}</span>
                                    </div>
                                    <div class="st-info-item">
                                        <span class="text-muted"><i class="bi bi-calendar me-2"></i>Academic Year</span>
                                        <span class="st-badge st-badge-secondary">{{ $classroom->academicYear->name ?? 'N/A' }}</span>
                                    </div>
                                    @if($classroom->teachers->isNotEmpty())
                                        <div class="mt-4">
                                            <span class="text-muted d-block mb-2"><i class="bi bi-person-badge me-2"></i>Assigned Teachers</span>
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach($classroom->teachers->take(3) as $teacher)
                                                    <span class="badge bg-light text-dark border">{{ $teacher->name }}</span>
                                                @endforeach
                                                @if($classroom->teachers->count() > 3)
                                                    <span class="badge bg-light text-dark border">+{{ $classroom->teachers->count() - 3 }} more</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="{{ route('senior_teacher.students.index') }}?classroom_id={{ $classroom->id }}" 
                                       class="btn btn-st-outline btn-sm">
                                        <i class="bi bi-people me-2"></i>View Students
                                    </a>
                                    <a href="{{ route('attendance.records') }}?classroom_id={{ $classroom->id }}" 
                                       class="btn btn-outline-success btn-sm">
                                        <i class="bi bi-calendar-check me-2"></i>View Attendance
                                    </a>
                                    <a href="{{ route('senior_teacher.fee_balances') }}?classroom_id={{ $classroom->id }}" 
                                       class="btn btn-outline-info btn-sm">
                                        <i class="bi bi-currency-exchange me-2"></i>Fee Balances
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

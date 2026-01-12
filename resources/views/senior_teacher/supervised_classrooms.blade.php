@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-building me-2"></i>Supervised Classrooms</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('senior_teacher.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Supervised Classrooms</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    @if($classrooms->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <h4 class="mt-3 text-muted">No Supervised Classrooms</h4>
                <p class="text-muted">You haven't been assigned any classrooms to supervise yet.</p>
                <a href="{{ route('senior_teacher.dashboard') }}" class="btn btn-primary mt-3">
                    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($classrooms as $classroom)
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">{{ $classroom->name }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted"><i class="bi bi-people me-2"></i>Students</span>
                                    <span class="badge bg-info">{{ $classroom->students_count }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted"><i class="bi bi-calendar me-2"></i>Academic Year</span>
                                    <span class="badge bg-secondary">{{ $classroom->academicYear->name ?? 'N/A' }}</span>
                                </div>
                                @if($classroom->teachers->isNotEmpty())
                                    <div class="mt-3">
                                        <span class="text-muted d-block mb-2"><i class="bi bi-person-badge me-2"></i>Assigned Teachers</span>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($classroom->teachers->take(3) as $teacher)
                                                <span class="badge bg-light text-dark">{{ $teacher->name }}</span>
                                            @endforeach
                                            @if($classroom->teachers->count() > 3)
                                                <span class="badge bg-light text-dark">+{{ $classroom->teachers->count() - 3 }} more</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="{{ route('senior_teacher.students.index') }}?classroom_id={{ $classroom->id }}" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-people me-1"></i>View Students
                                </a>
                                <a href="{{ route('attendance.records') }}?classroom_id={{ $classroom->id }}" 
                                   class="btn btn-outline-success btn-sm">
                                    <i class="bi bi-calendar-check me-1"></i>View Attendance
                                </a>
                                <a href="{{ route('senior_teacher.fee_balances') }}?classroom_id={{ $classroom->id }}" 
                                   class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-currency-exchange me-1"></i>Fee Balances
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection


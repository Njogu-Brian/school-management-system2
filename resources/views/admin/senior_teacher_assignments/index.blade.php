@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-2">
                <i class="bi bi-person-badge text-primary me-2"></i>Senior Teacher Assignments
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Senior Teacher Assignments</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Info Card --}}
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <div class="d-flex align-items-start">
            <i class="bi bi-info-circle-fill fs-4 me-3 mt-1"></i>
            <div>
                <h6 class="alert-heading mb-2">About Senior Teacher Role</h6>
                <p class="mb-0">
                    Senior Teachers can supervise specific classrooms and staff members. 
                    They have teacher permissions plus the ability to view comprehensive data for their supervised classes and monitor their assigned staff.
                </p>
            </div>
        </div>
    </div>

    @if($seniorTeachers->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <h4 class="mt-3 text-muted">No Senior Teachers</h4>
                <p class="text-muted">No users have been assigned the Senior Teacher role yet.</p>
                <a href="{{ route('staff.index') }}" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-circle me-1"></i>Manage Staff & Roles
                </a>
            </div>
        </div>
    @else
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">
                        <i class="bi bi-people-fill text-primary me-2"></i>Senior Teachers
                    </h5>
                    <span class="badge bg-primary rounded-pill px-3 py-2">{{ $seniorTeachers->count() }} {{ Str::plural('Teacher', $seniorTeachers->count()) }}</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="py-3">Email</th>
                                <th class="py-3">Supervised Classrooms</th>
                                <th class="py-3">Supervised Staff</th>
                                <th class="py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($seniorTeachers as $teacher)
                                <tr>
                                    <td class="px-4">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary bg-gradient text-white d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 45px; height: 45px; font-weight: 600; font-size: 16px;">
                                                {{ strtoupper(substr($teacher->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark">{{ $teacher->name }}</div>
                                                @if($teacher->staff)
                                                    <small class="text-muted">
                                                        <i class="bi bi-briefcase me-1"></i>{{ $teacher->staff->position->name ?? 'Staff' }}
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="mailto:{{ $teacher->email }}" class="text-decoration-none text-muted">
                                            <i class="bi bi-envelope me-1"></i>{{ $teacher->email }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($teacher->supervisedClassrooms->isNotEmpty())
                                            <div>
                                                <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 mb-2">
                                                    <i class="bi bi-building me-1"></i>{{ $teacher->supervisedClassrooms->count() }} {{ Str::plural('Class', $teacher->supervisedClassrooms->count()) }}
                                                </span>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($teacher->supervisedClassrooms->take(3) as $classroom)
                                                        <span class="badge bg-light text-dark border">{{ $classroom->name }}</span>
                                                    @endforeach
                                                    @if($teacher->supervisedClassrooms->count() > 3)
                                                        <span class="badge bg-light text-muted border">+{{ $teacher->supervisedClassrooms->count() - 3 }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted"><i class="bi bi-dash-circle me-1"></i>None assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($teacher->supervisedStaff->isNotEmpty())
                                            <span class="badge bg-success bg-opacity-10 text-success px-2 py-1">
                                                <i class="bi bi-person-check me-1"></i>{{ $teacher->supervisedStaff->count() }} {{ Str::plural('Staff', $teacher->supervisedStaff->count()) }}
                                            </span>
                                        @else
                                            <span class="text-muted"><i class="bi bi-dash-circle me-1"></i>None assigned</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.senior_teacher_assignments.edit', $teacher->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil me-1"></i>Manage
                                        </a>
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
@endsection


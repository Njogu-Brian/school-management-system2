@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-person-badge me-2"></i>Senior Teacher Assignments</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Senior Teacher Assignments</li>
                        </ol>
                    </nav>
                </div>
            </div>
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

    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <strong>About Senior Teacher Role:</strong> Senior Teachers can supervise specific classrooms and staff members. 
        They have teacher permissions plus the ability to view comprehensive data for their supervised classes and monitor their assigned staff.
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
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Senior Teachers</h5>
                <span class="badge bg-primary">{{ $seniorTeachers->count() }} Senior Teachers</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Supervised Classrooms</th>
                                <th>Supervised Staff</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($seniorTeachers as $teacher)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary text-white me-2" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                                                {{ strtoupper(substr($teacher->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <strong>{{ $teacher->name }}</strong>
                                                @if($teacher->staff)
                                                    <br><small class="text-muted">{{ $teacher->staff->position->name ?? 'Staff' }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $teacher->email }}</td>
                                    <td>
                                        @if($teacher->supervisedClassrooms->isNotEmpty())
                                            <span class="badge bg-primary">{{ $teacher->supervisedClassrooms->count() }} Classes</span>
                                            <div class="mt-1">
                                                @foreach($teacher->supervisedClassrooms->take(2) as $classroom)
                                                    <small class="badge bg-light text-dark me-1">{{ $classroom->name }}</small>
                                                @endforeach
                                                @if($teacher->supervisedClassrooms->count() > 2)
                                                    <small class="text-muted">+{{ $teacher->supervisedClassrooms->count() - 2 }} more</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($teacher->supervisedStaff->isNotEmpty())
                                            <span class="badge bg-success">{{ $teacher->supervisedStaff->count() }} Staff</span>
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.senior_teacher_assignments.edit', $teacher->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Manage Assignments
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


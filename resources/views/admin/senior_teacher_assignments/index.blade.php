@extends('layouts.app')

@push('styles')
<style>
    .admin-page {
        --admin-primary: #6366f1;
        --admin-primary-dark: #4f46e5;
        --admin-accent: #818cf8;
        --admin-bg: #f8fafc;
        --admin-surface: #ffffff;
        --admin-border: #e2e8f0;
        --admin-text: #0f172a;
        --admin-muted: #64748b;
        background: var(--admin-bg);
        min-height: 100vh;
    }
    
    body.theme-dark .admin-page {
        --admin-bg: #0f172a;
        --admin-surface: #1e293b;
        --admin-border: #334155;
        --admin-text: #f1f5f9;
        --admin-muted: #94a3b8;
    }
    
    .admin-hero {
        background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-accent) 100%);
        color: white;
        border-radius: 16px;
        padding: 24px 28px;
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
        margin-bottom: 24px;
    }
    
    .admin-hero h2 { margin: 0; font-weight: 700; }
    .admin-hero .breadcrumb { margin: 8px 0 0; background: transparent; padding: 0; }
    .admin-hero .breadcrumb-item, .admin-hero .breadcrumb-item a { color: rgba(255,255,255,0.9); }
    .admin-hero .breadcrumb-item.active { color: rgba(255,255,255,0.7); }
    .admin-hero .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,0.6); }
    
    .admin-card {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .admin-card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .admin-card-header {
        background: linear-gradient(90deg, rgba(99, 102, 241, 0.08), rgba(129, 140, 248, 0.06));
        border-bottom: 1px solid var(--admin-border);
        padding: 16px 20px;
        font-weight: 700;
        color: var(--admin-primary);
    }
    
    .admin-info-card {
        background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
        border: 1px solid #93c5fd;
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 24px;
    }
    
    body.theme-dark .admin-info-card {
        background: linear-gradient(135deg, #1e3a5f 0%, #2e3852 100%);
        border-color: #3b82f6;
    }
    
    .admin-table {
        width: 100%;
        border-collapse: collapse;
        color: var(--admin-text);
    }
    
    .admin-table thead th {
        background: linear-gradient(90deg, rgba(99, 102, 241, 0.06), rgba(129, 140, 248, 0.04));
        border-bottom: 2px solid var(--admin-border);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 14px 16px;
    }
    
    .admin-table td {
        padding: 16px;
        border-bottom: 1px solid var(--admin-border);
        vertical-align: middle;
    }
    
    .admin-table tbody tr {
        transition: background-color 0.2s ease;
    }
    
    .admin-table tbody tr:hover {
        background: rgba(99, 102, 241, 0.04);
    }
    
    .btn-admin-primary {
        background: linear-gradient(135deg, var(--admin-primary), var(--admin-accent));
        border: none;
        color: white;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 10px;
        box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3);
        transition: all 0.2s ease;
    }
    
    .btn-admin-primary:hover {
        background: linear-gradient(135deg, var(--admin-primary-dark), var(--admin-primary));
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(99, 102, 241, 0.4);
    }
    
    .btn-admin-outline {
        background: white;
        border: 2px solid var(--admin-primary);
        color: var(--admin-primary);
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 10px;
        transition: all 0.2s ease;
    }
    
    .btn-admin-outline:hover {
        background: var(--admin-primary);
        color: white;
    }
    
    .avatar-circle {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--admin-primary), var(--admin-accent));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 18px;
        box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3);
    }
    
    .admin-badge {
        padding: 6px 12px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.75rem;
    }
    
    .admin-badge-primary {
        background: rgba(99, 102, 241, 0.1);
        color: var(--admin-primary);
        border: 1px solid rgba(99, 102, 241, 0.2);
    }
    
    .admin-badge-success {
        background: rgba(34, 197, 94, 0.1);
        color: #16a34a;
        border: 1px solid rgba(34, 197, 94, 0.2);
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-state i {
        font-size: 72px;
        color: var(--admin-muted);
        opacity: 0.5;
        margin-bottom: 20px;
    }
</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="container-fluid px-4 py-4">
        {{-- Page Header --}}
        <div class="admin-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2 class="mb-0">
                        <i class="bi bi-person-badge me-2"></i>Senior Teacher Assignments
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Senior Teacher Assignments</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Info Card --}}
        <div class="admin-info-card">
            <div class="d-flex align-items-start">
                <i class="bi bi-info-circle-fill fs-4 me-3 mt-1 text-primary"></i>
                <div>
                    <h6 class="fw-bold mb-2">About Senior Teacher Role</h6>
                    <p class="mb-0">
                        Senior Teachers can supervise specific classrooms and staff members. 
                        They have teacher permissions plus the ability to view comprehensive data for their supervised classes and monitor their assigned staff.
                    </p>
                </div>
            </div>
        </div>

        @if($seniorTeachers->isEmpty())
            <div class="admin-card">
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h4 class="mb-2">No Senior Teachers</h4>
                    <p class="text-muted mb-4">No users have been assigned the Senior Teacher role yet.</p>
                    <a href="{{ route('staff.index') }}" class="btn btn-admin-primary">
                        <i class="bi bi-plus-circle me-2"></i>Manage Staff & Roles
                    </a>
                </div>
            </div>
        @else
            <div class="admin-card">
                <div class="admin-card-header">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <span>
                            <i class="bi bi-people-fill me-2"></i>Senior Teachers
                        </span>
                        <span class="badge bg-primary rounded-pill px-3 py-2">{{ $seniorTeachers->count() }} {{ Str::plural('Teacher', $seniorTeachers->count()) }}</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Supervised Classrooms</th>
                                <th>Supervised Staff</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($seniorTeachers as $teacher)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3">
                                                {{ strtoupper(substr($teacher->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $teacher->name }}</div>
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
                                                <span class="admin-badge admin-badge-primary mb-2 d-inline-block">
                                                    <i class="bi bi-building me-1"></i>{{ $teacher->supervisedClassrooms->count() }} {{ Str::plural('Class', $teacher->supervisedClassrooms->count()) }}
                                                </span>
                                                <div class="d-flex flex-wrap gap-1 mt-1">
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
                                            <span class="admin-badge admin-badge-success">
                                                <i class="bi bi-person-check me-1"></i>{{ $teacher->supervisedStaff->count() }} {{ Str::plural('Staff', $teacher->supervisedStaff->count()) }}
                                            </span>
                                        @else
                                            <span class="text-muted"><i class="bi bi-dash-circle me-1"></i>None assigned</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.senior_teacher_assignments.edit', $teacher->id) }}" 
                                           class="btn btn-sm btn-admin-outline">
                                            <i class="bi bi-pencil me-1"></i>Manage
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection


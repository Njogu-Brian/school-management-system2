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
        margin-bottom: 24px;
    }
    
    .admin-card-header {
        padding: 18px 24px;
        font-weight: 700;
        font-size: 1.1rem;
        border-bottom: 1px solid var(--admin-border);
    }
    
    .admin-card-header-primary {
        background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-accent) 100%);
        color: white;
    }
    
    .admin-card-header-success {
        background: linear-gradient(135deg, #22c55e 0%, #10b981 100%);
        color: white;
    }
    
    .admin-card-body {
        padding: 24px;
    }
    
    .btn-admin-primary {
        background: linear-gradient(135deg, var(--admin-primary), var(--admin-accent));
        border: none;
        color: white;
        font-weight: 600;
        padding: 12px 24px;
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
    
    .btn-admin-success {
        background: linear-gradient(135deg, #22c55e, #10b981);
        border: none;
        color: white;
        font-weight: 600;
        padding: 12px 24px;
        border-radius: 10px;
        box-shadow: 0 4px 6px -1px rgba(34, 197, 94, 0.3);
        transition: all 0.2s ease;
    }
    
    .btn-admin-success:hover {
        background: linear-gradient(135deg, #16a34a, #15803d);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(34, 197, 94, 0.4);
    }
    
    .btn-admin-outline {
        background: white;
        border: 2px solid var(--admin-border);
        color: var(--admin-text);
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 10px;
        transition: all 0.2s ease;
    }
    
    .btn-admin-outline:hover {
        background: var(--admin-bg);
        border-color: var(--admin-primary);
        color: var(--admin-primary);
    }
    
    .avatar-circle-lg {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--admin-primary), var(--admin-accent));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 28px;
        box-shadow: 0 6px 12px -1px rgba(99, 102, 241, 0.4);
    }
    
    .stat-card {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px -1px rgba(0, 0, 0, 0.1);
    }
    
    .stat-card-primary {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(129, 140, 248, 0.05));
        border-color: rgba(99, 102, 241, 0.2);
    }
    
    .stat-card-success {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(16, 185, 129, 0.05));
        border-color: rgba(34, 197, 94, 0.2);
    }
    
    .form-check-input:checked {
        background-color: var(--admin-primary);
        border-color: var(--admin-primary);
    }
    
    .checkbox-container {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid var(--admin-border);
        border-radius: 12px;
        padding: 16px;
        background: var(--admin-bg);
    }
    
    .form-check {
        padding: 10px;
        border-radius: 8px;
        transition: background 0.2s ease;
    }
    
    .form-check:hover {
        background: rgba(99, 102, 241, 0.05);
    }
    
    .list-group-item {
        border-color: var(--admin-border);
        transition: all 0.2s ease;
    }
    
    .list-group-item:hover {
        background: rgba(99, 102, 241, 0.03);
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
                        <i class="bi bi-pencil-square me-2"></i>Manage Senior Teacher Assignments
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.senior_teacher_assignments.index') }}">Senior Teacher Assignments</a></li>
                            <li class="breadcrumb-item active">{{ $seniorTeacher->name }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.senior_teacher_assignments.index') }}" class="btn btn-admin-outline">
                        <i class="bi bi-arrow-left me-2"></i>Back to List
                    </a>
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

        {{-- Senior Teacher Info Card --}}
        <div class="admin-card">
            <div class="admin-card-body">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle-lg me-4">
                                {{ strtoupper(substr($seniorTeacher->name, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="mb-2 fw-bold">{{ $seniorTeacher->name }}</h3>
                                <p class="text-muted mb-2">
                                    <i class="bi bi-envelope me-1"></i>{{ $seniorTeacher->email }}
                                </p>
                                <div class="d-flex gap-2 flex-wrap">
                                    @if($seniorTeacher->staff)
                                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2">
                                            <i class="bi bi-briefcase me-1"></i>{{ $seniorTeacher->staff->position->name ?? 'Staff' }}
                                        </span>
                                    @endif
                                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-2">
                                        <i class="bi bi-award me-1"></i>Senior Teacher
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 mt-4 mt-md-0">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stat-card stat-card-primary">
                                    <div class="display-6 fw-bold text-primary mb-2">{{ $seniorTeacher->supervisedClassrooms->count() }}</div>
                                    <small class="text-muted fw-semibold">
                                        <i class="bi bi-building me-1"></i>Supervised Classes
                                    </small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card stat-card-success">
                                    <div class="display-6 fw-bold text-success mb-2">{{ $seniorTeacher->supervisedStaff->count() }}</div>
                                    <small class="text-muted fw-semibold">
                                        <i class="bi bi-person-check me-1"></i>Supervised Staff
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Supervised Classrooms --}}
            <div class="col-lg-6">
                <div class="admin-card h-100">
                    <div class="admin-card-header admin-card-header-primary">
                        <i class="bi bi-building-fill me-2"></i>Supervised Classrooms
                    </div>
                    <div class="admin-card-body">
                    <form action="{{ route('admin.senior_teacher_assignments.update_classrooms', $seniorTeacher->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-3">Select Classrooms to Supervise</label>
                            <div class="checkbox-container">
                                @foreach($allClassrooms as $classroom)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="classroom_ids[]" 
                                               value="{{ $classroom->id }}"
                                               id="classroom_{{ $classroom->id }}"
                                               {{ $seniorTeacher->supervisedClassrooms->contains($classroom->id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="classroom_{{ $classroom->id }}">
                                            {{ $classroom->name }}
                                            <span class="badge bg-light text-dark">{{ $classroom->students->count() }} students</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-admin-primary w-100">
                            <i class="bi bi-check-circle me-2"></i>Update Supervised Classrooms
                        </button>
                    </form>

                    @if($seniorTeacher->supervisedClassrooms->isNotEmpty())
                        <hr class="my-4">
                        <h6 class="mb-3 fw-bold"><i class="bi bi-list-check me-2"></i>Currently Supervising:</h6>
                        <div class="list-group">
                            @foreach($seniorTeacher->supervisedClassrooms as $classroom)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $classroom->name }}</strong>
                                        <br><small class="text-muted">{{ $classroom->students->count() }} students</small>
                                    </div>
                                    <form action="{{ route('admin.senior_teacher_assignments.remove_classroom', [$seniorTeacher->id, $classroom->id]) }}" 
                                          method="POST" 
                                          onsubmit="return confirm('Remove this classroom from supervision?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

            {{-- Supervised Staff --}}
            <div class="col-lg-6">
                <div class="admin-card h-100">
                    <div class="admin-card-header admin-card-header-success">
                        <i class="bi bi-person-badge-fill me-2"></i>Supervised Staff
                    </div>
                    <div class="admin-card-body">
                    <form action="{{ route('admin.senior_teacher_assignments.update_staff', $seniorTeacher->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-3">Select Staff to Supervise</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="staffSearch" placeholder="🔍 Search staff by name..." style="border-radius: 10px; padding: 12px 16px;">
                            </div>
                            <div class="checkbox-container" id="staffList">
                                @foreach($allStaff as $staff)
                                    <div class="form-check mb-2 staff-item" data-staff-name="{{ strtolower($staff->full_name) }}">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="staff_ids[]" 
                                               value="{{ $staff->id }}"
                                               id="staff_{{ $staff->id }}"
                                               {{ $seniorTeacher->supervisedStaff->contains($staff->id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="staff_{{ $staff->id }}">
                                            {{ $staff->full_name }}
                                            <span class="badge bg-light text-dark">{{ $staff->position->name ?? 'N/A' }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-admin-success w-100">
                            <i class="bi bi-check-circle me-2"></i>Update Supervised Staff
                        </button>
                    </form>

                    @if($seniorTeacher->supervisedStaff->isNotEmpty())
                        <hr class="my-4">
                        <h6 class="mb-3 fw-bold"><i class="bi bi-list-check me-2"></i>Currently Supervising:</h6>
                        <div class="list-group">
                            @foreach($seniorTeacher->supervisedStaff as $staff)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $staff->full_name }}</strong>
                                        <br><small class="text-muted">{{ $staff->position->name ?? 'N/A' }}</small>
                                    </div>
                                    <form action="{{ route('admin.senior_teacher_assignments.remove_staff', [$seniorTeacher->id, $staff->id]) }}" 
                                          method="POST" 
                                          onsubmit="return confirm('Remove this staff from supervision?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
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

@push('scripts')
<script>
    // Staff search functionality
    document.getElementById('staffSearch').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const staffItems = document.querySelectorAll('.staff-item');
        
        staffItems.forEach(item => {
            const staffName = item.getAttribute('data-staff-name');
            if (staffName.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
</script>
@endpush
@endsection


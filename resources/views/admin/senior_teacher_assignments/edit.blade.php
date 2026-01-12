@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-pencil me-2"></i>Manage Assignments</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.senior_teacher_assignments.index') }}">Senior Teacher Assignments</a></li>
                            <li class="breadcrumb-item active">{{ $seniorTeacher->name }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.senior_teacher_assignments.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Back to List
                    </a>
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

    {{-- Senior Teacher Info --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle bg-primary text-white me-3" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                            {{ strtoupper(substr($seniorTeacher->name, 0, 2)) }}
                        </div>
                        <div>
                            <h4 class="mb-1">{{ $seniorTeacher->name }}</h4>
                            <p class="text-muted mb-0">{{ $seniorTeacher->email }}</p>
                            @if($seniorTeacher->staff)
                                <span class="badge bg-light text-dark">{{ $seniorTeacher->staff->position->name ?? 'Staff' }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-2 bg-light rounded">
                                <div class="fs-4 fw-bold text-primary">{{ $seniorTeacher->supervisedClassrooms->count() }}</div>
                                <small class="text-muted">Supervised Classes</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-light rounded">
                                <div class="fs-4 fw-bold text-success">{{ $seniorTeacher->supervisedStaff->count() }}</div>
                                <small class="text-muted">Supervised Staff</small>
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
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-building me-2"></i>Supervised Classrooms</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.senior_teacher_assignments.update_classrooms', $seniorTeacher->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label class="form-label">Select Classrooms to Supervise</label>
                            <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
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
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle me-1"></i>Update Supervised Classrooms
                        </button>
                    </form>

                    @if($seniorTeacher->supervisedClassrooms->isNotEmpty())
                        <hr>
                        <h6 class="mb-3">Currently Supervising:</h6>
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
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Supervised Staff</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.senior_teacher_assignments.update_staff', $seniorTeacher->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label class="form-label">Select Staff to Supervise</label>
                            <div class="mb-2">
                                <input type="text" class="form-control" id="staffSearch" placeholder="Search staff...">
                            </div>
                            <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;" id="staffList">
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
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle me-1"></i>Update Supervised Staff
                        </button>
                    </form>

                    @if($seniorTeacher->supervisedStaff->isNotEmpty())
                        <hr>
                        <h6 class="mb-3">Currently Supervising:</h6>
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


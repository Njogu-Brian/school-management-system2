@extends('layouts.app')

@push('styles')
<style>
    .senior-teacher-page {
        --st-primary: #8b5cf6;
        --st-primary-dark: #7c3aed;
        --st-accent: #a78bfa;
        --st-bg: #faf5ff;
        --st-surface: #ffffff;
        --st-border: #e9d5ff;
        --st-text: #1e293b;
        --st-muted: #64748b;
        background: var(--st-bg);
        min-height: 100vh;
        padding: 24px 0;
    }
    
    body.theme-dark .senior-teacher-page {
        --st-bg: #1e1b4b;
        --st-surface: #312e81;
        --st-border: #4c1d95;
        --st-text: #f1f5f9;
        --st-muted: #cbd5e1;
    }
    
    .st-hero {
        background: linear-gradient(135deg, var(--st-primary) 0%, var(--st-accent) 100%);
        color: white;
        border-radius: 18px;
        padding: 28px 32px;
        box-shadow: 0 10px 25px rgba(139, 92, 246, 0.25);
        margin-bottom: 32px;
    }
    
    .st-hero h2 { margin: 0; font-weight: 700; font-size: 2rem; }
    .st-hero .breadcrumb { margin: 10px 0 0; background: transparent; padding: 0; }
    .st-hero .breadcrumb-item, .st-hero .breadcrumb-item a { color: rgba(255,255,255,0.95); text-decoration: none; }
    .st-hero .breadcrumb-item.active { color: rgba(255,255,255,0.75); }
    .st-hero .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,0.6); }
    
    .st-card {
        background: var(--st-surface);
        border: 1px solid var(--st-border);
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .st-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -1px rgba(0, 0, 0, 0.12), 0 4px 6px -1px rgba(0, 0, 0, 0.08);
    }
    
    .st-card-header {
        background: linear-gradient(135deg, var(--st-primary) 0%, var(--st-accent) 100%);
        color: white;
        padding: 18px 24px;
        font-weight: 700;
        font-size: 1.15rem;
        border: none;
    }
    
    .st-card-body {
        padding: 24px;
    }
    
    .st-info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid var(--st-border);
    }
    
    .st-info-item:last-child {
        border-bottom: none;
    }
    
    .st-badge {
        padding: 6px 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.8rem;
    }
    
    .st-badge-info {
        background: rgba(139, 92, 246, 0.15);
        color: var(--st-primary);
    }
    
    .st-badge-secondary {
        background: rgba(100, 116, 139, 0.15);
        color: var(--st-muted);
    }
    
    .btn-st-outline {
        background: white;
        border: 2px solid var(--st-primary);
        color: var(--st-primary);
        font-weight: 600;
        padding: 10px 18px;
        border-radius: 10px;
        transition: all 0.2s ease;
    }
    
    .btn-st-outline:hover {
        background: var(--st-primary);
        color: white;
        transform: translateY(-1px);
    }
    
    .btn-st-primary {
        background: linear-gradient(135deg, var(--st-primary), var(--st-accent));
        border: none;
        color: white;
        font-weight: 600;
        padding: 12px 24px;
        border-radius: 10px;
        box-shadow: 0 4px 6px -1px rgba(139, 92, 246, 0.3);
        transition: all 0.2s ease;
    }
    
    .btn-st-primary:hover {
        background: linear-gradient(135deg, var(--st-primary-dark), var(--st-primary));
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 6px 8px -1px rgba(139, 92, 246, 0.4);
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-state i {
        font-size: 80px;
        color: var(--st-muted);
        opacity: 0.4;
        margin-bottom: 24px;
    }
</style>
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

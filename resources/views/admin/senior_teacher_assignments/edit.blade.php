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
</style>
@endpush

@section('content')
<div class="admin-page">
    <div class="container-fluid px-4 py-4">
        <div class="admin-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Manage Senior Teacher — Campus Only
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
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header admin-card-header-primary">
                <i class="bi bi-geo-alt-fill me-2"></i>Campus Assignment
            </div>
            <div class="admin-card-body">
                <form action="{{ route('admin.senior_teacher_assignments.update_campus', $seniorTeacher->id) }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    @method('PUT')
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Assign Campus</label>
                        <select name="campus" class="form-select" required>
                            <option value="">Select campus</option>
                            <option value="lower" {{ ($campusAssignment?->campus === 'lower') ? 'selected' : '' }}>Lower Campus (Grade 4-9)</option>
                            <option value="upper" {{ ($campusAssignment?->campus === 'upper') ? 'selected' : '' }}>Upper Campus (Creche - Grade 3)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-admin-primary w-100">
                            <i class="bi bi-check-circle me-2"></i>Save Campus
                        </button>
                    </div>
                </form>
                <p class="text-muted mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>Each campus has one senior teacher. Supervision scope is the whole campus: all classrooms and staff on that campus.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

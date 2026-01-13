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
    }
    
    .st-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .st-table thead th {
        background: linear-gradient(90deg, rgba(139, 92, 246, 0.08), rgba(167, 139, 250, 0.05));
        border-bottom: 2px solid var(--st-border);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 16px;
        color: var(--st-primary);
    }
    
    .st-table td {
        padding: 18px 16px;
        border-bottom: 1px solid var(--st-border);
        vertical-align: middle;
    }
    
    .st-table tbody tr {
        transition: background-color 0.2s ease;
    }
    
    .st-table tbody tr:hover {
        background: rgba(139, 92, 246, 0.04);
    }
    
    .avatar-circle {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--st-primary), var(--st-accent));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        box-shadow: 0 4px 6px -1px rgba(139, 92, 246, 0.3);
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
    
    .st-info-alert {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(96, 165, 250, 0.05));
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 12px;
        padding: 18px 20px;
        margin-top: 24px;
    }
</style>
@endpush

@section('content')
<div class="senior-teacher-page">
    <div class="container-fluid px-4">
        <div class="st-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2><i class="bi bi-person-badge me-3"></i>Supervised Staff</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('senior_teacher.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Supervised Staff</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        @if($staff->isEmpty())
            <div class="st-card">
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h4 class="mb-3">No Supervised Staff</h4>
                    <p class="text-muted mb-4">You haven't been assigned any staff members to supervise yet.</p>
                    <a href="{{ route('senior_teacher.dashboard') }}" class="btn btn-st-primary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        @else
            <div class="st-card">
                <div class="table-responsive">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($staff as $member)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3">
                                                {{ strtoupper(substr($member->first_name, 0, 1)) }}{{ strtoupper(substr($member->last_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <strong class="d-block">{{ $member->full_name }}</strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $member->position->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        {{ $member->department->name ?? 'N/A' }}
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $member->user->email ?? $member->email ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $member->phone ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $member->status === 'Active' ? 'bg-success' : 'bg-danger' }} rounded-pill px-3 py-2">
                                            {{ $member->status ?? 'Unknown' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="st-info-alert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-info-circle-fill fs-5 me-3 text-primary"></i>
                    <div>
                        <strong>Note:</strong> As a Senior Teacher, you can view staff information but cannot modify their HR details.
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

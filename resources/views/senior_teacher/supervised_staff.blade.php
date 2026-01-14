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
                                        <small class="text-muted">{{ $member->phone_number ?? 'N/A' }}</small>
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

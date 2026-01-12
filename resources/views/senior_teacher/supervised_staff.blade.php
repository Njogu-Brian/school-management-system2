@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-person-badge me-2"></i>Supervised Staff</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('senior_teacher.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Supervised Staff</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    @if($staff->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <h4 class="mt-3 text-muted">No Supervised Staff</h4>
                <p class="text-muted">You haven't been assigned any staff members to supervise yet.</p>
                <a href="{{ route('senior_teacher.dashboard') }}" class="btn btn-primary mt-3">
                    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
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
                                            <div class="avatar-circle bg-primary text-white me-2">
                                                {{ strtoupper(substr($member->first_name, 0, 1)) }}{{ strtoupper(substr($member->last_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <strong>{{ $member->full_name }}</strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $member->position->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        {{ $member->department->name ?? 'N/A' }}
                                    </td>
                                    <td>
                                        <small>{{ $member->user->email ?? $member->email ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $member->phone ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $member->status === 'Active' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $member->status ?? 'Unknown' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Note:</strong> As a Senior Teacher, you can view staff information but cannot modify their HR details.
        </div>
    @endif
</div>

@push('styles')
<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
}
</style>
@endpush
@endsection


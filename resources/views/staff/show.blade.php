@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Staff Profile</h2>
            <small class="text-muted">View staff member details and information</small>
        </div>
        <div>
            <a href="{{ route('staff.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            <a href="{{ route('staff.edit', $staff->id) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </div>
    </div>

    {{-- Profile Header --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <img src="{{ $staff->photo_url }}" class="rounded-circle" width="120" height="120" alt="avatar" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($staff->full_name) }}&background=0D8ABC&color=fff&size=128'">
                </div>
                <div class="col">
                    <h3 class="mb-1">{{ $staff->first_name }} {{ $staff->middle_name }} {{ $staff->last_name }}</h3>
                    <p class="text-muted mb-2">
                        <span class="badge bg-primary">{{ $staff->staff_id }}</span>
                        @if($staff->jobTitle)
                            <span class="badge bg-info">{{ $staff->jobTitle->name }}</span>
                        @endif
                        @if($staff->department)
                            <span class="badge bg-success">{{ $staff->department->name }}</span>
                        @endif
                    </p>
                    <div class="small text-muted">
                        @if($staff->category)
                            <span><i class="bi bi-tag"></i> {{ $staff->category->name }}</span>
                        @endif
                        @if($staff->supervisor)
                            <span class="ms-3"><i class="bi bi-person-badge"></i> Supervisor: {{ $staff->supervisor->full_name }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-auto text-end">
                    @php 
                        $badge = $staff->status === 'active' ? 'success' : 'secondary';
                    @endphp
                    <span class="badge bg-{{ $badge }} fs-6">{{ ucfirst($staff->status) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3" id="staffTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">
                <i class="bi bi-person"></i> Personal Information
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="employment-tab" data-bs-toggle="tab" data-bs-target="#employment" type="button" role="tab">
                <i class="bi bi-briefcase"></i> Employment
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab">
                <i class="bi bi-telephone"></i> Contact & Emergency
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" type="button" role="tab">
                <i class="bi bi-bank"></i> Financial & Statutory
            </button>
        </li>
        @if($staff->subordinates->count() > 0)
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="subordinates-tab" data-bs-toggle="tab" data-bs-target="#subordinates" type="button" role="tab">
                <i class="bi bi-people"></i> Subordinates ({{ $staff->subordinates->count() }})
            </button>
        </li>
        @endif
    </ul>

    <div class="tab-content" id="staffTabsContent">
        {{-- Personal Information Tab --}}
        <div class="tab-pane fade show active" id="personal" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Full Name</label>
                            <div class="fw-semibold">{{ $staff->first_name }} {{ $staff->middle_name }} {{ $staff->last_name }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Date of Birth</label>
                            <div>{{ $staff->date_of_birth ? $staff->date_of_birth->format('d M Y') : '—' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Gender</label>
                            <div>{{ ucfirst($staff->gender ?? '—') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Marital Status</label>
                            <div>{{ $staff->marital_status ?? '—' }}</div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="text-muted small">Residential Address</label>
                            <div>{{ $staff->residential_address ?? '—' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">ID Number</label>
                            <div>{{ $staff->id_number ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Employment Tab --}}
        <div class="tab-pane fade" id="employment" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Department</label>
                            <div class="fw-semibold">{{ $staff->department->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Job Title</label>
                            <div class="fw-semibold">{{ $staff->jobTitle->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Category</label>
                            <div>{{ $staff->category->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Supervisor</label>
                            <div>
                                @if($staff->supervisor)
                                    <a href="{{ route('staff.show', $staff->supervisor->id) }}">{{ $staff->supervisor->full_name }}</a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Hire Date</label>
                            <div>{{ $staff->hire_date ? $staff->hire_date->format('d M Y') : '—' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Termination Date</label>
                            <div>{{ $staff->termination_date ? $staff->termination_date->format('d M Y') : '—' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Employment Status</label>
                            <div>
                                @php
                                    $statusColors = [
                                        'active' => 'success',
                                        'on_leave' => 'warning',
                                        'terminated' => 'danger',
                                        'suspended' => 'secondary'
                                    ];
                                    $color = $statusColors[$staff->employment_status ?? 'active'] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">{{ ucfirst(str_replace('_', ' ', $staff->employment_status ?? 'active')) }}</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Employment Type</label>
                            <div>
                                @php
                                    $typeLabels = [
                                        'full_time' => 'Full Time',
                                        'part_time' => 'Part Time',
                                        'contract' => 'Contract',
                                        'intern' => 'Intern'
                                    ];
                                @endphp
                                {{ $typeLabels[$staff->employment_type ?? 'full_time'] ?? ucfirst($staff->employment_type ?? 'Full Time') }}
                            </div>
                        </div>
                        @if($staff->contract_start_date || $staff->contract_end_date)
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Contract Start Date</label>
                            <div>{{ $staff->contract_start_date ? $staff->contract_start_date->format('d M Y') : '—' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Contract End Date</label>
                            <div>{{ $staff->contract_end_date ? $staff->contract_end_date->format('d M Y') : '—' }}</div>
                        </div>
                        @endif
                        @if($staff->user)
                        <div class="col-md-12 mb-3">
                            <label class="text-muted small">System Access</label>
                            <div>
                                <span class="badge bg-info">{{ $staff->user->email }}</span>
                                @if($staff->user->roles->count() > 0)
                                    @foreach($staff->user->roles as $role)
                                        <span class="badge bg-primary">{{ $role->name }}</span>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact & Emergency Tab --}}
        <div class="tab-pane fade" id="contact" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Contact Information</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Work Email</label>
                            <div><i class="bi bi-envelope"></i> {{ $staff->work_email ?? '—' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Personal Email</label>
                            <div><i class="bi bi-envelope-at"></i> {{ $staff->personal_email ?? '—' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Phone Number</label>
                            <div><i class="bi bi-telephone"></i> {{ $staff->phone_number ?? '—' }}</div>
                        </div>
                    </div>

                    <h5 class="mb-3 mt-4">Emergency Contact</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Name</label>
                            <div>{{ $staff->emergency_contact_name ?? '—' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Relationship</label>
                            <div>{{ $staff->emergency_contact_relationship ?? '—' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Phone</label>
                            <div><i class="bi bi-telephone"></i> {{ $staff->emergency_contact_phone ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Financial & Statutory Tab --}}
        <div class="tab-pane fade" id="financial" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Bank Details</h5>
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Bank Name</label>
                            <div>{{ $staff->bank_name ?? '—' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Bank Branch</label>
                            <div>{{ $staff->bank_branch ?? '—' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Account Number</label>
                            <div>{{ $staff->bank_account ?? '—' }}</div>
                        </div>
                    </div>

                    <h5 class="mb-3 mt-4">Statutory Information</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">KRA PIN</label>
                            <div>{{ $staff->kra_pin ?? '—' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">NSSF Number</label>
                            <div>{{ $staff->nssf ?? '—' }}</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">NHIF Number</label>
                            <div>{{ $staff->nhif ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Subordinates Tab --}}
        @if($staff->subordinates->count() > 0)
        <div class="tab-pane fade" id="subordinates" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Staff ID</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Job Title</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staff->subordinates as $subordinate)
                                <tr>
                                    <td>{{ $subordinate->staff_id }}</td>
                                    <td>{{ $subordinate->first_name }} {{ $subordinate->last_name }}</td>
                                    <td>{{ $subordinate->department->name ?? '—' }}</td>
                                    <td>{{ $subordinate->jobTitle->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $subordinate->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($subordinate->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('staff.show', $subordinate->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection


@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Staff</div>
                <h1 class="mb-1">Staff Management</h1>
                <p class="text-muted mb-0">Manage employees, roles, and HR details.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('staff.upload.form') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-upload"></i> Bulk Upload
                </a>
                <a href="{{ route('staff.template') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-download"></i> Template
                </a>
                <a href="{{ route('staff.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> Add Staff
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('errors') && is_array(session('errors')))
            <div class="alert alert-warning alert-dismissible fade show">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Import Errors</h5>
                <p class="mb-2">The following rows failed to import:</p>
                <ul class="mb-0">
                    @foreach(session('errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error_details') && is_array(session('error_details')))
            <div class="settings-card mb-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-excel"></i> Detailed Import Error Report</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-modern table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Row #</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(session('error_details') as $detail)
                                    <tr>
                                        <td>{{ $detail['row'] }}</td>
                                        <td>{{ $detail['name'] ?: 'N/A' }}</td>
                                        <td>{{ $detail['email'] ?: 'N/A' }}</td>
                                        <td class="text-danger"><small>{{ $detail['error'] }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-primary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted text-uppercase fw-semibold small mb-1">Total Staff</div>
                                <h3 class="mb-0">{{ $totalStaff }}</h3>
                            </div>
                            <span class="pill-icon pill-primary"><i class="bi bi-people"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-success h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted text-uppercase fw-semibold small mb-1">Active Staff</div>
                                <h3 class="mb-0">{{ $activeStaff }}</h3>
                            </div>
                            <span class="pill-icon pill-success"><i class="bi bi-check-circle"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-secondary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted text-uppercase fw-semibold small mb-1">Archived</div>
                                <h3 class="mb-0">{{ $archivedStaff }}</h3>
                            </div>
                            <span class="pill-icon pill-secondary"><i class="bi bi-archive"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-info h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted text-uppercase fw-semibold small mb-1">Departments</div>
                                <h3 class="mb-0">{{ $departments->count() }}</h3>
                            </div>
                            <span class="pill-icon pill-info"><i class="bi bi-building"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Filters</h5>
                    <p class="text-muted small mb-0">Search and filter staff.</p>
                </div>
                <span class="pill-badge pill-secondary">Live query</span>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, email, phone, staff ID">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">All Departments</option>
                            @foreach(\App\Models\Department::orderBy('name')->get() as $d)
                                <option value="{{ $d->id }}" @selected(request('department_id')==$d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" @selected(request('status')==='active')>Active</option>
                            <option value="archived" @selected(request('status')==='archived')>Archived</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-settings-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('staff.bulk-assign-supervisor') }}" id="bulkSupervisorForm">
            @csrf
            <div class="settings-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Staff</h5>
                        <p class="mb-0 text-muted small">Roster with bulk actions.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-ghost-strong dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-check-square"></i> Bulk Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkSupervisorModal">
                                        <i class="bi bi-person-badge"></i> Assign Supervisor
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="selectAllStaff" onchange="toggleAllStaff(this)">
                                    </th>
                                    <th>Staff</th>
                                    <th>Contacts</th>
                                    <th>HR Details</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($staff as $s)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="staff_ids[]" value="{{ $s->id }}" class="staff-checkbox">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="{{ $s->photo_url }}" class="rounded-circle me-3" width="44" height="44" alt="avatar" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($s->full_name) }}&background=0D8ABC&color=fff&size=44'">
                                                <div>
                                                    <div class="fw-semibold">{{ $s->first_name }} {{ $s->last_name }}</div>
                                                    <div class="small text-muted">ID: {{ $s->staff_id }}</div>
                                                    @if($s->supervisor)
                                                        <div class="small text-muted">
                                                            <i class="bi bi-person-badge"></i> Supervisor: {{ $s->supervisor->first_name }} {{ $s->supervisor->last_name }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <i class="bi bi-envelope text-primary"></i>
                                                    <span>{{ $s->work_email }}</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 text-muted">
                                                    <i class="bi bi-telephone text-success"></i>
                                                    <span>{{ $s->phone_number }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="small">
                                            <div class="mb-1">
                                                <span class="text-muted">Dept:</span>
                                                <span class="pill-badge pill-info">{{ $s->department->name ?? '—' }}</span>
                                            </div>
                                            <div class="mb-1">
                                                <span class="text-muted">Title:</span> {{ $s->jobTitle->name ?? '—' }}
                                            </div>
                                            <div>
                                                <span class="text-muted">Category:</span>
                                                <span class="pill-badge pill-secondary">{{ $s->category->name ?? '—' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $badge = $s->status === 'active' ? 'pill-success' : 'pill-secondary';
                                            @endphp
                                            <span class="pill-badge {{ $badge }}">{{ ucfirst($s->status) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="{{ route('staff.show', $s->id) }}" class="btn btn-sm btn-ghost-strong" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('staff.edit', $s->id) }}" class="btn btn-sm btn-ghost-strong" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-ghost-strong dropdown-toggle" data-bs-toggle="dropdown"></button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('staff.show', $s->id) }}">
                                                                <i class="bi bi-eye"></i> View Details
                                                            </a>
                                                        </li>
                                                        @if($s->user)
                                                            <li>
                                                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#resetPasswordModal{{ $s->id }}">
                                                                    <i class="bi bi-key"></i> Reset Password
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <form action="{{ route('staff.resend-credentials', $s->id) }}" method="POST" onsubmit="return confirm('Resend login credentials to {{ $s->full_name }}? This will send an email and SMS with their login details.');">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="bi bi-envelope-paper"></i> Resend Login Credentials
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                        @endif
                                                        @if($s->status === 'active')
                                                            <li>
                                                                <form action="{{ route('staff.archive', $s->id) }}" method="POST" onsubmit="return confirm('Archive this staff member?')">
                                                                    @csrf @method('PATCH')
                                                                    <button type="submit" class="dropdown-item text-danger">
                                                                        <i class="bi bi-archive"></i> Archive
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @else
                                                            <li>
                                                                <form action="{{ route('staff.restore', $s->id) }}" method="POST" onsubmit="return confirm('Restore this staff member?')">
                                                                    @csrf @method('PATCH')
                                                                    <button type="submit" class="dropdown-item text-success">
                                                                        <i class="bi bi-arrow-counterclockwise"></i> Restore
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    @if($s->user)
                                        <div class="modal fade" id="resetPasswordModal{{ $s->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="bi bi-key"></i> Reset Password for {{ $s->full_name }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="{{ route('staff.reset-password', $s->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to reset the password for {{ $s->full_name }}? The new password will be sent via email and SMS.');">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <div class="alert alert-info">
                                                                <i class="bi bi-info-circle"></i> The new password will be automatically sent to the staff member via email and SMS.
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Password Options</label>
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input" type="radio" name="password_option" id="useIdNumber{{ $s->id }}" value="id_number" {{ $s->id_number ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="useIdNumber{{ $s->id }}">
                                                                        Use ID Number as Password
                                                                        @if($s->id_number)
                                                                            <small class="text-muted">({{ $s->id_number }})</small>
                                                                        @else
                                                                            <small class="text-danger">(ID Number not set)</small>
                                                                        @endif
                                                                    </label>
                                                                </div>
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input" type="radio" name="password_option" id="generateRandom{{ $s->id }}" value="random" {{ !$s->id_number ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="generateRandom{{ $s->id }}">
                                                                        Generate Random Password (8 characters)
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" name="password_option" id="customPassword{{ $s->id }}" value="custom">
                                                                    <label class="form-check-label" for="customPassword{{ $s->id }}">
                                                                        Set Custom Password
                                                                    </label>
                                                                </div>
                                                            </div>

                                                            <div class="mb-3 d-none" id="customPasswordField{{ $s->id }}">
                                                                <label class="form-label">Custom Password</label>
                                                                <input type="text" name="new_password" class="form-control" placeholder="Enter custom password (min 6 characters)" minlength="6">
                                                                <small class="text-muted">Minimum 6 characters required</small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-settings-primary">
                                                                <i class="bi bi-key"></i> Reset Password
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        @push('scripts')
                                        <script>
                                            document.addEventListener('DOMContentLoaded', function() {
                                                const modal = document.getElementById('resetPasswordModal{{ $s->id }}');
                                                if (!modal) return;
                                                const customField = modal.querySelector('#customPasswordField{{ $s->id }}');
                                                modal.querySelectorAll('input[name="password_option"]').forEach(radio => {
                                                    radio.addEventListener('change', function() {
                                                        if (this.value === 'custom') {
                                                            customField.classList.remove('d-none');
                                                            customField.querySelector('input').required = true;
                                                        } else {
                                                            customField.classList.add('d-none');
                                                            customField.querySelector('input').required = false;
                                                            customField.querySelector('input').value = '';
                                                        }
                                                    });
                                                });
                                            });
                                        </script>
                                        @endpush
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            No staff found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($staff->hasPages())
                    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-muted">
                            Showing {{ $staff->firstItem() }}–{{ $staff->lastItem() }} of {{ $staff->total() }} staff
                        </div>
                        {{ $staff->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection

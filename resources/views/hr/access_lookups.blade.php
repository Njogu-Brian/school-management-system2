@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Access & Lookups</div>
                <h1 class="mb-1">Access Control & HR Lookups</h1>
                <p class="text-muted mb-0">Manage roles, permissions, and HR lookup data.</p>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <ul class="nav settings-tabs" id="settingsTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#roles" role="tab">
                            <i class="bi bi-shield-lock"></i> Roles & Permissions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#lookups" role="tab">
                            <i class="bi bi-card-checklist"></i> HR Lookups
                        </a>
                    </li>
                </ul>

                <div class="tab-content mt-3">

                    {{-- Roles & Permissions --}}
                    <div class="tab-pane fade show active" id="roles" role="tabpanel">
                        <div class="settings-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Manage Roles & Permissions</h5>
                                    <p class="mb-0 text-muted small">Assign permissions across modules.</p>
                                </div>
                                <span class="input-chip">{{ count($roles) }} roles</span>
                            </div>
                            <div class="card-body">
                                @foreach($roles as $role)
                                    <form action="{{ route('hr.roles.permissions.update', $role->id) }}" method="POST" class="mb-4">
                                        @csrf
                                        <div class="section-title mb-2">{{ ucfirst($role->name) }}</div>
                                        <div class="row g-3">
                                            @foreach($permissions as $module => $perms)
                                                <div class="col-md-3">
                                                    <div class="fw-semibold text-muted small mb-2">{{ ucfirst($module) }}</div>
                                                    @foreach($perms as $perm)
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="permissions[]"
                                                                value="{{ $perm->id }}"
                                                                {{ $role->permissions->contains($perm->id) ? 'checked' : '' }}>
                                                            <label class="form-check-label">{{ $perm->name }}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button class="btn btn-settings-primary mt-3"><i class="bi bi-save"></i> Save for {{ ucfirst($role->name) }}</button>
                                        </div>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- HR Lookups --}}
                    <div class="tab-pane fade" id="lookups" role="tabpanel">
                        <div class="settings-card">
                            <div class="card-header">
                                <h5 class="mb-0">HR Lookups</h5>
                                <div class="section-note mb-0">Staff categories, departments, job titles, custom fields.</div>
                            </div>
                            <div class="card-body">

                                {{-- Staff Categories --}}
                                <div class="section-title">Staff Categories</div>
                                <form action="{{ route('lookups.category.store') }}" method="POST" class="row g-2 mb-3">
                                    @csrf
                                    <div class="col-md-9">
                                        <input type="text" name="name" class="form-control" placeholder="Category name" required>
                                    </div>
                                    <div class="col-md-3 d-flex justify-content-end">
                                        <button class="btn btn-settings-primary w-100">Add</button>
                                    </div>
                                </form>
                                <ul class="list-group mb-4">
                                    @foreach($categories as $cat)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            {{ $cat->name }}
                                            <form action="{{ route('lookups.category.delete', $cat->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>

                                {{-- Departments --}}
                                <div class="section-title">Departments</div>
                                <form action="{{ route('lookups.department.store') }}" method="POST" class="row g-2 mb-3">
                                    @csrf
                                    <div class="col-md-9">
                                        <input type="text" name="name" class="form-control" placeholder="Department name" required>
                                    </div>
                                    <div class="col-md-3 d-flex justify-content-end">
                                        <button class="btn btn-settings-primary w-100">Add</button>
                                    </div>
                                </form>
                                <ul class="list-group mb-4">
                                    @foreach($departments as $dept)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            {{ $dept->name }}
                                            <form action="{{ route('lookups.department.delete', $dept->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>

                                {{-- Job Titles --}}
                                <div class="section-title">Job Titles</div>
                                <form action="{{ route('lookups.jobtitle.store') }}" method="POST" class="row g-2 mb-3">
                                    @csrf
                                    <div class="col-md-4">
                                        <select name="department_id" class="form-select" required>
                                            <option value="">Select Department</option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="name" class="form-control" placeholder="Job title" required>
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-settings-primary w-100">Add</button>
                                    </div>
                                </form>
                                <ul class="list-group mb-4">
                                    @foreach($jobTitles as $jt)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            {{ $jt->name }} ({{ $jt->department->name ?? 'N/A' }})
                                            <form action="{{ route('lookups.jobtitle.delete', $jt->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>

                                {{-- Custom Fields --}}
                                <div class="section-title">Custom Fields</div>
                                <form action="{{ route('lookups.customfield.store') }}" method="POST" class="row g-2 mb-3">
                                    @csrf
                                    <div class="col-md-3"><input type="text" name="label" class="form-control" placeholder="Label" required></div>
                                    <div class="col-md-3"><input type="text" name="field_key" class="form-control" placeholder="Key" required></div>
                                    <div class="col-md-3">
                                        <select name="field_type" class="form-select" required>
                                            <option value="text">Text</option>
                                            <option value="number">Number</option>
                                            <option value="email">Email</option>
                                            <option value="date">Date</option>
                                            <option value="file">File</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 form-check d-flex align-items-center">
                                        <input type="checkbox" name="required" class="form-check-input me-2" id="req">
                                        <label for="req" class="form-check-label">Required</label>
                                    </div>
                                    <div class="col-md-1">
                                        <button class="btn btn-settings-primary w-100">Add</button>
                                    </div>
                                </form>
                                <ul class="list-group">
                                    @foreach($customFields as $f)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            {{ $f->label }} ({{ $f->field_key }}, {{ $f->field_type }})
                                            <form action="{{ route('lookups.customfield.delete', $f->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection


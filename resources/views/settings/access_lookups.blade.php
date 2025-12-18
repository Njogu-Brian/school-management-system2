@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Settings / Access & HR</div>
                <h1>Access Control & HR Lookups</h1>
                <p>Align permissions and HR reference data with your school’s policy.</p>
            </div>
        </div>

        <ul class="nav nav-pills settings-tabs" id="settingsTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-roles" type="button" role="tab">
                    <i class="bi bi-shield-lock"></i> Roles & Permissions
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-lookups" type="button" role="tab">
                    <i class="bi bi-people"></i> HR Lookups
                </button>
            </li>
        </ul>

        <div class="tab-content tab-surface">
            <div class="tab-pane fade show active" id="tab-roles" role="tabpanel">
                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Manage Roles & Permissions</h5>
                    </div>
                    <div class="card-body">
                        @foreach($roles as $role)
                            <form action="{{ route('settings.roles.update_permissions', $role->id) }}" method="POST" class="mb-4 border rounded p-3">
                                @csrf
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">{{ ucfirst($role->name) }}</h6>
                                    <span class="input-chip">{{ $role->permissions->count() }} assigned</span>
                                </div>
                                <div class="row g-3">
                                    @foreach($permissions as $module => $perms)
                                        <div class="col-md-3">
                                            <div class="fw-semibold mb-2">{{ ucfirst($module) }}</div>
                                            @foreach($perms as $perm)
                                                <div class="form-check mb-1">
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
                                <button class="btn btn-settings-primary mt-3">Save for {{ ucfirst($role->name) }}</button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-lookups" role="tabpanel">
                <div class="settings-card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Staff Categories</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('lookups.category.store') }}" method="POST" class="row g-2 mb-3">
                            @csrf
                            <div class="col-md-9">
                                <input type="text" name="name" class="form-control" placeholder="Category name" required>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-settings-primary w-100">Add Category</button>
                            </div>
                        </form>
                        <ul class="list-group">
                            @foreach($categories as $cat)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $cat->name }}
                                    <form action="{{ route('lookups.category.delete', $cat->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="settings-card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Departments</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('lookups.department.store') }}" method="POST" class="row g-2 mb-3">
                            @csrf
                            <div class="col-md-9">
                                <input type="text" name="name" class="form-control" placeholder="Department name" required>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-settings-primary w-100">Add Department</button>
                            </div>
                        </form>
                        <ul class="list-group">
                            @foreach($departments as $dept)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $dept->name }}
                                    <form action="{{ route('lookups.department.delete', $dept->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="settings-card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Job Titles</h5>
                    </div>
                    <div class="card-body">
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
                        <ul class="list-group">
                            @foreach($jobTitles as $jt)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $jt->name }} ({{ $jt->department->name ?? 'N/A' }})
                                    <form action="{{ route('lookups.jobtitle.delete', $jt->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Custom Fields</h5>
                    </div>
                    <div class="card-body">
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
                                <input type="checkbox" name="required" class="form-check-input" id="req">
                                <label for="req" class="form-check-label ms-2">Required</label>
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
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
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
@endsection

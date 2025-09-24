@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Access Control & HR Lookups</h1>

    <ul class="nav nav-tabs" id="settingsTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#roles" role="tab">Roles & Permissions</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#lookups" role="tab">HR Lookups</a>
        </li>
    </ul>

    <div class="tab-content p-3 border border-top-0">

        {{-- Roles & Permissions --}}
        <div class="tab-pane fade show active" id="roles" role="tabpanel">
            <h4>Manage Roles & Permissions</h4>

            @foreach($roles as $role)
                <form action="{{ route('settings.roles.update_permissions', $role->id) }}" method="POST" class="mb-4">
                    @csrf
                    <h5 class="mt-3">{{ ucfirst($role->name) }}</h5>
                    <div class="row">
                        @foreach($permissions as $module => $perms)
                            <div class="col-md-3">
                                <h6>{{ ucfirst($module) }}</h6>
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
                    <button class="btn btn-primary mt-2">💾 Save for {{ ucfirst($role->name) }}</button>
                </form>
            @endforeach
        </div>

        {{-- HR Lookups --}}
        <div class="tab-pane fade" id="lookups" role="tabpanel">
            <h4>HR Lookups</h4>

            {{-- Staff Categories --}}
            <h5>Staff Categories</h5>
            <form action="{{ route('lookups.category.store') }}" method="POST" class="d-flex mb-2">
                @csrf
                <input type="text" name="name" class="form-control me-2" placeholder="Category name" required>
                <button class="btn btn-success">Add</button>
            </form>
            <ul class="list-group mb-3">
                @foreach($categories as $cat)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $cat->name }}
                        <form action="{{ route('lookups.category.delete', $cat->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>

            {{-- Departments --}}
            <h5>Departments</h5>
            <form action="{{ route('lookups.department.store') }}" method="POST" class="d-flex mb-2">
                @csrf
                <input type="text" name="name" class="form-control me-2" placeholder="Department name" required>
                <button class="btn btn-success">Add</button>
            </form>
            <ul class="list-group mb-3">
                @foreach($departments as $dept)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $dept->name }}
                        <form action="{{ route('lookups.department.delete', $dept->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>

            {{-- Job Titles --}}
            <h5>Job Titles</h5>
            <form action="{{ route('lookups.jobtitle.store') }}" method="POST" class="row g-2 mb-2">
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
                    <button class="btn btn-success w-100">Add</button>
                </div>
            </form>
            <ul class="list-group mb-3">
                @foreach($jobTitles as $jt)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $jt->name }} ({{ $jt->department->name ?? 'N/A' }})
                        <form action="{{ route('lookups.jobtitle.delete', $jt->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>

            {{-- Custom Fields --}}
            <h5>Custom Fields</h5>
            <form action="{{ route('lookups.customfield.store') }}" method="POST" class="row g-2 mb-2">
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
                <div class="col-md-2 form-check">
                    <input type="checkbox" name="required" class="form-check-input" id="req">
                    <label for="req" class="form-check-label">Required</label>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-success">Add</button>
                </div>
            </form>
            <ul class="list-group">
                @foreach($customFields as $f)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $f->label }} ({{ $f->field_key }}, {{ $f->field_type }})
                        <form action="{{ route('lookups.customfield.delete', $f->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection

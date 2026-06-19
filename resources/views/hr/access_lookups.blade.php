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
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <h5 class="mb-0">Manage Roles & Permissions</h5>
                                    <p class="mb-0 text-muted small">Select a role, then assign permissions by module.</p>
                                </div>
                                <span class="input-chip">{{ count($roles) }} roles</span>
                            </div>
                            <div class="card-body">
                                @if(session('success'))
                                    <div class="alert alert-success py-2">{{ session('success') }}</div>
                                @endif

                                @if($selectedRole)
                                    {{-- Role selector --}}
                                    <div class="row g-3 align-items-end mb-4">
                                        <div class="col-md-5 col-lg-4">
                                            <label for="roleSelect" class="form-label small text-muted mb-1">Role</label>
                                            <select id="roleSelect" class="form-select" onchange="window.location.href=this.value">
                                                @foreach($roles as $role)
                                                    <option value="{{ route('hr.access-lookups', ['role' => $role->id]) }}"
                                                        {{ $selectedRole->id === $role->id ? 'selected' : '' }}>
                                                        {{ $role->name }} ({{ $role->permissions->count() }} assigned)
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-5 col-lg-4">
                                            <label for="moduleFilter" class="form-label small text-muted mb-1">Filter modules</label>
                                            <input type="search" id="moduleFilter" class="form-control"
                                                   placeholder="Search e.g. finance, exams, attendance…">
                                        </div>
                                        <div class="col-md-2 col-lg-4 d-flex gap-2 flex-wrap">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="showAssignedOnly">
                                                Assigned only
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="showAllModules">
                                                Show all
                                            </button>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 mb-4 role-pills">
                                        @foreach($roles as $role)
                                            <a href="{{ route('hr.access-lookups', ['role' => $role->id]) }}"
                                               class="btn btn-sm {{ $selectedRole->id === $role->id ? 'btn-settings-primary' : 'btn-outline-secondary' }}">
                                                {{ $role->name }}
                                            </a>
                                        @endforeach
                                    </div>

                                    <form action="{{ route('hr.roles.permissions.update', $selectedRole->id) }}" method="POST" id="rolePermissionsForm">
                                        @csrf
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                                            <div>
                                                <div class="section-title mb-0">{{ $selectedRole->name }}</div>
                                                <p class="text-muted small mb-0">Toggle permissions below, then save.</p>
                                            </div>
                                            <span class="input-chip" id="assignedCountChip">
                                                {{ $selectedRole->permissions->count() }} / {{ $permissions->flatten()->count() }} assigned
                                            </span>
                                        </div>

                                        <div class="row g-3" id="permissionModules">
                                            @foreach($permissions as $module => $perms)
                                                @php
                                                    $moduleLabel = $moduleLabels[$module] ?? \App\Support\NavAccess::moduleLabel($module);
                                                    $assignedInModule = $perms->filter(fn ($p) => $selectedRole->permissions->contains($p->id))->count();
                                                @endphp
                                                <div class="col-md-6 col-lg-4 col-xl-3 permission-module-col"
                                                     data-module="{{ strtolower($module . ' ' . $moduleLabel) }}"
                                                     data-assigned="{{ $assignedInModule }}"
                                                     data-total="{{ $perms->count() }}">
                                                    <div class="border rounded p-3 h-100 bg-light-subtle">
                                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                                            <div class="fw-semibold small">{{ $moduleLabel }}</div>
                                                            <span class="badge text-bg-secondary module-count-badge">{{ $assignedInModule }}/{{ $perms->count() }}</span>
                                                        </div>
                                                        @foreach($perms as $perm)
                                                            <div class="form-check">
                                                                <input class="form-check-input perm-checkbox" type="checkbox"
                                                                    name="permissions[]"
                                                                    value="{{ $perm->id }}"
                                                                    id="perm-{{ $selectedRole->id }}-{{ $perm->id }}"
                                                                    {{ $selectedRole->permissions->contains($perm->id) ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="perm-{{ $selectedRole->id }}-{{ $perm->id }}">
                                                                    {{ str_contains($perm->name, '.') ? substr($perm->name, strlen($module) + 1) : $perm->name }}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 pt-3 border-top">
                                            <span class="text-muted small" id="visibleModulesHint"></span>
                                            <button type="submit" class="btn btn-settings-primary">
                                                <i class="bi bi-save"></i> Save {{ $selectedRole->name }} permissions
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <p class="text-muted mb-0">No roles found. Run the roles and permissions seeder first.</p>
                                @endif
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

@push('scripts')
@if($selectedRole ?? false)
<script>
(function () {
    const moduleFilter = document.getElementById('moduleFilter');
    const showAssignedOnly = document.getElementById('showAssignedOnly');
    const showAllModules = document.getElementById('showAllModules');
    const cols = Array.from(document.querySelectorAll('.permission-module-col'));
    const checkboxes = Array.from(document.querySelectorAll('.perm-checkbox'));
    const assignedChip = document.getElementById('assignedCountChip');
    const hint = document.getElementById('visibleModulesHint');
    let assignedOnly = false;

    function updateAssignedCount() {
        const checked = checkboxes.filter(cb => cb.checked).length;
        assignedChip.textContent = checked + ' / {{ $permissions->flatten()->count() }} assigned';
        cols.forEach(col => {
            const moduleChecks = col.querySelectorAll('.perm-checkbox');
            const assigned = Array.from(moduleChecks).filter(cb => cb.checked).length;
            col.dataset.assigned = assigned;
            const badge = col.querySelector('.module-count-badge');
            if (badge) badge.textContent = assigned + '/' + col.dataset.total;
        });
    }

    function applyFilters() {
        const q = (moduleFilter?.value || '').trim().toLowerCase();
        let visible = 0;
        cols.forEach(col => {
            const matchesSearch = !q || col.dataset.module.includes(q);
            const matchesAssigned = !assignedOnly || parseInt(col.dataset.assigned, 10) > 0;
            const show = matchesSearch && matchesAssigned;
            col.classList.toggle('d-none', !show);
            if (show) visible++;
        });
        if (hint) {
            hint.textContent = visible + ' module' + (visible === 1 ? '' : 's') + ' shown';
        }
    }

    moduleFilter?.addEventListener('input', applyFilters);

    showAssignedOnly?.addEventListener('click', () => {
        assignedOnly = true;
        showAssignedOnly.classList.add('d-none');
        showAllModules?.classList.remove('d-none');
        applyFilters();
    });

    showAllModules?.addEventListener('click', () => {
        assignedOnly = false;
        showAllModules.classList.add('d-none');
        showAssignedOnly?.classList.remove('d-none');
        applyFilters();
    });

    checkboxes.forEach(cb => cb.addEventListener('change', () => {
        updateAssignedCount();
        if (assignedOnly) applyFilters();
    }));

    updateAssignedCount();
    applyFilters();
})();
</script>
@endif
@endpush


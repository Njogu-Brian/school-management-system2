@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Staff</div>
                <h1 class="mb-1">Verify Staff Upload</h1>
                <p class="text-muted mb-0">Review and confirm parsed rows before import.</p>
            </div>
            <a href="{{ route('staff.upload.form') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Re-upload
            </a>
        </div>

        @if(session('errors'))
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach(session('errors') as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('staff.upload.commit') }}">
            @csrf

            <div class="settings-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0"><i class="bi bi-sliders"></i> Bulk Assignment Options</h5>
                        <p class="text-muted small mb-0">Apply to all rows (overridden by per-row selections).</p>
                    </div>
                    <span class="pill-badge pill-secondary">Optional</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Bulk Department</label>
                            <select name="bulk_department_id" class="form-select" id="bulkDepartment">
                                <option value="">— None (use individual) —</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bulk Job Title</label>
                            <select name="bulk_job_title_id" class="form-select" id="bulkJobTitle">
                                <option value="">— None (use individual) —</option>
                                @foreach($jobTitles as $j)
                                    <option value="{{ $j->id }}">{{ $j->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bulk Category</label>
                            <select name="bulk_staff_category_id" class="form-select" id="bulkCategory">
                                <option value="">— None (use individual) —</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Bulk Role</label>
                            <select name="bulk_spatie_role_name" class="form-select" id="bulkRole">
                                <option value="">— None (use individual) —</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-3">
                            <label class="form-label">Bulk Supervisor</label>
                            <select name="bulk_supervisor_id" class="form-select" id="bulkSupervisor">
                                <option value="">— None (use individual) —</option>
                                @foreach($staff as $s)
                                    <option value="{{ $s->id }}">{{ $s->full_name }} ({{ $s->staff_id }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-ghost-strong" id="applyBulkBtn">
                            <i class="bi bi-arrow-down"></i> Apply to All Rows
                        </button>
                        <button type="button" class="btn btn-sm btn-ghost-strong" id="clearBulkBtn">
                            <i class="bi bi-x-circle"></i> Clear Bulk Selections
                        </button>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0">Review Rows</h5>
                        <p class="text-muted small mb-0">Adjust mappings before import.</p>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Work Email</th>
                                    <th>Phone</th>
                                    <th>Department</th>
                                    <th>Job Title</th>
                                    <th>Category</th>
                                    <th>Supervisor</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $i => $r)
                                    <tr>
                                        <td>{{ $i+1 }}</td>
                                        <td>{{ $r['first_name'] }} {{ $r['last_name'] }}</td>
                                        <td>{{ $r['work_email'] }}</td>
                                        <td>{{ $r['phone_number'] }}</td>

                                        <td>
                                            <select name="department_id[{{ $i }}]" class="form-select">
                                                <option value="">—</option>
                                                @foreach($departments as $d)
                                                    <option value="{{ $d->id }}"
                                                        @selected(strcasecmp($r['department_guess'],$d->name)==0)>{{ $d->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td>
                                            <select name="job_title_id[{{ $i }}]" class="form-select">
                                                <option value="">—</option>
                                                @foreach($jobTitles as $j)
                                                    <option value="{{ $j->id }}"
                                                        @selected(strcasecmp($r['job_title_guess'],$j->name)==0)>{{ $j->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td>
                                            <select name="staff_category_id[{{ $i }}]" class="form-select">
                                                <option value="">—</option>
                                                @foreach($categories as $c)
                                                    <option value="{{ $c->id }}"
                                                        @selected(strcasecmp($r['category_guess'],$c->name)==0)>{{ $c->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td>
                                            <select name="supervisor_id[{{ $i }}]" class="form-select">
                                                <option value="">—</option>
                                                @foreach($supervisors as $s)
                                                    <option value="{{ $s->id }}"
                                                        @selected($r['supervisor_staff_id_guess'] === $s->staff_id)>
                                                        {{ $s->staff_id }} — {{ $s->first_name }} {{ $s->last_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td>
                                            <select name="spatie_role_name[{{ $i }}]" class="form-select">
                                                <option value="">—</option>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->name }}"
                                                        @selected(strcasecmp($r['spatie_role_guess'],$role->name)==0)>
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2 justify-content-end">
                <a href="{{ route('staff.upload.form') }}" class="btn btn-ghost-strong">Re-upload</a>
                <button class="btn btn-settings-primary">Confirm & Import</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const bulkDept = document.getElementById('bulkDepartment');
    const bulkJob = document.getElementById('bulkJobTitle');
    const bulkCat = document.getElementById('bulkCategory');
    const bulkRole = document.getElementById('bulkRole');
    const bulkSupervisor = document.getElementById('bulkSupervisor');
    const applyBtn = document.getElementById('applyBulkBtn');
    const clearBtn = document.getElementById('clearBulkBtn');

    applyBtn.addEventListener('click', function() {
      const deptVal = bulkDept.value;
      const jobVal = bulkJob.value;
      const catVal = bulkCat.value;
      const roleVal = bulkRole.value;
      const supervisorVal = bulkSupervisor.value;

      document.querySelectorAll('select[name^="department_id"]').forEach(sel => { if (deptVal) sel.value = deptVal; });
      document.querySelectorAll('select[name^="job_title_id"]').forEach(sel => { if (jobVal) sel.value = jobVal; });
      document.querySelectorAll('select[name^="staff_category_id"]').forEach(sel => { if (catVal) sel.value = catVal; });
      document.querySelectorAll('select[name^="spatie_role_name"]').forEach(sel => { if (roleVal) sel.value = roleVal; });
      document.querySelectorAll('select[name^="supervisor_id"]').forEach(sel => { if (supervisorVal) sel.value = supervisorVal; });
      alert('Bulk selections applied to all rows!');
    });

    clearBtn.addEventListener('click', function() {
      bulkDept.value = '';
      bulkJob.value = '';
      bulkCat.value = '';
      bulkRole.value = '';
      bulkSupervisor.value = '';
    });
  });
</script>
@endpush
@endsection

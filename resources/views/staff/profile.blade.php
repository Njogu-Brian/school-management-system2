@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-3">My Profile</h1>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if($pending->count())
        <div class="alert alert-warning">
            You have {{ $pending->count() }} pending change request(s). Admin approval is required before they take effect.
        </div>
    @endif

    <div class="card shadow-sm mb-4">
    <div class="card-body d-flex align-items-center">
        <img
        src="{{ $staff->photo_path ? asset('storage/'.$staff->photo_path) : 'https://ui-avatars.com/api/?name='.urlencode($staff->first_name.' '.$staff->last_name).'&background=0D8ABC&color=fff&size=96' }}"
        class="rounded-circle me-3" width="72" height="72" alt="avatar">

        <div class="flex-fill">
        <div class="fs-5 fw-bold">{{ $staff->first_name }} {{ $staff->last_name }}</div>
        <div class="text-muted">Staff ID: {{ $staff->staff_id }}</div>
        <div class="text-muted">Department: {{ $staff->department->name ?? '—' }} | Title: {{ $staff->jobTitle->name ?? '—' }}</div>
        <div class="text-muted">Category: {{ $staff->category->name ?? '—' }} | Supervisor: {{ $staff->supervisor?->full_name ?? '—' }}</div>
        </div>

        <div style="width:220px">
        <label class="form-label mb-1">Update Passport Photo</label>
        <input type="file" name="photo" form="profileForm" accept="image/*" class="form-control" onchange="previewProfilePhoto(this)">
        <div class="form-text">JPG/PNG, ≤ 2MB</div>
        </div>
    </div>
    </div>

    <form id="profileForm" action="{{ route('staff.profile.update') }}" method="POST" enctype="multipart/form-data" class="card shadow-sm">
        @csrf
        <div class="card-body">
            <h5 class="mb-3">Contact</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Work Email *</label>
                    <input type="email" name="work_email" class="form-control" value="{{ old('work_email', $staff->work_email) }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Personal Email</label>
                    <input type="email" name="personal_email" class="form-control" value="{{ old('personal_email', $staff->personal_email) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $staff->phone_number) }}" required>
                </div>
            </div>

            <h5 class="mt-4 mb-3">Personal</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">ID Number *</label>
                    <input type="text" name="id_number" class="form-control" value="{{ old('id_number', $staff->id_number) }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $staff->date_of_birth) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">—</option>
                        @foreach(['male'=>'Male','female'=>'Female','other'=>'Other'] as $val=>$label)
                            <option value="{{ $val }}" @selected(old('gender', $staff->gender) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Marital Status</label>
                    <input type="text" name="marital_status" class="form-control" value="{{ old('marital_status', $staff->marital_status) }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Residential Address</label>
                    <input type="text" name="residential_address" class="form-control" value="{{ old('residential_address', $staff->residential_address) }}">
                </div>
            </div>

            <h5 class="mt-4 mb-3">Emergency Contact</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $staff->emergency_contact_name) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Relationship</label>
                    <input type="text" name="emergency_contact_relationship" class="form-control" value="{{ old('emergency_contact_relationship', $staff->emergency_contact_relationship) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $staff->emergency_contact_phone) }}">
                </div>
            </div>

            <h5 class="mt-4 mb-3">Bank & Statutory</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">KRA PIN</label>
                    <input type="text" name="kra_pin" class="form-control" value="{{ old('kra_pin', $staff->kra_pin) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">NSSF</label>
                    <input type="text" name="nssf" class="form-control" value="{{ old('nssf', $staff->nssf) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">NHIF</label>
                    <input type="text" name="nhif" class="form-control" value="{{ old('nhif', $staff->nhif) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $staff->bank_name) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Bank Branch</label>
                    <input type="text" name="bank_branch" class="form-control" value="{{ old('bank_branch', $staff->bank_branch) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Bank Account</label>
                    <input type="text" name="bank_account" class="form-control" value="{{ old('bank_account', $staff->bank_account) }}">
                </div>
            </div>

            <h5 class="mt-4 mb-3">HR (Proposed)</h5>
            <p class="text-muted small">You can request changes here; an administrator must approve them.</p>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">—</option>
                        @foreach(\App\Models\Department::orderBy('name')->get() as $d)
                            <option value="{{ $d->id }}" @selected(old('department_id', $staff->department_id) == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Job Title</label>
                    <select name="job_title_id" class="form-select">
                        <option value="">—</option>
                        @foreach(\App\Models\JobTitle::orderBy('name')->get() as $j)
                            <option value="{{ $j->id }}" @selected(old('job_title_id', $staff->job_title_id) == $j->id)>{{ $j->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Category</label>
                    <select name="staff_category_id" class="form-select">
                        <option value="">—</option>
                        @foreach(\App\Models\StaffCategory::orderBy('name')->get() as $c)
                            <option value="{{ $c->id }}" @selected(old('staff_category_id', $staff->staff_category_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Supervisor</label>
                    <select name="supervisor_id" class="form-select">
                        <option value="">—</option>
                        @foreach(\App\Models\Staff::orderBy('first_name')->get() as $s)
                            <option value="{{ $s->id }}" @selected(old('supervisor_id', $staff->supervisor_id) == $s->id)>{{ $s->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between">
            <div class="text-muted small">
                Submitting will create a <strong>pending</strong> change request. Admin will review and approve/reject.
            </div>
            <button class="btn btn-primary">Submit Changes for Approval</button>
        </div>
    </form>

    @if($pending->count())
        <div class="card mt-4">
            <div class="card-header">Pending Requests</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th><th>Submitted</th><th>Fields</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending as $pc)
                            <tr>
                                <td>{{ $pc->id }}</td>
                                <td>{{ $pc->created_at->format('d M Y, H:i') }}</td>
                                <td>
                                    @foreach(array_keys($pc->changes ?? []) as $f)
                                        <span class="badge bg-secondary me-1">{{ $f }}</span>
                                    @endforeach
                                </td>
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
  function previewProfilePhoto(input){
    if(!input.files?.length) return;
    const file = input.files[0];
    if(!['image/jpeg','image/png','image/jpg'].includes(file.type)) { alert('Only JPG/PNG allowed'); input.value=''; return; }
    if(file.size > 2*1024*1024){ alert('Image must be 2MB or smaller.'); input.value=''; return; }

    // update the header avatar instantly
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.querySelector('.card-body img.rounded-circle');
      if(img) img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
</script>
@endpush
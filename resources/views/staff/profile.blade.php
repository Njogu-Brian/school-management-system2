@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">My Profile</div>
        <h1 class="mb-1">My Profile</h1>
        <p class="text-muted mb-0">Update your contact, personal, and statutory information.</p>
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

    <div class="settings-card mb-3">
      <div class="card-body d-flex align-items-center flex-wrap gap-3">
        <img src="{{ $staff->photo_url }}" class="rounded-circle" width="80" height="80" alt="avatar" id="profilePhotoPreview"
             onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode($staff->full_name) }}&background=0D8ABC&color=fff&size=80'">
        <div class="flex-fill">
          <div class="fs-5 fw-bold">{{ $staff->first_name }} {{ $staff->last_name }}</div>
          <div class="text-muted">Staff ID: {{ $staff->staff_id }}</div>
          <div class="text-muted">Department: {{ $staff->department->name ?? '—' }} | Title: {{ $staff->jobTitle->name ?? '—' }}</div>
          <div class="text-muted">Category: {{ $staff->category->name ?? '—' }} | Supervisor: {{ $staff->supervisor?->full_name ?? '—' }}</div>
        </div>
        <div style="min-width:240px">
          <label class="form-label mb-1">Update Passport Photo</label>
          <input type="file" name="photo" form="profileForm" accept="image/*" class="form-control" onchange="previewProfilePhoto(this)">
          <div class="form-text">JPG/PNG, ≤ 2MB</div>
        </div>
      </div>
    </div>

    <form id="profileForm" action="{{ route('staff.profile.update') }}" method="POST" enctype="multipart/form-data" class="settings-card">
      @csrf
      <div class="card-body">
        <h5 class="mb-3">Contact</h5>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Work Email</label>
            <input type="email" class="form-control" value="{{ $staff->work_email }}" disabled>
            <div class="form-text">Contact admin to change work email</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Personal Email</label>
            <input type="email" name="personal_email" class="form-control" value="{{ old('personal_email', $staff->personal_email) }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">Phone Number *</label>
            <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $staff->phone_number) }}" required>
          </div>
        </div>

        <h5 class="mt-4 mb-3">Personal</h5>
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">ID Number *</label>
            <input type="text" name="id_number" class="form-control" value="{{ old('id_number', $staff->id_number) }}" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $staff->date_of_birth ? $staff->date_of_birth->format('Y-m-d') : '') }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-select">
              <option value="">—</option>
              @foreach(['male'=>'Male','female'=>'Female','other'=>'Other'] as $val=>$label)
                <option value="{{ $val }}" @selected(old('gender', $staff->gender) === $val)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Marital Status</label>
            <input type="text" name="marital_status" class="form-control" value="{{ old('marital_status', $staff->marital_status) }}">
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label class="form-label">Residential Address</label>
            <input type="text" name="residential_address" class="form-control" value="{{ old('residential_address', $staff->residential_address) }}">
          </div>
        </div>

        <h5 class="mt-4 mb-3">Emergency Contact</h5>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $staff->emergency_contact_name) }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">Relationship</label>
            <input type="text" name="emergency_contact_relationship" class="form-control" value="{{ old('emergency_contact_relationship', $staff->emergency_contact_relationship) }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">Phone</label>
            <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $staff->emergency_contact_phone) }}">
          </div>
        </div>

        <h5 class="mt-4 mb-3">Bank & Statutory</h5>
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">KRA PIN</label>
            <input type="text" name="kra_pin" class="form-control" value="{{ old('kra_pin', $staff->kra_pin) }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">NSSF</label>
            <input type="text" name="nssf" class="form-control" value="{{ old('nssf', $staff->nssf) }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">NHIF</label>
            <input type="text" name="nhif" class="form-control" value="{{ old('nhif', $staff->nhif) }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Bank Name</label>
            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $staff->bank_name) }}">
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label class="form-label">Bank Branch</label>
            <input type="text" name="bank_branch" class="form-control" value="{{ old('bank_branch', $staff->bank_branch) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Bank Account</label>
            <input type="text" name="bank_account" class="form-control" value="{{ old('bank_account', $staff->bank_account) }}">
          </div>
        </div>
      </div>

      <div class="card-footer d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div class="text-muted small">
          <i class="bi bi-info-circle"></i> Submitting will create a <strong>pending</strong> change request. Admin will review and approve/reject.<br>
          <small>Note: HR lookups (Department, Job Title, Category, Supervisor) can only be changed by administrators.</small>
        </div>
        <button class="btn btn-settings-primary">Submit Changes for Approval</button>
      </div>
    </form>

    @if($pending->count())
      <div class="settings-card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Pending Requests</h5>
          <span class="pill-badge pill-warning">{{ $pending->count() }} pending</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern mb-0">
              <thead class="table-light">
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
                        <span class="pill-badge pill-secondary me-1">{{ $f }}</span>
                      @endforeach
                    </td>
                    <td><span class="pill-badge pill-warning">Pending</span></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
  function previewProfilePhoto(input){
    if(!input.files?.length) return;
    const file = input.files[0];
    if(!['image/jpeg','image/png','image/jpg'].includes(file.type)) { alert('Only JPG/PNG allowed'); input.value=''; return; }
    if(file.size > 2*1024*1024){ alert('Image must be 2MB or smaller.'); input.value=''; return; }
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.getElementById('profilePhotoPreview');
      if(img) img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }
</script>
@endpush

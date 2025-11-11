@php
  $isEdit = isset($staff);
  $photoUrl = $isEdit ? ($staff->photo_url ?? '') : 'https://ui-avatars.com/api/?name='.urlencode(old('first_name','').' '.old('last_name','')).'&background=0D8ABC&color=fff&size=128';
@endphp

<div class="card shadow-sm">
  <div class="card-body">
    <div class="row g-4">
      <div class="col-md-3">
        <label class="form-label d-block">Passport Photo</label>
        <div class="border rounded-3 p-3 text-center">
          <img id="photoPreview" src="{{ $photoUrl }}" class="rounded-circle mb-3" width="120" height="120" alt="photo">
          <input type="file" name="photo" accept="image/*" class="form-control" onchange="previewPhoto(this)">
          <div class="form-text">JPG/PNG, ≤ 2MB</div>
        </div>
      </div>

      <div class="col-md-9">
        <div class="row g-3">

          {{-- Identity --}}
          <div class="col-12"><h6 class="text-uppercase text-muted">Identity</h6></div>
          <div class="col-md-3">
            <label class="form-label">Staff ID</label>
            <input type="text" name="staff_id" class="form-control" value="{{ old('staff_id', $staff->staff_id ?? '') }}" placeholder="Auto if blank">
          </div>
          <div class="col-md-3">
            <label class="form-label">First Name *</label>
            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $staff->first_name ?? '') }}" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Middle Name</label>
            <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $staff->middle_name ?? '') }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Last Name *</label>
            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $staff->last_name ?? '') }}" required>
          </div>

          {{-- Contacts --}}
          <div class="col-12 pt-2"><h6 class="text-uppercase text-muted">Contacts</h6></div>
          <div class="col-md-4">
            <label class="form-label">Work Email *</label>
            <input type="email" name="work_email" class="form-control" value="{{ old('work_email', $staff->work_email ?? '') }}" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Personal Email</label>
            <input type="email" name="personal_email" class="form-control" value="{{ old('personal_email', $staff->personal_email ?? '') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">Phone Number *</label>
            <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $staff->phone_number ?? '') }}" required>
          </div>

          {{-- Personal --}}
          <div class="col-12 pt-2"><h6 class="text-uppercase text-muted">Personal</h6></div>
          <div class="col-md-3">
            <label class="form-label">ID Number *</label>
            <input type="text" name="id_number" class="form-control" value="{{ old('id_number', $staff->id_number ?? '') }}" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $staff->date_of_birth ?? '') }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-select">
              <option value="">—</option>
              @foreach(['male'=>'Male','female'=>'Female','other'=>'Other'] as $val=>$label)
                <option value="{{ $val }}" @selected(old('gender', $staff->gender ?? '') === $val)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Marital Status</label>
            <input type="text" name="marital_status" class="form-control" value="{{ old('marital_status', $staff->marital_status ?? '') }}">
          </div>

          <div class="col-md-12">
            <label class="form-label">Residential Address</label>
            <input type="text" name="residential_address" class="form-control" value="{{ old('residential_address', $staff->residential_address ?? '') }}">
          </div>

          {{-- Emergency --}}
          <div class="col-12 pt-2"><h6 class="text-uppercase text-muted">Emergency Contact</h6></div>
          <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $staff->emergency_contact_name ?? '') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">Relationship</label>
            <input type="text" name="emergency_contact_relationship" class="form-control" value="{{ old('emergency_contact_relationship', $staff->emergency_contact_relationship ?? '') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">Phone</label>
            <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $staff->emergency_contact_phone ?? '') }}">
          </div>

          {{-- HR --}}

            {{-- Access --}}
          <div class="col-12 pt-2"><h6 class="text-uppercase text-muted">Access</h6></div>
          <div class="col-md-4">
            <label class="form-label">System Role</label>
            <select name="spatie_role_id" class="form-select">
              <option value="">— Select system role —</option>
              @foreach($spatieRoles as $r)
                <option value="{{ $r->id }}"
                  @selected(old('spatie_role_id', isset($staff) ? optional($staff->user?->roles?->first())->id : null) == $r->id)>
                  {{ $r->name }}
                </option>
              @endforeach
            </select>
            <div class="form-text">Controls dashboard & module access.</div>
          </div>

          <div class="col-12 pt-2"><h6 class="text-uppercase text-muted">HR</h6></div>
          <div class="col-md-3">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
              <option value="">—</option>
              @foreach(\App\Models\Department::orderBy('name')->get() as $d)
                <option value="{{ $d->id }}" @selected(old('department_id', $staff->department_id ?? '')==$d->id)>{{ $d->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Job Title</label>
            <select name="job_title_id" class="form-select">
              <option value="">—</option>
              @foreach(\App\Models\JobTitle::orderBy('name')->get() as $j)
                <option value="{{ $j->id }}" @selected(old('job_title_id', $staff->job_title_id ?? '')==$j->id)>{{ $j->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Category</label>
            <select name="staff_category_id" class="form-select">
              <option value="">—</option>
              @foreach(\App\Models\StaffCategory::orderBy('name')->get() as $c)
                <option value="{{ $c->id }}" @selected(old('staff_category_id', $staff->staff_category_id ?? '')==$c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Supervisor</label>
            <select name="supervisor_id" class="form-select">
              <option value="">—</option>
              @foreach(\App\Models\Staff::orderBy('first_name')->get() as $sp)
                @if(!isset($staff) || $sp->id != $staff->id)
                  <option value="{{ $sp->id }}" @selected(old('supervisor_id', $staff->supervisor_id ?? '')==$sp->id)>{{ $sp->full_name }}</option>
                @endif
              @endforeach
            </select>
          </div>

          {{-- Employment Information --}}
          <div class="col-12 pt-2"><h6 class="text-uppercase text-muted">Employment Information</h6></div>
          <div class="col-md-3">
            <label class="form-label">Hire Date</label>
            <input type="date" name="hire_date" class="form-control" value="{{ old('hire_date', isset($staff) && $staff->hire_date ? $staff->hire_date->format('Y-m-d') : '') }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Employment Status</label>
            <select name="employment_status" class="form-select">
              <option value="active" @selected(old('employment_status', $staff->employment_status ?? 'active') === 'active')>Active</option>
              <option value="on_leave" @selected(old('employment_status', $staff->employment_status ?? '') === 'on_leave')>On Leave</option>
              <option value="suspended" @selected(old('employment_status', $staff->employment_status ?? '') === 'suspended')>Suspended</option>
              <option value="terminated" @selected(old('employment_status', $staff->employment_status ?? '') === 'terminated')>Terminated</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Employment Type</label>
            <select name="employment_type" class="form-select">
              <option value="full_time" @selected(old('employment_type', $staff->employment_type ?? 'full_time') === 'full_time')>Full Time</option>
              <option value="part_time" @selected(old('employment_type', $staff->employment_type ?? '') === 'part_time')>Part Time</option>
              <option value="contract" @selected(old('employment_type', $staff->employment_type ?? '') === 'contract')>Contract</option>
              <option value="intern" @selected(old('employment_type', $staff->employment_type ?? '') === 'intern')>Intern</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Termination Date</label>
            <input type="date" name="termination_date" class="form-control" value="{{ old('termination_date', isset($staff) && $staff->termination_date ? $staff->termination_date->format('Y-m-d') : '') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Contract Start Date</label>
            <input type="date" name="contract_start_date" class="form-control" value="{{ old('contract_start_date', isset($staff) && $staff->contract_start_date ? $staff->contract_start_date->format('Y-m-d') : '') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Contract End Date</label>
            <input type="date" name="contract_end_date" class="form-control" value="{{ old('contract_end_date', isset($staff) && $staff->contract_end_date ? $staff->contract_end_date->format('Y-m-d') : '') }}">
          </div>

          {{-- Statutory/Bank --}}
          <div class="col-12 pt-2"><h6 class="text-uppercase text-muted">Statutory & Bank</h6></div>
          <div class="col-md-3">
            <label class="form-label">KRA PIN</label>
            <input type="text" name="kra_pin" class="form-control" value="{{ old('kra_pin', $staff->kra_pin ?? '') }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">NSSF</label>
            <input type="text" name="nssf" class="form-control" value="{{ old('nssf', $staff->nssf ?? '') }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">NHIF</label>
            <input type="text" name="nhif" class="form-control" value="{{ old('nhif', $staff->nhif ?? '') }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Bank</label>
            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $staff->bank_name ?? '') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Bank Branch</label>
            <input type="text" name="bank_branch" class="form-control" value="{{ old('bank_branch', $staff->bank_branch ?? '') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Bank Account</label>
            <input type="text" name="bank_account" class="form-control" value="{{ old('bank_account', $staff->bank_account ?? '') }}">
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  function previewPhoto(input){
    if(!input.files || !input.files[0]) return;
    const file = input.files[0];
    const allowed = ['image/jpeg','image/png','image/jpg'];
    if(!allowed.includes(file.type)) { alert('Only JPG/PNG allowed'); input.value=''; return; }
    if(file.size > 2*1024*1024) { alert('Image must be 2MB or smaller.'); input.value=''; return; }
    const reader = new FileReader();
    reader.onload = e => document.getElementById('photoPreview').src = e.target.result;
    reader.readAsDataURL(file);
  }
</script>
@endpush

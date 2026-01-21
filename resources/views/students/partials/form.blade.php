@php
  // $mode = 'create' | 'edit'
  // expects: $classrooms, $streams, $categories, $trips, $dropOffPoints
  // optional: $student, $familyMembers (only on edit)
  $s = $student ?? null;
@endphp

@csrf
@if($mode === 'edit') @method('PUT') @endif

<div class="card-body">

  {{-- IDENTIFIERS --}}
  <h6 class="text-uppercase text-muted mb-3">Identifiers</h6>
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">Admission Number</label>
      <input type="text" name="admission_number"
             value="{{ old('admission_number', $s->admission_number ?? '') }}"
             class="form-control"
             placeholder="{{ $mode==='create' ? 'Leave blank to auto-generate' : '' }}">
    </div>

    <div class="col-md-6">
      <label class="form-label">Link to Sibling Family <span class="badge bg-info">Optional</span></label>
      <div class="input-group">
        <input type="text" name="family_id" id="family_id"
               value="{{ old('family_id', $s->family_id ?? '') }}"
               class="form-control" placeholder="Search for existing sibling...">
        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#familySearchModal">
          <i class="bi bi-search"></i> Search Sibling
        </button>
        <button class="btn btn-outline-danger" type="button" id="familyClear"><i class="bi bi-x"></i></button>
      </div>
      <div class="form-text">
        <i class="bi bi-info-circle"></i> Search for an existing student to link this new student as their sibling. 
        Family details will be auto-populated from parent records.
      </div>
      <input type="hidden" name="copy_family_from_student_id" id="copy_family_from_student_id">
      <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" value="1" name="create_family_from_parent" id="create_family_from_parent" checked>
        <label class="form-check-label" for="create_family_from_parent">
          Create new family for this student (if no sibling selected)
        </label>
      </div>
    </div>
  </div>

  <hr class="my-4">

  {{-- STUDENT --}}
  <h6 class="text-uppercase text-muted mb-3">Student Information</h6>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">First Name <span class="text-danger">*</span></label>
      <input type="text" name="first_name" value="{{ old('first_name', $s->first_name ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Middle Name</label>
      <input type="text" name="middle_name" value="{{ old('middle_name', $s->middle_name ?? '') }}" class="form-control">
    </div>
    <div class="col-md-4">
      <label class="form-label">Last Name <span class="text-danger">*</span></label>
      <input type="text" name="last_name" value="{{ old('last_name', $s->last_name ?? '') }}" class="form-control" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">Gender <span class="text-danger">*</span></label>
      <select name="gender" class="form-select" required>
        @php $g = old('gender', $s->gender ?? ''); @endphp
        <option value="">Select</option>
    <option value="male"   @selected($g==='male')>Male</option>
    <option value="female" @selected($g==='female')>Female</option>
    <option value="other"  @selected($g==='other')>Other</option>
      </select>
    </div>
  <div class="col-md-3">
    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
    <input type="date" name="dob" value="{{ old('dob', $s && $s->dob ? (\Carbon\Carbon::parse($s->dob)->format('Y-m-d')) : '') }}" class="form-control" required>
  </div>
    <div class="col-md-3">
      <label class="form-label">NEMIS Number</label>
      <input type="text" name="nemis_number" value="{{ old('nemis_number', $s->nemis_number ?? '') }}" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">KNEC Assessment No.</label>
      <input type="text" name="knec_assessment_number" value="{{ old('knec_assessment_number', $s->knec_assessment_number ?? '') }}" class="form-control">
    </div>

  </div>

  <hr class="my-4">

  {{-- EXTENDED DEMOGRAPHICS --}}
<h6 class="text-uppercase text-muted mb-3">Class & Category</h6>
<div class="row g-3">
<div class="col-md-4">
  <label class="form-label">Classroom <span class="text-danger">*</span></label>
  <select name="classroom_id" class="form-select" required id="classroom_id">
    <option value="">Select</option>
    @foreach($classrooms as $c)
      <option value="{{ $c->id }}" @selected(old('classroom_id', $s->classroom_id ?? '') == $c->id)>{{ $c->name }}</option>
    @endforeach
  </select>
</div>
<div class="col-md-4">
  <label class="form-label">Stream</label>
    <select name="stream_id" class="form-select" id="stream_id">
    <option value="">Select</option>
    @foreach($streams as $st)
      <option value="{{ $st->id }}" data-classroom="{{ $st->classroom_id ?? '' }}" @selected(old('stream_id', $s->stream_id ?? '') == $st->id)>{{ $st->name }}</option>
    @endforeach
  </select>
  <div class="form-text text-muted" id="stream-hint"></div>
</div>
  <div class="col-md-4">
    <label class="form-label">Category <span class="text-danger">*</span></label>
    <select name="category_id" class="form-select" required>
      <option value="">Select</option>
      @foreach($categories as $cat)
        <option value="{{ $cat->id }}" @selected(old('category_id', $s->category_id ?? '') == $cat->id)>{{ $cat->name }}</option>
      @endforeach
    </select>
  </div>
</div>

<div class="row g-3 mt-2" id="previous-school-section">
  <div class="col-md-6">
    <label class="form-label">Previous Schools</label>
    <textarea name="previous_schools" class="form-control" rows="2" placeholder="List previous schools (one per line or JSON format)">{{ old('previous_schools', $s->previous_schools ?? '') }}</textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label">Transfer Reason</label>
    <textarea name="transfer_reason" class="form-control" rows="2">{{ old('transfer_reason', $s->transfer_reason ?? '') }}</textarea>
  </div>
  <div class="col-12 text-muted small">Only required for classes above Creche/Foundation (currently shown for all).</div>
</div>

<hr class="my-4">

  {{-- MEDICAL INFORMATION --}}
  <h6 class="text-uppercase text-muted mb-3">Medical Information</h6>
  <div class="row g-3">
    <div class="col-md-6">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="has_allergies" value="1" id="has_allergies" @checked(old('has_allergies', $s->has_allergies ?? false))>
        <label class="form-check-label" for="has_allergies">Has allergies?</label>
      </div>
      <textarea name="allergies_notes" class="form-control mt-2" rows="2" placeholder="Specify allergies (if any)">{{ old('allergies_notes', $s->allergies_notes ?? '') }}</textarea>
    </div>
    <div class="col-md-6">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="is_fully_immunized" value="1" id="is_fully_immunized" @checked(old('is_fully_immunized', $s->is_fully_immunized ?? false))>
        <label class="form-check-label" for="is_fully_immunized">Fully immunized?</label>
      </div>
      <label class="form-label mt-3">Preferred Hospital/Facility</label>
      <input type="text" name="preferred_hospital" value="{{ old('preferred_hospital', $s->preferred_hospital ?? '') }}" class="form-control" placeholder="e.g., ABC Medical Center">
    </div>
    <div class="col-md-12">
      <label class="form-label">Allergies</label>
      <textarea name="allergies" class="form-control" rows="2" placeholder="List any allergies">{{ old('allergies', $s->allergies ?? '') }}</textarea>
    </div>
    <div class="col-md-12">
      <label class="form-label">Chronic Conditions</label>
      <textarea name="chronic_conditions" class="form-control" rows="2" placeholder="List any chronic medical conditions">{{ old('chronic_conditions', $s->chronic_conditions ?? '') }}</textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label">Emergency Contact Name</label>
      <small class="text-muted d-block mb-1">Person we call if parents/guardians cannot be reached.</small>
      <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $s->emergency_contact_name ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
      <label class="form-label">Emergency Phone (with country code)</label>
      <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $s->emergency_contact_phone ?? '') }}" class="form-control" placeholder="+2547XXXXXXXX">
    </div>
  </div>

<hr class="my-4">

{{-- SPECIAL NEEDS --}}
<h6 class="text-uppercase text-muted mb-3">Special Needs</h6>
<div class="row g-3">
  <div class="col-md-6">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="has_special_needs" value="1" id="has_special_needs" @checked(old('has_special_needs', $s->has_special_needs ?? false))>
      <label class="form-check-label" for="has_special_needs">Has Special Needs</label>
    </div>
  </div>
  <div class="col-md-12">
    <label class="form-label">Special Needs Description</label>
    <textarea name="special_needs_description" class="form-control" rows="2">{{ old('special_needs_description', $s->special_needs_description ?? '') }}</textarea>
  </div>
  <div class="col-md-12">
    <label class="form-label">Learning Disabilities</label>
    <textarea name="learning_disabilities" class="form-control" rows="2">{{ old('learning_disabilities', $s->learning_disabilities ?? '') }}</textarea>
  </div>
</div>

  {{-- TRANSPORT --}}
  <h6 class="text-uppercase text-muted mb-3">Transport</h6>
  <div class="row g-3">
    <div class="col-md-12">
      <div class="form-check form-switch">
        @php $needsTransport = old('drop_off_point_id', $s->drop_off_point_id ?? null) || old('trip_id', $s->trip_id ?? null); @endphp
        <input class="form-check-input" type="checkbox" id="needs_transport" name="needs_transport" value="1" @checked($needsTransport)>
        <label class="form-check-label" for="needs_transport">Student needs school transport</label>
      </div>
    </div>
    <div class="col-md-6 transport-field">
      <label class="form-label">Trip</label>
      <select name="trip_id" class="form-select">
        <option value="">—</option>
        @foreach (($trips ?? []) as $trip)
          <option value="{{ $trip->id }}" @selected(old('trip_id', $s->trip_id ?? '')==$trip->id)>{{ $trip->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4 transport-field">
      <label class="form-label">Drop-off Point</label>
      <select name="drop_off_point_id" class="form-select" id="drop_off_point_id">
        <option value="">—</option>
        @foreach (($dropOffPoints ?? []) as $p)
          <option value="{{ $p->id }}" @selected(old('drop_off_point_id', $s->drop_off_point_id ?? '')==$p->id)>{{ $p->name }}</option>
        @endforeach
        <option value="other">Other (specify)</option>
      </select>
      <input type="text" name="drop_off_point_other" id="drop_off_point_other" class="form-control mt-2"
             placeholder="Type drop-off point" value="{{ old('drop_off_point_other', $s->drop_off_point_other ?? '') }}">
    </div>
    <div class="col-md-4 transport-field">
      <label class="form-label">Transport Fee (this term)</label>
      <input type="number" step="0.01" name="transport_fee_amount" class="form-control" value="{{ old('transport_fee_amount') }}" placeholder="0.00">
      <div class="form-text">Added to this term's invoice alongside other charges.</div>
    </div>
  </div>

  <hr class="my-4">

  {{-- DOCUMENTS --}}
  <h6 class="text-uppercase text-muted mb-3">Documents</h6>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Passport Photo</label>
      <input type="file" name="photo" class="form-control" accept="image/*">
      @if($s?->photo_path)
        <div class="small mt-1"><a target="_blank" href="{{ asset('storage/'.$s->photo_path) }}">Current file</a></div>
      @endif
    </div>
    <div class="col-md-6">
      <label class="form-label">Birth Certificate</label>
      <input type="file" name="birth_certificate" class="form-control" accept=".pdf,image/*">
      @if($s?->birth_certificate_path)
        <div class="small mt-1"><a target="_blank" href="{{ asset('storage/'.$s->birth_certificate_path) }}">Current file</a></div>
      @endif
    </div>
  </div>

  <hr class="my-4">

  {{-- PARENT / GUARDIAN --}}
  <h6 class="text-uppercase text-muted mb-3">Parent / Guardian</h6>
  @php $p = $s->parent ?? null; @endphp
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">Marital Status</label>
      <select name="marital_status" class="form-select">
        <option value="">Select</option>
        <option value="married" @selected(old('marital_status', $p->marital_status ?? '')=='married')>Married</option>
        <option value="single_parent" @selected(old('marital_status', $p->marital_status ?? '')=='single_parent')>Single Parent</option>
        <option value="co_parenting" @selected(old('marital_status', $p->marital_status ?? '')=='co_parenting')>Co-parenting</option>
      </select>
    </div>
    {{-- Father --}}
    <div class="col-12"><div class="fw-semibold mb-1">Father</div></div>
    <div class="col-md-3"><label class="form-label">Name</label>
      <input type="text" name="father_name" value="{{ old('father_name', $p->father_name ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Phone</label>
      <div class="input-group">
        <select name="father_phone_country_code" class="form-select" style="max-width:170px">
          @php
            $fatherCountryCode = old('father_phone_country_code', $p->father_phone_country_code ?? '+254');
            // Normalize country code
            $fatherCountryCode = strtolower($fatherCountryCode) === '+ke' || strtolower($fatherCountryCode) === 'ke' ? '+254' : $fatherCountryCode;
            $fatherLocalPhone = extract_local_phone($p->father_phone ?? '', $fatherCountryCode);
          @endphp
          @foreach(($countryCodes ?? [['code'=>'+254','label'=>'Kenya (+254)']]) as $cc)
            <option value="{{ $cc['code'] }}" @selected($fatherCountryCode == $cc['code'])>{{ $cc['label'] }}</option>
          @endforeach
        </select>
        <input type="text" name="father_phone" value="{{ old('father_phone', $fatherLocalPhone) }}" class="form-control" placeholder="7XXXXXXXX">
      </div>
    </div>
    <div class="col-md-3"><label class="form-label">WhatsApp</label>
      <div class="input-group">
        <select name="father_whatsapp_country_code" class="form-select" style="max-width:170px" disabled>
          @foreach(($countryCodes ?? [['code'=>'+254','label'=>'Kenya (+254)']]) as $cc)
            <option value="{{ $cc['code'] }}" @selected($fatherCountryCode == $cc['code'])>{{ $cc['label'] }}</option>
          @endforeach
        </select>
        <input type="text" name="father_whatsapp" value="{{ old('father_whatsapp', extract_local_phone($p->father_whatsapp ?? '', $fatherCountryCode)) }}" class="form-control" placeholder="Same code as phone">
      </div>
      <small class="text-muted">Uses same country code as phone</small>
    </div>
    <div class="col-md-3"><label class="form-label">Email</label>
      <input type="email" name="father_email" value="{{ old('father_email', $p->father_email ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">ID Number</label>
      <input type="text" name="father_id_number" value="{{ old('father_id_number', $p->father_id_number ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">ID Document (upload)</label>
      <input type="file" name="father_id_document" class="form-control" accept=".pdf,image/*">
      @if($p?->father_id_document)
        <div class="small mt-1"><a target="_blank" href="{{ asset('storage/'.$p->father_id_document) }}">Current file</a></div>
      @endif
    </div>

    {{-- Mother --}}
    <div class="col-12 mt-2"><div class="fw-semibold mb-1">Mother</div></div>
    <div class="col-md-3"><label class="form-label">Name</label>
      <input type="text" name="mother_name" value="{{ old('mother_name', $p->mother_name ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Phone</label>
      <div class="input-group">
        <select name="mother_phone_country_code" class="form-select" style="max-width:170px">
          @php
            $motherCountryCode = old('mother_phone_country_code', $p->mother_phone_country_code ?? '+254');
            // Normalize country code
            $motherCountryCode = strtolower($motherCountryCode) === '+ke' || strtolower($motherCountryCode) === 'ke' ? '+254' : $motherCountryCode;
            $motherLocalPhone = extract_local_phone($p->mother_phone ?? '', $motherCountryCode);
          @endphp
          @foreach(($countryCodes ?? [['code'=>'+254','label'=>'Kenya (+254)']]) as $cc)
            <option value="{{ $cc['code'] }}" @selected($motherCountryCode == $cc['code'])>{{ $cc['label'] }}</option>
          @endforeach
        </select>
        <input type="text" name="mother_phone" value="{{ old('mother_phone', $motherLocalPhone) }}" class="form-control" placeholder="7XXXXXXXX">
      </div>
    </div>
    <div class="col-md-3"><label class="form-label">WhatsApp</label>
      <div class="input-group">
        <select name="mother_whatsapp_country_code" class="form-select" style="max-width:170px" disabled>
          @foreach(($countryCodes ?? [['code'=>'+254','label'=>'Kenya (+254)']]) as $cc)
            <option value="{{ $cc['code'] }}" @selected($motherCountryCode == $cc['code'])>{{ $cc['label'] }}</option>
          @endforeach
        </select>
        <input type="text" name="mother_whatsapp" value="{{ old('mother_whatsapp', extract_local_phone($p->mother_whatsapp ?? '', $motherCountryCode)) }}" class="form-control" placeholder="Same code as phone">
      </div>
      <small class="text-muted">Uses same country code as phone</small>
    </div>
    <div class="col-md-3"><label class="form-label">Email</label>
      <input type="email" name="mother_email" value="{{ old('mother_email', $p->mother_email ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">ID Number</label>
      <input type="text" name="mother_id_number" value="{{ old('mother_id_number', $p->mother_id_number ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">ID Document (upload)</label>
      <input type="file" name="mother_id_document" class="form-control" accept=".pdf,image/*">
      @if($p?->mother_id_document)
        <div class="small mt-1"><a target="_blank" href="{{ asset('storage/'.$p->mother_id_document) }}">Current file</a></div>
      @endif
    </div>

    {{-- Guardian --}}
    <div class="col-12 mt-2"><div class="fw-semibold mb-1">Guardian (optional)</div></div>
    <div class="col-md-3"><label class="form-label">Name</label>
      <input type="text" name="guardian_name" value="{{ old('guardian_name', $p->guardian_name ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Phone</label>
      <div class="input-group">
        <select name="guardian_phone_country_code" class="form-select" style="max-width:170px">
          @php
            $guardianCountryCode = old('guardian_phone_country_code', $p->guardian_phone_country_code ?? '+254');
            // Normalize country code
            $guardianCountryCode = strtolower($guardianCountryCode) === '+ke' || strtolower($guardianCountryCode) === 'ke' ? '+254' : $guardianCountryCode;
            $guardianLocalPhone = extract_local_phone($p->guardian_phone ?? '', $guardianCountryCode);
          @endphp
          @foreach(($countryCodes ?? [['code'=>'+254','label'=>'Kenya (+254)']]) as $cc)
            <option value="{{ $cc['code'] }}" @selected($guardianCountryCode == $cc['code'])>{{ $cc['label'] }}</option>
          @endforeach
        </select>
        <input type="text" name="guardian_phone" value="{{ old('guardian_phone', $guardianLocalPhone) }}" class="form-control" placeholder="7XXXXXXXX">
      </div>
    </div>
    <div class="col-md-3"><label class="form-label">WhatsApp</label>
      <div class="input-group">
        <select name="guardian_whatsapp_country_code" class="form-select" style="max-width:170px" disabled>
          @foreach(($countryCodes ?? [['code'=>'+254','label'=>'Kenya (+254)']]) as $cc)
            <option value="{{ $cc['code'] }}" @selected($guardianCountryCode == $cc['code'])>{{ $cc['label'] }}</option>
          @endforeach
        </select>
        <input type="text" name="guardian_whatsapp" value="{{ old('guardian_whatsapp', extract_local_phone($p->guardian_whatsapp ?? '', $guardianCountryCode)) }}" class="form-control" placeholder="Same code as phone">
      </div>
      <small class="text-muted">Uses same country code as phone</small>
    </div>
  <div class="col-md-3"><label class="form-label">Relationship</label>
    <input type="text" name="guardian_relationship" value="{{ old('guardian_relationship', $p->guardian_relationship ?? '') }}" class="form-control"></div>
    <div class="col-md-6">
      <label class="form-label">Residential Area <span class="text-danger">*</span></label>
      <input type="text" name="residential_area" value="{{ old('residential_area', $s->residential_area ?? '') }}" class="form-control" placeholder="Estate / Area" required>
    </div>
  </div>

  {{-- SIBLINGS LIST (edit mode) --}}
  @if($mode==='edit' && !empty($familyMembers))
    <hr class="my-4">
    <h6 class="text-uppercase text-muted mb-3">Mapped Siblings</h6>
    @if(count($familyMembers))
      <div class="d-flex flex-wrap gap-2">
        @foreach($familyMembers as $fm)
          <a href="{{ route('students.show', $fm->id) }}" class="badge bg-light border text-dark text-decoration-none">
            {{ $fm->admission_number }} — {{ $fm->full_name }}
          </a>
        @endforeach
      </div>
      <div class="form-text">Siblings are students sharing the same Family ID.</div>
    @else
      <div class="text-muted">No siblings mapped yet.</div>
    @endif
  @endif

  {{-- STATUS & LIFECYCLE --}}
  @if($mode === 'edit')
  <hr class="my-4">
  <h6 class="text-uppercase text-muted mb-3">Status & Lifecycle</h6>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="active" @selected(old('status', $s->status ?? 'active')=='active')>Active</option>
        <option value="inactive" @selected(old('status', $s->status ?? '')=='inactive')>Inactive</option>
        <option value="graduated" @selected(old('status', $s->status ?? '')=='graduated')>Graduated</option>
        <option value="transferred" @selected(old('status', $s->status ?? '')=='transferred')>Transferred</option>
        <option value="expelled" @selected(old('status', $s->status ?? '')=='expelled')>Expelled</option>
        <option value="suspended" @selected(old('status', $s->status ?? '')=='suspended')>Suspended</option>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Admission Date</label>
      <input type="date" name="admission_date" class="form-control" value="{{ old('admission_date', $s->admission_date?->toDateString() ?? '') }}">
    </div>
    <div class="col-md-6">
      <label class="form-label">Graduation Date</label>
      <input type="date" name="graduation_date" class="form-control" value="{{ old('graduation_date', $s->graduation_date?->toDateString() ?? '') }}">
    </div>
    <div class="col-md-6">
      <label class="form-label">Transfer Date</label>
      <input type="date" name="transfer_date" class="form-control" value="{{ old('transfer_date', $s->transfer_date?->toDateString() ?? '') }}">
    </div>
    <div class="col-md-6">
      <label class="form-label">Transfer To School</label>
      <input type="text" name="transfer_to_school" class="form-control" value="{{ old('transfer_to_school', $s->transfer_to_school ?? '') }}">
    </div>
    <div class="col-md-12">
      <label class="form-label">Status Change Reason</label>
      <textarea name="status_change_reason" class="form-control" rows="2">{{ old('status_change_reason', $s->status_change_reason ?? '') }}</textarea>
    </div>
    <div class="col-md-6">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_readmission" value="1" id="is_readmission" @checked(old('is_readmission', $s->is_readmission ?? false))>
        <label class="form-check-label" for="is_readmission">Is Re-admission</label>
      </div>
    </div>
  </div>
  @else
  {{-- For create mode, just set default status --}}
  <input type="hidden" name="status" value="active">
  <input type="hidden" name="admission_date" value="{{ today()->toDateString() }}">
  @endif

</div>

<div class="card-footer d-flex justify-content-end gap-2">
  <a href="{{ $mode==='edit' && $s ? route('students.show',$s->id) : route('students.index') }}" class="btn btn-outline-secondary">Cancel</a>
  @if($mode==='create')
    <button type="submit" name="save_add_another" value="1" class="btn btn-secondary">
      <i class="bi bi-plus-circle"></i> Save & Add Another
    </button>
  @endif
  <button type="submit" class="btn btn-{{ $mode==='edit' ? 'primary' : 'success' }}">
    <i class="bi bi-{{ $mode==='edit' ? 'save' : 'check-lg' }}"></i>
    {{ $mode==='edit' ? 'Update' : 'Submit Admission' }}
  </button>
</div>

{{-- Family search modal --}}
<div class="modal fade" id="familySearchModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Search Student to Link Family</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="input-group mb-2">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" id="fs_query" class="form-control" placeholder="Type a name or admission #">
        </div>
        <div id="fs_results" class="list-group small"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  // dependent streams (optional: if your getStreams uses classroom_id)
  (function(){
    const classroomSelect = document.querySelector('select[name="classroom_id"]');
    const streamSelect = document.querySelector('select[name="stream_id"]');

    function loadStreams(classroomId, preselect = '{{ old('stream_id', $s->stream_id ?? '') }}') {
      if (!classroomId) { streamSelect.innerHTML = '<option value="">Select Stream</option>'; return; }
      fetch('{{ route('students.getStreams') }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}','Content-Type': 'application/json'},
        body: JSON.stringify({ classroom_id: classroomId })
      }).then(r=>r.json()).then(rows=>{
        const opts = ['<option value="">Select Stream</option>'];
        rows.forEach(s=>{
          const sel = (String(s.id) === String(preselect)) ? 'selected' : '';
          opts.push(`<option value="${s.id}" ${sel}>${s.name}</option>`);
        });
        streamSelect.innerHTML = opts.join('');
      }).catch(()=>{});
    }
    classroomSelect?.addEventListener('change', e => loadStreams(e.target.value, ''));
    if (classroomSelect?.value) loadStreams(classroomSelect.value);
  })();

  // family: clear
  document.getElementById('familyClear')?.addEventListener('click', ()=>{
    document.getElementById('family_id').value = '';
    document.getElementById('copy_family_from_student_id').value = '';
  });

  // transport visibility + other drop-off input
  (function(){
    const toggle = document.getElementById('needs_transport');
    const fields = document.querySelectorAll('.transport-field');
    const dropSelect = document.getElementById('drop_off_point_id');
    const dropOther = document.getElementById('drop_off_point_other');

    function syncFields() {
      const enabled = toggle?.checked;
      fields.forEach(f => f.style.display = enabled ? '' : 'none');
      if (!enabled) {
        dropOther.value = '';
        if (dropSelect) dropSelect.value = '';
      }
      syncOther();
    }

    function syncOther() {
      if (!dropSelect || !dropOther) return;
      const showOther = dropSelect.value === 'other';
      dropOther.style.display = showOther ? '' : 'none';
      if (!showOther) dropOther.value = '';
    }

    toggle?.addEventListener('change', syncFields);
    dropSelect?.addEventListener('change', syncOther);
    syncFields();
  })();

  // stream required if classroom has streams (based on data-classroom on stream options)
  (function(){
    const classroomSelect = document.querySelector('select[name="classroom_id"]');
    const streamSelect = document.querySelector('select[name="stream_id"]');
    const hint = document.getElementById('stream-hint');
    function updateRequirement() {
      if (!classroomSelect || !streamSelect) return;
      const cls = classroomSelect.value;
      let hasStreams = false;
      streamSelect.querySelectorAll('option').forEach(opt => {
        if (opt.value && opt.getAttribute('data-classroom') === cls) {
          hasStreams = true;
        }
      });
      if (hasStreams) {
        streamSelect.setAttribute('required', 'required');
        if (hint) hint.textContent = 'Stream is required for the selected classroom.';
      } else {
        streamSelect.removeAttribute('required');
        if (hint) hint.textContent = '';
      }
    }
    classroomSelect?.addEventListener('change', updateRequirement);
    updateRequirement();
  })();

  // family: search & select
  (function(){
    const q = document.getElementById('fs_query');
    const box = document.getElementById('fs_results');

    let t=null;
    q?.addEventListener('input', ()=>{
      clearTimeout(t);
      t = setTimeout(async ()=>{
        const val = q.value.trim();
        if (!val) { box.innerHTML=''; return; }
        const res = await fetch(`{{ route('api.students.search') }}?q=`+encodeURIComponent(val));
        const rows = await res.json();
        box.innerHTML = '';
        rows.forEach(r=>{
          const a = document.createElement('a');
          a.href = '#';
          a.className = 'list-group-item list-group-item-action';
          a.textContent = `${r.admission_number} — ${r.full_name}`;
          a.addEventListener('click', (e)=>{
            e.preventDefault();
            // set hidden field to copy family from this student
            document.getElementById('copy_family_from_student_id').value = r.id;
            // UI hint: also set Family ID placeholder to "will copy"
            document.getElementById('family_id').value = '';
            const modal = bootstrap.Modal.getInstance(document.getElementById('familySearchModal'));
            modal?.hide();
          });
          box.appendChild(a);
        });
      }, 300);
    });
  })();
</script>
@endpush

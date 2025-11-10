@php
  // $mode = 'create' | 'edit'
  // expects: $classrooms, $streams, $categories, $transportRoutes (optional)
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
      <label class="form-label">First Name</label>
      <input type="text" name="first_name" value="{{ old('first_name', $s->first_name ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Middle Name</label>
      <input type="text" name="middle_name" value="{{ old('middle_name', $s->middle_name ?? '') }}" class="form-control">
    </div>
    <div class="col-md-4">
      <label class="form-label">Last Name</label>
      <input type="text" name="last_name" value="{{ old('last_name', $s->last_name ?? '') }}" class="form-control" required>
    </div>

    <div class="col-md-3">
      <label class="form-label">Gender</label>
      <select name="gender" class="form-select" required>
        @php $g = old('gender', $s->gender ?? ''); @endphp
        <option value="">Select</option>
        <option value="male"   @selected($g==='male')>Male</option>
        <option value="female" @selected($g==='female')>Female</option>
        <option value="other"  @selected($g==='other')>Other</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Date of Birth</label>
      <input type="date" name="dob" value="{{ old('dob', $s->dob ?? '') }}" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">NEMIS Number</label>
      <input type="text" name="nemis_number" value="{{ old('nemis_number', $s->nemis_number ?? '') }}" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">KNEC Assessment No.</label>
      <input type="text" name="knec_assessment_number" value="{{ old('knec_assessment_number', $s->knec_assessment_number ?? '') }}" class="form-control">
    </div>

    <div class="col-md-3">
      <label class="form-label">National ID Number</label>
      <input type="text" name="national_id_number" value="{{ old('national_id_number', $s->national_id_number ?? '') }}" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">Passport Number</label>
      <input type="text" name="passport_number" value="{{ old('passport_number', $s->passport_number ?? '') }}" class="form-control">
    </div>
  </div>

  <hr class="my-4">

  {{-- EXTENDED DEMOGRAPHICS --}}
  <h6 class="text-uppercase text-muted mb-3">Extended Demographics</h6>
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">Religion</label>
      <input type="text" name="religion" value="{{ old('religion', $s->religion ?? '') }}" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">Ethnicity</label>
      <input type="text" name="ethnicity" value="{{ old('ethnicity', $s->ethnicity ?? '') }}" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">Language Preference</label>
      <input type="text" name="language_preference" value="{{ old('language_preference', $s->language_preference ?? '') }}" class="form-control" placeholder="e.g., English, Swahili">
    </div>
    <div class="col-md-3">
      <label class="form-label">Blood Group</label>
      <select name="blood_group" class="form-select">
        <option value="">Select</option>
        <option value="A+" @selected(old('blood_group', $s->blood_group ?? '')=='A+')>A+</option>
        <option value="A-" @selected(old('blood_group', $s->blood_group ?? '')=='A-')>A-</option>
        <option value="B+" @selected(old('blood_group', $s->blood_group ?? '')=='B+')>B+</option>
        <option value="B-" @selected(old('blood_group', $s->blood_group ?? '')=='B-')>B-</option>
        <option value="AB+" @selected(old('blood_group', $s->blood_group ?? '')=='AB+')>AB+</option>
        <option value="AB-" @selected(old('blood_group', $s->blood_group ?? '')=='AB-')>AB-</option>
        <option value="O+" @selected(old('blood_group', $s->blood_group ?? '')=='O+')>O+</option>
        <option value="O-" @selected(old('blood_group', $s->blood_group ?? '')=='O-')>O-</option>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Home Address</label>
      <input type="text" name="home_address" value="{{ old('home_address', $s->home_address ?? '') }}" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">City</label>
      <input type="text" name="home_city" value="{{ old('home_city', $s->home_city ?? '') }}" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">County</label>
      <input type="text" name="home_county" value="{{ old('home_county', $s->home_county ?? '') }}" class="form-control">
    </div>
    <div class="col-md-3">
      <label class="form-label">Postal Code</label>
      <input type="text" name="home_postal_code" value="{{ old('home_postal_code', $s->home_postal_code ?? '') }}" class="form-control">
    </div>
    <div class="col-md-12">
      <label class="form-label">Previous Schools</label>
      <textarea name="previous_schools" class="form-control" rows="2" placeholder="List previous schools (one per line or JSON format)">{{ old('previous_schools', $s->previous_schools ?? '') }}</textarea>
    </div>
    <div class="col-md-12">
      <label class="form-label">Transfer Reason</label>
      <textarea name="transfer_reason" class="form-control" rows="2">{{ old('transfer_reason', $s->transfer_reason ?? '') }}</textarea>
    </div>
  </div>

  <hr class="my-4">

  {{-- MEDICAL INFORMATION --}}
  <h6 class="text-uppercase text-muted mb-3">Medical Information</h6>
  <div class="row g-3">
    <div class="col-md-12">
      <label class="form-label">Allergies</label>
      <textarea name="allergies" class="form-control" rows="2" placeholder="List any allergies">{{ old('allergies', $s->allergies ?? '') }}</textarea>
    </div>
    <div class="col-md-12">
      <label class="form-label">Chronic Conditions</label>
      <textarea name="chronic_conditions" class="form-control" rows="2" placeholder="List any chronic medical conditions">{{ old('chronic_conditions', $s->chronic_conditions ?? '') }}</textarea>
    </div>
    <div class="col-md-6">
      <label class="form-label">Medical Insurance Provider</label>
      <input type="text" name="medical_insurance_provider" value="{{ old('medical_insurance_provider', $s->medical_insurance_provider ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
      <label class="form-label">Medical Insurance Number</label>
      <input type="text" name="medical_insurance_number" value="{{ old('medical_insurance_number', $s->medical_insurance_number ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
      <label class="form-label">Emergency Medical Contact Name</label>
      <input type="text" name="emergency_medical_contact_name" value="{{ old('emergency_medical_contact_name', $s->emergency_medical_contact_name ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
      <label class="form-label">Emergency Medical Contact Phone</label>
      <input type="text" name="emergency_medical_contact_phone" value="{{ old('emergency_medical_contact_phone', $s->emergency_medical_contact_phone ?? '') }}" class="form-control">
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

  <hr class="my-4">

  {{-- CLASS & CATEGORY --}}
  <h6 class="text-uppercase text-muted mb-3">Class & Category</h6>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Class</label>
      <select name="classroom_id" class="form-select" required>
        <option value="">Select Class</option>
        @foreach ($classrooms as $c)
          <option value="{{ $c->id }}" @selected(old('classroom_id', $s->classroom_id ?? '')==$c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Stream</label>
      <select name="stream_id" class="form-select">
        <option value="">Select Stream</option>
        @foreach ($streams as $st)
          <option value="{{ $st->id }}" @selected(old('stream_id', $s->stream_id ?? '')==$st->id)>{{ $st->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Category</label>
      <select name="category_id" class="form-select">
        <option value="">Select Category</option>
        @foreach ($categories as $cat)
          <option value="{{ $cat->id }}" @selected(old('category_id', $s->category_id ?? '')==$cat->id)>{{ $cat->name }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <hr class="my-4">

  {{-- TRANSPORT --}}
  <h6 class="text-uppercase text-muted mb-3">Transport</h6>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Route</label>
      <select name="route_id" class="form-select">
        <option value="">—</option>
        @foreach (($transportRoutes ?? []) as $r)
          <option value="{{ $r->id }}" @selected(old('route_id', $s->route_id ?? '')==$r->id)>{{ $r->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Drop-off Point</label>
      <input type="text" name="drop_off_point" value="{{ old('drop_off_point', $s->drop_off_point ?? '') }}" class="form-control">
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
    {{-- Father --}}
    <div class="col-12"><div class="fw-semibold mb-1">Father</div></div>
    <div class="col-md-3"><label class="form-label">Name</label>
      <input type="text" name="father_name" value="{{ old('father_name', $p->father_name ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Phone</label>
      <input type="text" name="father_phone" value="{{ old('father_phone', $p->father_phone ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">WhatsApp</label>
      <input type="text" name="father_whatsapp" value="{{ old('father_whatsapp', $p->father_whatsapp ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Email</label>
      <input type="email" name="father_email" value="{{ old('father_email', $p->father_email ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">ID Number</label>
      <input type="text" name="father_id_number" value="{{ old('father_id_number', $p->father_id_number ?? '') }}" class="form-control"></div>

    {{-- Mother --}}
    <div class="col-12 mt-2"><div class="fw-semibold mb-1">Mother</div></div>
    <div class="col-md-3"><label class="form-label">Name</label>
      <input type="text" name="mother_name" value="{{ old('mother_name', $p->mother_name ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Phone</label>
      <input type="text" name="mother_phone" value="{{ old('mother_phone', $p->mother_phone ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">WhatsApp</label>
      <input type="text" name="mother_whatsapp" value="{{ old('mother_whatsapp', $p->mother_whatsapp ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Email</label>
      <input type="email" name="mother_email" value="{{ old('mother_email', $p->mother_email ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">ID Number</label>
      <input type="text" name="mother_id_number" value="{{ old('mother_id_number', $p->mother_id_number ?? '') }}" class="form-control"></div>

    {{-- Guardian --}}
    <div class="col-12 mt-2"><div class="fw-semibold mb-1">Guardian (optional)</div></div>
    <div class="col-md-3"><label class="form-label">Name</label>
      <input type="text" name="guardian_name" value="{{ old('guardian_name', $p->guardian_name ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Phone</label>
      <input type="text" name="guardian_phone" value="{{ old('guardian_phone', $p->guardian_phone ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Email</label>
      <input type="email" name="guardian_email" value="{{ old('guardian_email', $p->guardian_email ?? '') }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label">Relationship</label>
      <input type="text" name="guardian_relationship" value="{{ old('guardian_relationship', $p->guardian_relationship ?? '') }}" class="form-control"></div>
  </div>

  {{-- SIBLINGS LIST (edit mode) --}}
  @if($mode==='edit' && !empty($familyMembers))
    <hr class="my-4">
    <h6 class="text-uppercase text-muted mb-3">Mapped Siblings</h6>
    @if(count($familyMembers))
      <div class="d-flex flex-wrap gap-2">
        @foreach($familyMembers as $fm)
          <a href="{{ route('students.show', $fm->id) }}" class="badge bg-light border text-dark text-decoration-none">
            {{ $fm->admission_number }} — {{ $fm->first_name }} {{ $fm->last_name }}
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

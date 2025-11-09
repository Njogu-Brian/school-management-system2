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

    <div class="col-md-3">
      <label class="form-label">Family ID</label>
      <div class="input-group">
        <input type="text" name="family_id" id="family_id"
               value="{{ old('family_id', $s->family_id ?? '') }}"
               class="form-control" placeholder="—">
        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#familySearchModal">
          Link via Student
        </button>
        <button class="btn btn-outline-danger" type="button" id="familyClear">Clear</button>
      </div>
      <div class="form-text">Pick an existing student → we’ll copy their family.</div>
      <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" value="1" name="create_family_from_parent" id="create_family_from_parent">
        <label class="form-check-label" for="create_family_from_parent">
          Create new family from this student's parent info (if Family ID is empty)
        </label>
      </div>
    </div>

    <div class="col-md-3">
      <label class="form-label">Sibling (optional)</label>
      <input type="text" class="form-control" placeholder="Use search → pick a student" disabled>
      <input type="hidden" name="copy_family_from_student_id" id="copy_family_from_student_id">
      <div class="form-text">Choosing a student in the modal sets Family ID automatically.</div>
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

    <div class="col-md-6">
      <label class="form-label">Previous School</label>
      <input type="text" name="previous_school" value="{{ old('previous_school', $s->previous_school ?? '') }}" class="form-control">
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

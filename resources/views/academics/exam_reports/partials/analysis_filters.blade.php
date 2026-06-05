@php
  $analysisFilterData = [
    'classrooms' => ($classrooms ?? collect())->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
    'classroomExamTypeIds' => $classroomExamTypeIds ?? [],
    'termYearClassScopes' => $termYearClassScopes ?? [],
    'streamsByClassroom' => $streamsByClassroom ?? [],
  ];
@endphp
<script type="application/json" id="analysis-filter-data">@json($analysisFilterData)</script>

<div class="settings-card mb-3">
  <div class="card-body">
    <form method="GET" id="analysisFilterForm" class="row g-3 align-items-end">
      <div class="col-12">
        <label class="form-label d-block mb-2">Analysis scope</label>
        <div class="btn-group flex-wrap" role="group">
          <input type="radio" class="btn-check" name="analysis_flow" id="analysis_flow_exam" value="by_exam_type" autocomplete="off"
                 @checked(($analysisFlow ?? 'by_exam_type') === 'by_exam_type')>
          <label class="btn btn-outline-primary" for="analysis_flow_exam">Single exam</label>
          <input type="radio" class="btn-check" name="analysis_flow" id="analysis_flow_term" value="term" autocomplete="off"
                 @checked(($analysisFlow ?? '') === 'term')>
          <label class="btn btn-outline-primary" for="analysis_flow_term">Whole term</label>
        </div>
      </div>

      <div class="col-md-3">
        <label class="form-label">Academic year</label>
        <select name="academic_year_id" class="form-select" id="analysis_year_id">
          <option value="">Select year</option>
          @foreach($academicYears ?? [] as $y)
            <option value="{{ $y->id }}" @selected(request('academic_year_id') == $y->id)>{{ $y->year }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Term</label>
        <select name="term_id" class="form-select" id="analysis_term_id">
          <option value="">Select term</option>
          @foreach($terms ?? [] as $t)
            <option value="{{ $t->id }}" data-academic-year-id="{{ $t->academic_year_id }}" @selected(request('term_id') == $t->id)>
              {{ $t->academicYear->year ?? '' }} · {{ $t->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-3 flow-exam-only {{ ($analysisFlow ?? 'by_exam_type') === 'by_exam_type' ? '' : 'd-none' }}">
        <label class="form-label">Exam type</label>
        <select name="exam_type_id" class="form-select" id="analysis_exam_type_id">
          <option value="">Select type</option>
          @foreach($examTypes ?? [] as $et)
            <option value="{{ $et->id }}" @selected(request('exam_type_id') == $et->id)>{{ $et->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Class</label>
        <select name="classroom_id" class="form-select" id="analysis_classroom_id" required>
          <option value="">Select class</option>
        </select>
        <div class="form-text text-muted small d-none" id="analysis_class_hint">Pick year and term to list classes with exam data.</div>
      </div>

      <div class="col-md-3 d-none" id="analysisStreamWrap">
        <label class="form-label">Stream</label>
        <select name="stream_id" class="form-select" id="analysis_stream_id">
          <option value="">All streams</option>
        </select>
      </div>

      @if(!empty($showSchoolScope))
        <div class="col-md-2">
          <label class="form-label">Scope</label>
          <select name="scope" class="form-select" id="analysis_scope">
            <option value="class" @selected(($scope ?? 'class') === 'class')>Class</option>
            @if(!empty($examReportsFullAccess))
              <option value="school" @selected(($scope ?? 'class') === 'school')>School-wide</option>
            @endif
          </select>
        </div>
      @endif

      <div class="col-12 d-flex justify-content-end gap-2">
        <button type="submit" name="load" value="1" class="btn btn-settings-primary">Generate</button>
      </div>
    </form>
    @if(!empty($subjectScopedTeacher))
      <p class="text-muted small mb-0 mt-2"><i class="bi bi-info-circle"></i> Showing only subjects assigned to you.</p>
    @endif
  </div>
</div>

@push('scripts')
<script>
(function () {
  const form = document.getElementById('analysisFilterForm');
  if (!form) return;

  const raw = document.getElementById('analysis-filter-data');
  const DATA = raw ? JSON.parse(raw.textContent) : {};
  const CLASSROOMS = DATA.classrooms || [];
  const EXAM_TYPES_BY_CLASS = DATA.classroomExamTypeIds || {};
  const TERM_SCOPES = DATA.termYearClassScopes || [];
  const STREAMS_BY_CLASS = DATA.streamsByClassroom || {};

  const yearSel = document.getElementById('analysis_year_id');
  const termSel = document.getElementById('analysis_term_id');
  const classSel = document.getElementById('analysis_classroom_id');
  const classHint = document.getElementById('analysis_class_hint');
  const streamWrap = document.getElementById('analysisStreamWrap');
  const streamSel = document.getElementById('analysis_stream_id');
  const scopeSel = document.getElementById('analysis_scope');

  function currentFlow() {
    return form.querySelector('input[name="analysis_flow"]:checked')?.value || 'by_exam_type';
  }

  function filterTermsByYear() {
    const y = yearSel?.value;
    const currentTerm = termSel?.value;
    let valid = false;
    termSel?.querySelectorAll('option').forEach(function (opt) {
      if (!opt.value) { opt.hidden = false; return; }
      const show = y && String(opt.getAttribute('data-academic-year-id')) === String(y);
      opt.hidden = !show;
      if (show && opt.value === currentTerm) valid = true;
    });
    if (!y || !valid) termSel.value = '';
  }

  function examTypeClassIds(examTypeId) {
    if (!examTypeId) return [];
    const et = parseInt(examTypeId, 10);
    const out = [];
    Object.keys(EXAM_TYPES_BY_CLASS).forEach(function (cid) {
      if ((EXAM_TYPES_BY_CLASS[cid] || []).map(Number).includes(et)) out.push(parseInt(cid, 10));
    });
    return out;
  }

  function termClassIds(yearId, termId) {
    if (!yearId || !termId) return [];
    const y = parseInt(yearId, 10), t = parseInt(termId, 10);
    const out = [];
    TERM_SCOPES.forEach(function (row) {
      if (row.academic_year_id === y && row.term_id === t) out.push(row.classroom_id);
    });
    return [...new Set(out)];
  }

  function fillClasses(ids) {
    const prev = @json(request()->filled('classroom_id') ? (int) request('classroom_id') : null);
    const allowed = new Set(ids.map(String));
    classSel.innerHTML = '<option value="">Select class</option>';
    CLASSROOMS.forEach(function (c) {
      if (!allowed.has(String(c.id))) return;
      const o = document.createElement('option');
      o.value = c.id;
      o.textContent = c.name;
      if (prev != null && Number(c.id) === Number(prev)) o.selected = true;
      classSel.appendChild(o);
    });
    classSel.disabled = ids.length === 0;
    if (classHint) classHint.classList.toggle('d-none', ids.length > 0);
    syncStreams();
  }

  function syncClasses() {
    const flow = currentFlow();
    const y = yearSel?.value;
    const t = termSel?.value;
    if (!y || !t) {
      classSel.innerHTML = '<option value="">Select class</option>';
      classSel.disabled = true;
      if (classHint) classHint.classList.remove('d-none');
      syncStreams();
      return;
    }
    let ids;
    if (flow === 'term') {
      ids = termClassIds(y, t);
    } else {
      const et = document.getElementById('analysis_exam_type_id')?.value;
      if (!et) {
        classSel.innerHTML = '<option value="">Select class</option>';
        classSel.disabled = true;
        if (classHint) classHint.classList.remove('d-none');
        syncStreams();
        return;
      }
      const typeClasses = examTypeClassIds(et);
      ids = termClassIds(y, t).filter(function (id) { return typeClasses.includes(id); });
    }
    fillClasses(ids);
  }

  function syncStreams() {
    if (!streamWrap || !streamSel) return;
    const cid = classSel?.value ? parseInt(classSel.value, 10) : null;
    const list = cid ? (STREAMS_BY_CLASS[String(cid)] || []) : [];
    const reqStream = @json(request()->filled('stream_id') ? (int) request('stream_id') : null);
    streamSel.innerHTML = '<option value="">All streams</option>';
    if (!list.length) {
      streamWrap.classList.add('d-none');
      streamSel.value = '';
      return;
    }
    streamWrap.classList.remove('d-none');
    list.forEach(function (st) {
      const o = document.createElement('option');
      o.value = st.id;
      o.textContent = st.name;
      if (reqStream != null && Number(st.id) === Number(reqStream)) o.selected = true;
      streamSel.appendChild(o);
    });
  }

  function syncFlow() {
    const flow = currentFlow();
    document.querySelectorAll('.flow-exam-only').forEach(function (el) {
      el.classList.toggle('d-none', flow !== 'by_exam_type');
    });
    const examType = document.getElementById('analysis_exam_type_id');
    if (examType) examType.disabled = flow !== 'by_exam_type';
    if (scopeSel) {
      const school = scopeSel.value === 'school';
      classSel.disabled = school;
      streamSel.disabled = school;
    }
    syncClasses();
  }

  yearSel?.addEventListener('change', function () { filterTermsByYear(); syncClasses(); });
  termSel?.addEventListener('change', syncClasses);
  document.getElementById('analysis_exam_type_id')?.addEventListener('change', syncClasses);
  classSel?.addEventListener('change', syncStreams);
  scopeSel?.addEventListener('change', syncFlow);
  form.querySelectorAll('input[name="analysis_flow"]').forEach(function (r) {
    r.addEventListener('change', syncFlow);
  });

  filterTermsByYear();
  syncFlow();
})();
</script>
@endpush

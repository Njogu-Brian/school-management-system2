@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    @include('academics.exam_reports.partials.exam_report_print_css')
    @include('academics.exam_reports.partials.cbc_grade_styles')
    <style>
      .class-sheet-filter-row > [class*="col-"] {
        display: flex;
        flex-direction: column;
      }
      .class-sheet-filter-row .form-label {
        margin-bottom: 0.35rem;
      }
      .class-sheet-filter-row .form-select {
        width: 100%;
      }
    </style>
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 no-print">
      <div>
        <div class="crumb">Academics · Exam Reports &amp; Analysis</div>
        <h1 class="mb-1">Class Mark Sheet</h1>
        <p class="mb-0">Choose year and term first, then class and stream (when they exist).</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.teacher-performance') }}">Teacher Performance</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.subject-performance') }}">Subject Performance</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.student-insights') }}">Student Insights</a>
      </div>
    </div>

    <div class="settings-card mb-3 no-print">
      <div class="card-body">
        <form method="GET" id="classSheetForm" class="row g-3 align-items-end">

          <div class="col-12">
            <label class="form-label d-block mb-2">Report type</label>
            <div class="btn-group flex-wrap" role="group">
              <input type="radio" class="btn-check" name="sheet_flow" id="flow_exam_type" value="by_exam_type" autocomplete="off"
                     @checked(($sheetFlow ?? 'by_exam_type') === 'by_exam_type')>
              <label class="btn btn-outline-primary" for="flow_exam_type">By exam type</label>
              <input type="radio" class="btn-check" name="sheet_flow" id="flow_subject" value="by_subject" autocomplete="off"
                     @checked(($sheetFlow ?? '') === 'by_subject')>
              <label class="btn btn-outline-primary" for="flow_subject">By subject</label>
              <input type="radio" class="btn-check" name="sheet_flow" id="flow_term" value="term" autocomplete="off"
                     @checked(($sheetFlow ?? '') === 'term')>
              <label class="btn btn-outline-primary" for="flow_term">Whole term</label>
            </div>
          </div>

          <div class="col-12">
            <div class="row g-3 align-items-end class-sheet-filter-row">
              <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <label for="academic_year_id_field" class="form-label">Academic year</label>
                <select name="academic_year_id" class="form-select" id="academic_year_id_field">
                  <option value="">Select year</option>
                  @foreach($academicYears ?? [] as $y)
                    <option value="{{ $y->id }}" @selected((int) ($selectedYearId ?? request('academic_year_id')) === (int) $y->id)>{{ $y->year }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <label for="term_id_field" class="form-label">Term</label>
                <select name="term_id" class="form-select" id="term_id_field">
                  <option value="">Select term</option>
                  @foreach($terms ?? [] as $t)
                    <option value="{{ $t->id }}"
                            data-academic-year-id="{{ $t->academic_year_id }}"
                            @selected((int) ($selectedTermId ?? request('term_id')) === (int) $t->id)>
                      {{ $t->academicYear->year ?? '' }} · {{ $t->name }}
                    </option>
                  @endforeach
                </select>
              </div>

              {{-- By exam type --}}
              <div class="col-6 col-md-4 col-lg-3 col-xl-2 flow-exam-type {{ ($sheetFlow ?? 'by_exam_type') === 'by_exam_type' ? '' : 'd-none' }}">
                <label for="exam_type_id_field" class="form-label">Exam type</label>
                <select name="exam_type_id" class="form-select" id="exam_type_id_field">
                  <option value="">Select type</option>
                  @foreach($examTypes ?? [] as $et)
                    <option value="{{ $et->id }}" @selected(request('exam_type_id') == $et->id)>{{ $et->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-6 col-md-4 col-lg-3 col-xl-2 flow-exam-type {{ ($sheetFlow ?? 'by_exam_type') === 'by_exam_type' ? '' : 'd-none' }}">
                <label for="classroom_id_exam_type" class="form-label">Class</label>
                <select name="classroom_id" class="form-select" id="classroom_id_exam_type">
                  <option value="">Select class</option>
                </select>
              </div>

              {{-- By subject --}}
              <div class="col-6 col-md-4 col-lg-3 col-xl-2 flow-subject {{ ($sheetFlow ?? '') === 'by_subject' ? '' : 'd-none' }}">
                <label for="subject_id_field" class="form-label">Subject</label>
                <select name="subject_id" class="form-select" id="subject_id_field">
                  <option value="">Select subject</option>
                  @foreach($subjects ?? [] as $sub)
                    <option value="{{ $sub->id }}" @selected(request('subject_id') == $sub->id)>{{ $sub->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-12 col-lg-6 flow-subject {{ ($sheetFlow ?? '') === 'by_subject' ? '' : 'd-none' }}">
                <label for="classroom_ids_subject" class="form-label">Classes <span class="text-muted small">(Ctrl/Cmd + click for several)</span></label>
                <select name="classroom_ids[]" class="form-select" id="classroom_ids_subject" multiple size="4">
                </select>
              </div>

              {{-- Whole term --}}
              <div class="col-6 col-md-4 col-lg-3 col-xl-2 flow-term {{ ($sheetFlow ?? '') === 'term' ? '' : 'd-none' }}">
                <label for="classroom_id_term" class="form-label">Class</label>
                <select name="classroom_id" class="form-select" id="classroom_id_term">
                  <option value="">Select class</option>
                </select>
              </div>

              <div class="col-6 col-md-4 col-lg-3 col-xl-2 d-none" id="streamFieldWrapper">
                <label for="stream_id_field" class="form-label">Stream</label>
                <select name="stream_id" class="form-select" id="stream_id_field">
                  <option value="">All streams</option>
                </select>
              </div>
            </div>
            <p class="form-text small mb-0 mt-2" id="term_year_hint">Terms update when you pick a year.</p>
            <p class="form-text small mb-0 mt-1 text-warning d-none" id="filter_scope_hint"></p>
          </div>

          <div class="col-12 d-none" aria-hidden="true">
            <div id="hint_exam_type_class" class="d-none"></div>
            <div id="hint_subject_class" class="d-none"></div>
            <div id="hint_term_class" class="d-none"></div>
          </div>

          <div class="col-12 d-flex flex-wrap justify-content-end gap-2 no-print">
            <button type="submit" name="load" value="1" class="btn btn-settings-primary">Load results</button>
            @if(!empty($bundles) && collect($bundles)->contains(fn ($b) => !empty($b['payload'])))
              @php $q = request()->query(); @endphp
              <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
              </button>
              <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.export.class-sheet', $q) }}">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export XLSX
              </a>
              <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.export.class-sheet-pdf', $q) }}">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
              </a>
            @endif
            @if(($sheetFlow ?? '') === 'term' && request('academic_year_id') && request('term_id'))
              <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.export.term-workbook', ['academic_year_id' => request('academic_year_id'), 'term_id' => request('term_id')]) }}">
                @if(!empty($examReportsFullAccess))
                  Term workbook (all classes)
                @else
                  Term workbook (my classes)
                @endif
              </a>
            @endif
          </div>
        </form>
      </div>
    </div>

    @if($classrooms->isEmpty())
      <div class="alert alert-warning border-0 shadow-sm mb-3 no-print" role="alert">
        <i class="bi bi-info-circle me-1"></i>
        No classes are available for your account.
      </div>
    @endif

    <div id="class-sheet-print-area" class="exam-report-print-root">
      @if(!empty($bundles))
        @foreach($bundles as $bundle)
          <div class="settings-card mb-3">
            <div class="card-header d-flex flex-wrap align-items-center gap-2 d-print-none">
              <i class="bi bi-table"></i>
              <h5 class="mb-0">{{ $bundle['classroom']->name ?? 'Class' }}</h5>
              @php
                $metaHdr = $bundle['payload']['meta'] ?? [];
                $streamHdr = $metaHdr['stream_name'] ?? null;
                $hasStreams = ! empty($streamsByClassroom[$bundle['classroom']->id ?? ''] ?? $streamsByClassroom[(string) ($bundle['classroom']->id ?? '')] ?? []);
              @endphp
              @if($streamHdr)
                <span class="mark-sheet-stream-pill">{{ $streamHdr }}</span>
              @elseif($hasStreams)
                <span class="mark-sheet-stream-pill">All streams</span>
              @endif
              @if(!empty($metaHdr))
                <span class="text-muted small">
                  @if(($metaHdr['mode'] ?? '') === 'exam_session')
                    {{ $metaHdr['exam_session']['name'] ?? '' }}
                  @elseif(($metaHdr['mode'] ?? '') === 'subject_paper')
                    {{ $metaHdr['subject']['name'] ?? '' }}
                  @elseif(($metaHdr['mode'] ?? '') === 'term')
                    Term overview
                  @elseif(($metaHdr['mode'] ?? '') === 'exam')
                    {{ $metaHdr['exam']['name'] ?? '' }}
                  @endif
                </span>
              @endif
            </div>
            <div class="card-body p-0">
              @if(!empty($bundle['notice']))
                <div class="p-3">
                  <div class="alert alert-warning mb-0">{{ $bundle['notice'] }}</div>
                </div>
              @elseif(!empty($bundle['payload']))
                @php
                  $m = $bundle['payload']['meta'] ?? [];
                  $lhSub = null;
                  if (! empty($m)) {
                    if (($m['mode'] ?? '') === 'exam_session') {
                        $lhSub = $m['exam_session']['name'] ?? null;
                    } elseif (($m['mode'] ?? '') === 'subject_paper') {
                        $lhSub = $m['subject']['name'] ?? null;
                    } elseif (($m['mode'] ?? '') === 'term') {
                        $lhSub = 'Term overview';
                    } elseif (($m['mode'] ?? '') === 'exam') {
                        $lhSub = $m['exam']['name'] ?? null;
                    }
                  }
                  $streamLh = $m['stream_name'] ?? null;
                  if (! $streamLh && ($hasStreams ?? false)) {
                    $streamLh = 'All streams';
                  }
                  $lhParts = array_filter([
                    $bundle['classroom']->name ?? null,
                    $streamLh,
                    $lhSub,
                  ]);
                  $lhSubtitle = implode(' · ', $lhParts);
                @endphp
                @include('academics.exam_reports.partials.report_letterhead', [
                  'reportTitle' => 'Class Mark Sheet',
                  'reportSubtitle' => $lhSubtitle !== '' ? $lhSubtitle : null,
                  'generatedAt' => now(),
                  'generatedBy' => auth()->user()?->name,
                ])
                @include('academics.exam_reports.partials.class_sheet_table', [
                  'payload' => $bundle['payload'],
                  'showStreamColumn' => ! empty($streamsByClassroom[$bundle['classroom']->id ?? ''] ?? $streamsByClassroom[(string) ($bundle['classroom']->id ?? '')] ?? []),
                ])
              @endif
            </div>
          </div>
        @endforeach
      @endif
    </div>
  </div>
</div>

@php
  $classSheetFilterData = [
    'classrooms' => $classrooms->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
    'classroomExamTypeIds' => $classroomExamTypeIds ?? [],
    'subjectExamScopes' => $subjectExamScopes ?? [],
    'termYearClassScopes' => $termYearClassScopes ?? [],
    'streamsByClassroom' => $streamsByClassroom ?? [],
  ];
@endphp
<script type="application/json" id="class-sheet-filter-data">@json($classSheetFilterData)</script>

@push('scripts')
<script>
(function () {
  const form = document.getElementById('classSheetForm');
  if (!form) return;

  const raw = document.getElementById('class-sheet-filter-data');
  const DATA = raw ? JSON.parse(raw.textContent) : {};
  const CLASSROOMS = DATA.classrooms || [];
  const EXAM_TYPES_BY_CLASS = DATA.classroomExamTypeIds || {};
  const SUBJECT_SCOPES = DATA.subjectExamScopes || [];
  const TERM_SCOPES = DATA.termYearClassScopes || [];
  const STREAMS_BY_CLASS = DATA.streamsByClassroom || {};

  const yearSel = document.getElementById('academic_year_id_field');
  const termSel = document.getElementById('term_id_field');
  const streamWrap = document.getElementById('streamFieldWrapper');
  const streamSel = document.getElementById('stream_id_field');
  const filterScopeHint = document.getElementById('filter_scope_hint');

  function setFilterScopeHint(message) {
    if (!filterScopeHint) return;
    if (!message) {
      filterScopeHint.textContent = '';
      filterScopeHint.classList.add('d-none');
      return;
    }
    filterScopeHint.textContent = message;
    filterScopeHint.classList.remove('d-none');
  }

  function filterTermsByYear() {
    const y = yearSel && yearSel.value;
    const currentTerm = termSel.value;
    let valid = false;
    termSel.querySelectorAll('option').forEach(function (opt) {
      if (!opt.value) {
        opt.hidden = false;
        return;
      }
      const oy = String(opt.getAttribute('data-academic-year-id') || '');
      const show = y && oy === String(y);
      opt.hidden = !show;
      if (show && opt.value === currentTerm) valid = true;
    });
    if (!y || !valid) {
      termSel.value = '';
    }
  }

  function fillSelectOptions(selectEl, ids, preserveValue) {
    const allowed = new Set(ids.map(String));
    const prev = preserveValue != null && preserveValue !== '' ? String(preserveValue) : '';
    selectEl.innerHTML = '';
    const ph = document.createElement('option');
    ph.value = '';
    ph.textContent = selectEl.id === 'classroom_ids_subject' ? 'Select class(es)' : 'Select class';
    selectEl.appendChild(ph);
    CLASSROOMS.forEach(function (c) {
      if (!allowed.has(String(c.id))) return;
      const o = document.createElement('option');
      o.value = c.id;
      o.textContent = c.name;
      selectEl.appendChild(o);
    });
    if (!selectEl.multiple && prev && allowed.has(prev)) {
      selectEl.value = prev;
    }
  }

  function examTypeClassIds(examTypeId) {
    if (!examTypeId) return [];
    const et = parseInt(examTypeId, 10);
    const out = [];
    Object.keys(EXAM_TYPES_BY_CLASS).forEach(function (cid) {
      const list = EXAM_TYPES_BY_CLASS[cid] || [];
      if (list.map(Number).includes(et)) out.push(parseInt(cid, 10));
    });
    return out;
  }

  function syncExamTypeClass() {
    const sel = document.getElementById('classroom_id_exam_type');
    const et = document.getElementById('exam_type_id_field')?.value;
    if (!sel) return;
    if (!et) {
      sel.innerHTML = '<option value="">Select class</option>';
      sel.disabled = true;
      setFilterScopeHint('');
      syncStreams();
      return;
    }
    const ids = examTypeClassIds(et);
    const reqClass = @json(request()->filled('classroom_id') ? (int) request('classroom_id') : null);
    fillSelectOptions(sel, ids, reqClass != null ? reqClass : sel.value);
    sel.disabled = ids.length === 0;
    setFilterScopeHint(ids.length === 0 ? 'No classes have exams for this type yet.' : '');
    syncStreams();
  }

  function subjectClassIds(subjectId, yearId, termId) {
    if (!subjectId || !yearId || !termId) return [];
    const s = parseInt(subjectId, 10), y = parseInt(yearId, 10), t = parseInt(termId, 10);
    const out = [];
    SUBJECT_SCOPES.forEach(function (row) {
      if (row.subject_id === s && row.academic_year_id === y && row.term_id === t) {
        out.push(row.classroom_id);
      }
    });
    return [...new Set(out)];
  }

  function syncSubjectClasses() {
    const sel = document.getElementById('classroom_ids_subject');
    if (!sel) return;
    const sub = document.getElementById('subject_id_field')?.value;
    const y = yearSel?.value;
    const t = termSel?.value;
    if (!sub || !y || !t) {
      sel.innerHTML = '';
      sel.disabled = true;
      setFilterScopeHint('Pick subject, year, and term to list classes with that paper.');
      syncStreams();
      return;
    }
    const ids = subjectClassIds(sub, y, t);
    const prev = @json(array_values(array_map('intval', (array) request('classroom_ids', []))));
    sel.innerHTML = '';
    sel.disabled = ids.length === 0;
    if (ids.length === 0) {
      setFilterScopeHint('No classes have this subject paper for the selected year and term.');
      syncStreams();
      return;
    }
    setFilterScopeHint('');
    const prevSet = new Set(prev.map(String));
    CLASSROOMS.forEach(function (c) {
      if (!ids.includes(c.id)) return;
      const o = document.createElement('option');
      o.value = c.id;
      o.textContent = c.name;
      o.selected = prevSet.has(String(c.id));
      sel.appendChild(o);
    });
    syncStreams();
  }

  function termFlowClassIds(yearId, termId) {
    if (!yearId || !termId) return [];
    const y = parseInt(yearId, 10), t = parseInt(termId, 10);
    const out = [];
    TERM_SCOPES.forEach(function (row) {
      if (row.academic_year_id === y && row.term_id === t) out.push(row.classroom_id);
    });
    return [...new Set(out)];
  }

  function syncTermClass() {
    const sel = document.getElementById('classroom_id_term');
    if (!sel) return;
    const y = yearSel?.value;
    const t = termSel?.value;
    if (!y || !t) {
      sel.innerHTML = '<option value="">Select class</option>';
      sel.disabled = true;
      setFilterScopeHint('');
      syncStreams();
      return;
    }
    const ids = termFlowClassIds(y, t);
    const reqClass = @json(request()->filled('classroom_id') ? (int) request('classroom_id') : null);
    fillSelectOptions(sel, ids, reqClass != null ? reqClass : sel.value);
    sel.disabled = ids.length === 0;
    setFilterScopeHint(ids.length === 0 ? 'No classes have exams in this year/term.' : '');
    syncStreams();
  }

  function primaryClassIdForStream() {
    const flow = form.querySelector('input[name="sheet_flow"]:checked')?.value || 'by_exam_type';
    if (flow === 'by_exam_type') {
      const v = document.getElementById('classroom_id_exam_type')?.value;
      return v ? parseInt(v, 10) : null;
    }
    if (flow === 'by_subject') {
      const m = document.getElementById('classroom_ids_subject');
      if (!m || !m.selectedOptions.length) return null;
      return parseInt(m.selectedOptions[0].value, 10);
    }
    if (flow === 'term') {
      const v = document.getElementById('classroom_id_term')?.value;
      return v ? parseInt(v, 10) : null;
    }
    return null;
  }

  function syncStreams() {
    if (!streamWrap || !streamSel) return;
    const cid = primaryClassIdForStream();
    const list = cid ? (STREAMS_BY_CLASS[String(cid)] || []) : [];
    const reqStream = @json(request()->filled('stream_id') ? (int) request('stream_id') : null);
    streamSel.innerHTML = '<option value="">All streams</option>';
    if (!list.length) {
      streamWrap.classList.add('d-none');
      streamSel.value = '';
      streamSel.disabled = true;
      return;
    }
    streamWrap.classList.remove('d-none');
    streamSel.disabled = false;
    list.forEach(function (st) {
      const o = document.createElement('option');
      o.value = st.id;
      o.textContent = st.name;
      if (reqStream != null && Number(st.id) === Number(reqStream)) o.selected = true;
      streamSel.appendChild(o);
    });
  }

  function syncFlowVisibility() {
    const flow = form.querySelector('input[name="sheet_flow"]:checked')?.value || 'by_exam_type';
    document.querySelectorAll('.flow-exam-type').forEach(function (el) { el.classList.toggle('d-none', flow !== 'by_exam_type'); });
    document.querySelectorAll('.flow-subject').forEach(function (el) { el.classList.toggle('d-none', flow !== 'by_subject'); });
    document.querySelectorAll('.flow-term').forEach(function (el) { el.classList.toggle('d-none', flow !== 'term'); });

    const idExam = document.getElementById('classroom_id_exam_type');
    const idTerm = document.getElementById('classroom_id_term');
    if (idExam) idExam.disabled = flow !== 'by_exam_type';
    if (idTerm) idTerm.disabled = flow !== 'term';

    const examTypeId = document.getElementById('exam_type_id_field');
    if (examTypeId) examTypeId.disabled = flow !== 'by_exam_type';
    const subj = document.getElementById('subject_id_field');
    if (subj) subj.disabled = flow !== 'by_subject';
    const subjCls = document.getElementById('classroom_ids_subject');
    if (subjCls) subjCls.disabled = flow !== 'by_subject';

    if (flow === 'by_exam_type') syncExamTypeClass();
    if (flow === 'by_subject') syncSubjectClasses();
    if (flow === 'term') syncTermClass();
    syncStreams();
  }

  yearSel && yearSel.addEventListener('change', function () {
    filterTermsByYear();
    syncSubjectClasses();
    syncTermClass();
    syncExamTypeClass();
    syncStreams();
  });
  termSel && termSel.addEventListener('change', function () {
    syncSubjectClasses();
    syncTermClass();
    syncStreams();
  });
  document.getElementById('exam_type_id_field')?.addEventListener('change', syncExamTypeClass);
  document.getElementById('subject_id_field')?.addEventListener('change', syncSubjectClasses);
  document.getElementById('classroom_id_exam_type')?.addEventListener('change', syncStreams);
  document.getElementById('classroom_ids_subject')?.addEventListener('change', syncStreams);
  document.getElementById('classroom_id_term')?.addEventListener('change', syncStreams);

  form.querySelectorAll('input[name="sheet_flow"]').forEach(function (r) {
    r.addEventListener('change', syncFlowVisibility);
  });

  filterTermsByYear();
  syncFlowVisibility();
})();
</script>
@endpush
@endsection

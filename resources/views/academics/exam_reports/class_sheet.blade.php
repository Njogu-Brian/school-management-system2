@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <style>
      @media print {
        .no-print { display: none !important; }
        .settings-page .settings-shell { max-width: 100% !important; padding: 0 !important; }
        .settings-card { box-shadow: none !important; border: none !important; }
        a[href]:after { content: none !important; }
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
        <p class="text-muted mb-0">Load by exam type (full sitting) or by subject (one paper). Then print or export.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.teacher-performance') }}">Teacher Performance</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.subject-performance') }}">Subject Performance</a>
        <a class="btn btn-outline-secondary" href="{{ route('academics.exam-reports.student-insights') }}">Student Insights</a>
      </div>
    </div>

    <div class="settings-card mb-3 no-print">
      <div class="card-body">
        <form method="GET" id="classSheetForm" class="row g-3">

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

          {{-- By exam type --}}
          <div class="col-md-3 flow-exam-type {{ ($sheetFlow ?? 'by_exam_type') === 'by_exam_type' ? '' : 'd-none' }}">
            <label class="form-label">Exam type</label>
            <select name="exam_type_id" class="form-select">
              <option value="">Select type</option>
              @foreach($examTypes ?? [] as $et)
                <option value="{{ $et->id }}" @selected(request('exam_type_id') == $et->id)>{{ $et->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 flow-exam-type {{ ($sheetFlow ?? 'by_exam_type') === 'by_exam_type' ? '' : 'd-none' }}">
            <label class="form-label">Class</label>
            <select name="classroom_id" class="form-select" id="classroom_id_exam_type">
              <option value="">Select class</option>
              @foreach($classrooms ?? [] as $c)
                <option value="{{ $c->id }}" @selected(request('classroom_id') == $c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
          </div>

          {{-- By subject: multi class --}}
          <div class="col-md-4 flow-subject {{ ($sheetFlow ?? '') === 'by_subject' ? '' : 'd-none' }}">
            <label class="form-label">Subject</label>
            <select name="subject_id" class="form-select">
              <option value="">Select subject</option>
              @foreach($subjects ?? [] as $sub)
                <option value="{{ $sub->id }}" @selected(request('subject_id') == $sub->id)>{{ $sub->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-5 flow-subject {{ ($sheetFlow ?? '') === 'by_subject' ? '' : 'd-none' }}">
            <label class="form-label">Classes <span class="text-muted small">(Ctrl/Cmd + click for several)</span></label>
            <select name="classroom_ids[]" class="form-select" multiple size="6">
              @foreach($classrooms ?? [] as $c)
                <option value="{{ $c->id }}" @selected(collect((array)request('classroom_ids', []))->contains($c->id))>{{ $c->name }}</option>
              @endforeach
            </select>
          </div>

          {{-- Whole term: single class --}}
          <div class="col-md-3 flow-term {{ ($sheetFlow ?? '') === 'term' ? '' : 'd-none' }}">
            <label class="form-label">Class</label>
            <select name="classroom_id" class="form-select" id="classroom_id_term">
              <option value="">Select class</option>
              @foreach($classrooms ?? [] as $c)
                <option value="{{ $c->id }}" @selected(request('classroom_id') == $c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label">Stream <span class="text-muted">(optional)</span></label>
            <select name="stream_id" class="form-select">
              <option value="">All streams</option>
              @foreach($streamsForClass ?? [] as $st)
                <option value="{{ $st->id }}" @selected(request('stream_id') == $st->id)>{{ $st->name }}</option>
              @endforeach
            </select>
            @if(($streamsForClass ?? collect())->isEmpty())
              <div class="form-text">Choose class and reload to list streams for that class.</div>
            @endif
          </div>

          <div class="col-md-2">
            <label class="form-label">Academic year</label>
            <select name="academic_year_id" class="form-select" id="academic_year_id_field">
              <option value="">Select year</option>
              @foreach($academicYears ?? [] as $y)
                <option value="{{ $y->id }}" @selected(request('academic_year_id') == $y->id)>{{ $y->year }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Term</label>
            <select name="term_id" class="form-select">
              <option value="">Select term</option>
              @foreach($termsForYear ?? [] as $t)
                <option value="{{ $t->id }}" @selected(request('term_id') == $t->id)>
                  {{ $t->academicYear->year ?? '' }} · {{ $t->name }}
                </option>
              @endforeach
            </select>
            @if(($termsForYear ?? collect())->isEmpty() && !request('academic_year_id'))
              <div class="form-text">Pick a year to narrow terms.</div>
            @endif
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

    <div id="class-sheet-print-area">
      @if(!empty($bundles))
        @foreach($bundles as $bundle)
          <div class="settings-card mb-3">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
              <i class="bi bi-table"></i>
              <h5 class="mb-0">{{ $bundle['classroom']->name ?? 'Class' }}</h5>
              @if(!empty($bundle['payload']['meta']))
                @php $meta = $bundle['payload']['meta']; @endphp
                <span class="text-muted small">
                  @if(($meta['mode'] ?? '') === 'exam_session')
                    {{ $meta['exam_session']['name'] ?? '' }}
                  @elseif(($meta['mode'] ?? '') === 'subject_paper')
                    {{ $meta['subject']['name'] ?? '' }}
                  @elseif(($meta['mode'] ?? '') === 'term')
                    Term overview
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
                @include('academics.exam_reports.partials.class_sheet_table', ['payload' => $bundle['payload']])
              @endif
            </div>
          </div>
        @endforeach
      @endif
    </div>
  </div>
</div>

@push('scripts')
<script>
(function () {
  const form = document.getElementById('classSheetForm');
  if (!form) return;

  function syncFlowVisibility() {
    const flow = form.querySelector('input[name="sheet_flow"]:checked')?.value || 'by_exam_type';
    document.querySelectorAll('.flow-exam-type').forEach(el => el.classList.toggle('d-none', flow !== 'by_exam_type'));
    document.querySelectorAll('.flow-subject').forEach(el => el.classList.toggle('d-none', flow !== 'by_subject'));
    document.querySelectorAll('.flow-term').forEach(el => el.classList.toggle('d-none', flow !== 'term'));
    const idExam = document.getElementById('classroom_id_exam_type');
    const idTerm = document.getElementById('classroom_id_term');
    if (idExam) { idExam.disabled = flow !== 'by_exam_type'; }
    if (idTerm) { idTerm.disabled = flow !== 'term'; }
    const examTypeId = form.querySelector('select[name="exam_type_id"]');
    if (examTypeId) examTypeId.disabled = flow !== 'by_exam_type';
    const subj = form.querySelector('select[name="subject_id"]');
    if (subj) subj.disabled = flow !== 'by_subject';
    const subjCls = form.querySelector('select[name="classroom_ids[]"]');
    if (subjCls) subjCls.disabled = flow !== 'by_subject';
  }

  form.querySelectorAll('input[name="sheet_flow"]').forEach(r => r.addEventListener('change', syncFlowVisibility));
  syncFlowVisibility();
})();
</script>
@endpush
@endsection

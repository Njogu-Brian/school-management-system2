@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Exams</div>
        <h1 class="mb-1">Enter Marks — Smart Context</h1>
        <p class="text-muted mb-0">Choose exam type, class, and optional stream to load learners with all active exams in one table.</p>
      </div>
      <a href="{{ route('academics.exam-marks.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @includeIf('partials.alerts')

    <div class="settings-card">
      <div class="card-body">
        <form method="post" action="{{ route('academics.exam-marks.matrix.edit') }}" class="row g-3" id="matrixSelectorForm">
          @csrf
          <div class="col-md-4">
            <label class="form-label">Exam Type</label>
            <select name="exam_type_id" class="form-select" required>
              <option value="">Select exam type</option>
              @foreach($types as $t)
                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->code }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Classroom</label>
            <select name="classroom_id" class="form-select" id="matrixClassroomSelect" required>
              <option value="">Select class</option>
              @foreach($classrooms as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Stream (optional)</label>
            <select name="stream_id" class="form-select" id="matrixStreamSelect">
              <option value="">All streams / class-level exams</option>
              @foreach($streams as $st)
                <option value="{{ $st->id }}" data-classroom-id="{{ $st->classroom_id }}">{{ $st->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 text-end">
            <button class="btn btn-settings-primary"><i class="bi bi-table me-1"></i> Load Mark Entry Table</button>
          </div>
        </div>
      </div>
    </div>

    <div class="settings-card mt-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h6 class="mb-1">Legacy single-exam mode</h6>
            <div class="small text-muted">Use only if you want to enter marks exam by exam.</div>
          </div>
          <button class="btn btn-ghost-strong btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#legacySingleExamForm">
            Show legacy form
          </button>
        </div>
        <div class="collapse mt-3" id="legacySingleExamForm">
          <form method="post" action="{{ route('academics.exam-marks.bulk.edit') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
              <label class="form-label">Exam</label>
              <select name="exam_id" class="form-select" required>
                <option value="">Select exam</option>
                @foreach($exams as $e)
                  <option value="{{ $e->id }}">{{ $e->name }} ({{ $e->term?->name }}/{{ $e->academicYear?->year }})</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Classroom</label>
              <select name="classroom_id" class="form-select" required>
                <option value="">Select class</option>
                @foreach($classrooms as $c)
                  <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Subject</label>
              <select name="subject_id" class="form-select" required>
                <option value="">Select subject</option>
                @foreach($subjects as $s)
                  <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12 text-end">
              <button class="btn btn-settings-primary"><i class="bi bi-arrow-right-circle me-1"></i> Continue</button>
            </div>
          </form>
        </form>
      </div>
    </div>

    <div class="small text-muted mt-2">Tip: Teachers see only subjects they are assigned in selected classes; senior teachers see all subjects in supervised classes plus assigned subjects outside supervision.</div>
  </div>
</div>

@push('scripts')
<script>
(() => {
  const classSel = document.getElementById('matrixClassroomSelect');
  const streamSel = document.getElementById('matrixStreamSelect');
  if (!classSel || !streamSel) return;

  const opts = Array.from(streamSel.options);
  const syncStreams = () => {
    const cid = classSel.value;
    opts.forEach((opt, idx) => {
      if (idx === 0) {
        opt.hidden = false;
        return;
      }
      const owner = opt.getAttribute('data-classroom-id');
      const show = !cid || owner === cid;
      opt.hidden = !show;
      if (!show && opt.selected) {
        streamSel.value = '';
      }
    });
  };

  classSel.addEventListener('change', syncStreams);
  syncStreams();
})();
</script>
@endpush
@endsection

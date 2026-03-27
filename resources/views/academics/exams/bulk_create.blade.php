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
        <h1 class="mb-1">Bulk create exams</h1>
        <p class="text-muted mb-0">Create one exam per class and subject with the same settings (saved as draft).</p>
      </div>
      <a href="{{ route('academics.exams.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @includeIf('partials.alerts')

    <div class="alert alert-soft alert-info border-0">
      <strong>Subjects.</strong> Either pick specific subjects (every selected class gets each subject), or enable
      “Use each class’s assigned subjects” to use <code>classroom_subjects</code> only.
    </div>

    <form method="post" action="{{ route('academics.exams.bulk-store') }}" class="settings-card">
      @csrf
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-12">
            <label class="form-label">Base name <span class="text-danger">*</span></label>
            <input name="name_template" class="form-control" required value="{{ old('name_template') }}"
              placeholder="e.g. Term 1 CAT 1">
            <div class="form-text">Each exam is named: <em>{{ '{base}' }} — Class — Subject</em>.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Modality</label>
            <select name="modality" class="form-select" required>
              @foreach(['physical','online'] as $m)
                <option value="{{ $m }}" @selected(old('modality','physical')==$m)>{{ ucfirst($m) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Weight</label>
            <input type="number" step="0.01" min="0" name="weight" class="form-control" value="{{ old('weight', 1) }}" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Exam type <span class="text-danger">*</span></label>
            <select name="exam_type_id" class="form-select" required>
              @foreach($types as $t)
                <option value="{{ $t->id }}" @selected(old('exam_type_id')==$t->id)>{{ $t->name }}</option>
              @endforeach
            </select>
            <div class="form-text">Max and min marks are inherited from this exam type.</div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Academic year</label>
            <select name="academic_year_id" class="form-select" required>
              @foreach($years as $y)
                <option value="{{ $y->id }}" @selected(old('academic_year_id')==$y->id)>{{ $y->year }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Term</label>
            <select name="term_id" class="form-select" required>
              @foreach($terms as $t)
                <option value="{{ $t->id }}" @selected(old('term_id')==$t->id)>{{ $t->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Stream (optional)</label>
            <select name="stream_id" class="form-select">
              <option value="">— All / not set —</option>
              @foreach($streams as $s)
                <option value="{{ $s->id }}" @selected(old('stream_id')==$s->id)>{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Starts on</label>
            <input type="date" name="starts_on" class="form-control" value="{{ old('starts_on') }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Ends on</label>
            <input type="date" name="ends_on" class="form-control" value="{{ old('ends_on') }}">
          </div>
          <div class="col-md-12">
            <label class="form-label">Classes <span class="text-danger">*</span></label>
            <select name="classroom_ids[]" class="form-select" multiple required size="8">
              @foreach($classrooms as $c)
                <option value="{{ $c->id }}" @selected(collect(old('classroom_ids', []))->contains($c->id))>{{ $c->name }}</option>
              @endforeach
            </select>
            <div class="form-text">Hold Ctrl (Windows) or Cmd (Mac) to select multiple.</div>
          </div>
          <div class="col-md-12">
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" name="use_all_subjects" value="1" id="useAllSubjects"
                @checked(old('use_all_subjects'))>
              <label class="form-check-label" for="useAllSubjects">Use each class’s assigned subjects</label>
            </div>
          </div>
          <div class="col-md-12" id="subjectPickWrap">
            <label class="form-label">Subjects</label>
            <select name="subject_ids[]" class="form-select" multiple size="8" id="subjectSelect">
              @foreach($subjects as $s)
                <option value="{{ $s->id }}" @selected(collect(old('subject_ids', []))->contains($s->id))>{{ $s->name }}</option>
              @endforeach
            </select>
            <div class="form-text">Only active subjects with mapped teachers are used. Unmapped/inactive selections are skipped.</div>
          </div>
          <div class="col-md-12">
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" name="publish_exam" value="1" id="publishExamBulk" @checked(old('publish_exam'))>
              <label class="form-check-label" for="publishExamBulk">Publish exam (visible on schedule)</label>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="publish_result" value="1" id="publishResultBulk" @checked(old('publish_result', true))>
              <label class="form-check-label" for="publishResultBulk">Publish result (eligible for report cards)</label>
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('academics.exams.index') }}" class="btn btn-ghost-strong">Cancel</a>
        <button type="submit" class="btn btn-settings-primary"><i class="bi bi-layers me-1"></i>Create exams</button>
      </div>
    </form>
  </div>
</div>
@push('scripts')
<script>
(function () {
  const box = document.getElementById('useAllSubjects');
  const wrap = document.getElementById('subjectPickWrap');
  const sel = document.getElementById('subjectSelect');
  if (!box || !wrap || !sel) return;
  function sync() {
    const on = box.checked;
    wrap.classList.toggle('opacity-50', on);
    sel.disabled = on;
  }
  box.addEventListener('change', sync);
  sync();
})();
</script>
@endpush
@endsection

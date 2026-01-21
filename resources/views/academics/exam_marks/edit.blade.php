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
        <h1 class="mb-1">Edit Mark</h1>
        <p class="text-muted mb-0">{{ $exam_mark->subject?->name }} • {{ $exam_mark->exam?->name }}</p>
      </div>
      <a href="{{ route('academics.exam-marks.index', ['exam_id' => $exam_mark->exam_id]) }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @includeIf('partials.alerts')

    <div class="settings-card">
      <div class="card-body">
        <div class="mb-3">
          <div class="fw-semibold">{{ $exam_mark->student?->full_name }}</div>
          <div class="text-muted small">{{ $exam_mark->subject?->name }} • {{ $exam_mark->exam?->name }}</div>
        </div>

        <form method="post" action="{{ route('academics.exam-marks.update', $exam_mark->id) }}">
          @csrf @method('PUT')
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Opener Score</label>
              <input type="number" step="0.01" min="0" max="100" name="opener_score" class="form-control" value="{{ old('opener_score', $exam_mark->opener_score) }}">
            </div>
            <div class="col-md-3">
              <label class="form-label">Midterm Score</label>
              <input type="number" step="0.01" min="0" max="100" name="midterm_score" class="form-control" value="{{ old('midterm_score', $exam_mark->midterm_score) }}">
            </div>
            <div class="col-md-3">
              <label class="form-label">Endterm Score</label>
              <input type="number" step="0.01" min="0" max="100" name="endterm_score" class="form-control" value="{{ old('endterm_score', $exam_mark->endterm_score) }}">
            </div>
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <input class="form-control" value="{{ ucfirst($exam_mark->status ?? 'draft') }}" disabled>
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">Subject Remark</label>
              <input type="text" name="subject_remark" class="form-control" maxlength="500" value="{{ old('subject_remark', $exam_mark->subject_remark) }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">General Remark</label>
              <input type="text" name="remark" class="form-control" maxlength="500" value="{{ old('remark', $exam_mark->remark) }}">
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('academics.exam-marks.index', ['exam_id' => $exam_mark->exam_id]) }}" class="btn btn-ghost-strong">Cancel</a>
            <button class="btn btn-settings-primary"><i class="bi bi-save2 me-1"></i>Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

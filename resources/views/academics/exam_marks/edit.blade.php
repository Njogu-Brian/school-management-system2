@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">Edit Mark</h3>
    <a href="{{ route('academics.exam-marks.index', ['exam_id' => $exam_mark->exam_id]) }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  @includeIf('partials.alerts')

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="mb-3">
        <div class="fw-semibold">
          {{ $exam_mark->student?->full_name ?? ($exam_mark->student?->first_name.' '.$exam_mark->student?->last_name) }}
        </div>
        <div class="text-muted small">
          {{ $exam_mark->subject?->name }} • {{ $exam_mark->exam?->name }}
        </div>
      </div>

      <form method="post" action="{{ route('academics.exam-marks.update', $exam_mark->id) }}">
        @csrf @method('PUT')

        <div class="row">
          <div class="col-md-3 mb-3">
            <label class="form-label">Opener Score</label>
            <input type="number" step="0.01" min="0" max="100" name="opener_score"
                   class="form-control" value="{{ old('opener_score', $exam_mark->opener_score) }}">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Midterm Score</label>
            <input type="number" step="0.01" min="0" max="100" name="midterm_score"
                   class="form-control" value="{{ old('midterm_score', $exam_mark->midterm_score) }}">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Endterm Score</label>
            <input type="number" step="0.01" min="0" max="100" name="endterm_score"
                   class="form-control" value="{{ old('endterm_score', $exam_mark->endterm_score) }}">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Status</label>
            <input class="form-control" value="{{ ucfirst($exam_mark->status ?? 'draft') }}" disabled>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Subject Remark</label>
          <input type="text" name="subject_remark" class="form-control" maxlength="500"
                 value="{{ old('subject_remark', $exam_mark->subject_remark) }}">
        </div>

        <div class="mb-3">
          <label class="form-label">General Remark</label>
          <input type="text" name="remark" class="form-control" maxlength="500"
                 value="{{ old('remark', $exam_mark->remark) }}">
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('academics.exam-marks.index', ['exam_id' => $exam_mark->exam_id]) }}" class="btn btn-outline-secondary">Cancel</a>
          <button class="btn btn-primary"><i class="bi bi-save2 me-1"></i>Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

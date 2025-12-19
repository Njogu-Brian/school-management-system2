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
        <h1 class="mb-1">Enter Marks — Select Context</h1>
        <p class="text-muted mb-0">Choose exam, class, and subject to load students.</p>
      </div>
      <a href="{{ route('academics.exam-marks.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @includeIf('partials.alerts')

    <div class="settings-card">
      <div class="card-body">
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
      </div>
    </div>

    <div class="small text-muted mt-2">Tip: Authorization ensures teachers can only enter marks for classes/subjects they teach.</div>
  </div>
</div>
@endsection

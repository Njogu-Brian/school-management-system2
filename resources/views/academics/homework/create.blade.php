@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics</div>
        <h1 class="mb-1">Assign Homework</h1>
        <p class="text-muted mb-0">Create homework, set due date, and target recipients.</p>
      </div>
      <a href="{{ route('academics.homework.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <form method="POST" action="{{ route('academics.homework.store') }}" enctype="multipart/form-data" class="settings-card">
      @csrf
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Due Date <span class="text-danger">*</span></label>
            <input type="date" name="due_date" class="form-control" required>
          </div>
        </div>

        <div class="mt-3">
          <label class="form-label">Instructions</label>
          <textarea name="instructions" class="form-control" rows="3"></textarea>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-md-4">
            <label class="form-label">Target Scope</label>
            <select name="target_scope" id="target_scope" class="form-select" required>
              <option value="class">Class</option>
              <option value="stream">Stream</option>
              <option value="students">Specific Students</option>
            </select>
          </div>
          <div class="col-md-4 scope-field scope-class">
            <label class="form-label">Classroom</label>
            <select name="classroom_id" class="form-select">
              <option value="">-- Select Class --</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4 scope-field scope-stream d-none">
            <label class="form-label">Stream</label>
            <select name="stream_id" class="form-select">
              <option value="">-- Select Stream --</option>
              @foreach($classrooms as $classroom)
                @foreach($classroom->streams as $stream)
                  <option value="{{ $stream->id }}">{{ $classroom->name }} - {{ $stream->name }}</option>
                @endforeach
              @endforeach
            </select>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label class="form-label">Subject</label>
            <select name="subject_id" class="form-select">
              <option value="">-- Select Subject --</option>
              @foreach($subjects as $subject)
                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6 scope-field scope-students d-none">
            <label class="form-label">Students</label>
            <select name="student_ids[]" class="form-select" multiple>
              @foreach($students as $student)
                <option value="{{ $student->id }}">{{ $student->admission_no }} - {{ $student->first_name }} {{ $student->last_name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Hold Ctrl/Cmd to select multiple students</small>
          </div>
        </div>

        <div class="mt-3">
          <label class="form-label">Attachment (optional)</label>
          <input type="file" name="attachment" class="form-control">
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('academics.homework.index') }}" class="btn btn-ghost-strong">Cancel</a>
        <button class="btn btn-settings-primary">Assign</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const scopeSelect = document.getElementById('target_scope');
    const scopeFields = document.querySelectorAll('.scope-field');
    function toggleScopeFields() {
      scopeFields.forEach(f => f.classList.add('d-none'));
      const selected = scopeSelect.value;
      document.querySelectorAll('.scope-' + selected).forEach(f => f.classList.remove('d-none'));
    }
    scopeSelect.addEventListener('change', toggleScopeFields);
    toggleScopeFields();
  });
</script>
@endpush
@endsection

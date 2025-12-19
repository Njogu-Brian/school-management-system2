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
        <h1 class="mb-1">Add New Classroom</h1>
        <p class="text-muted mb-0">Create a class, set promotion mapping, and assign teachers.</p>
      </div>
      <a href="{{ route('academics.classrooms.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <form action="{{ route('academics.classrooms.store') }}" method="POST" class="settings-card">
      @csrf
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Class Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Next Class (for promotion)</label>
            <select name="next_class_id" class="form-select">
              <option value="">-- Select Next Class --</option>
              <option value="">Alumni (Last Class)</option>
              @foreach ($classrooms as $class)
                <option value="{{ $class->id }}" 
                  @if(in_array($class->id, $usedAsNextClass ?? [])) disabled style="color: #999; background-color: #f5f5f5;" @endif>
                  {{ $class->name }}
                  @if(in_array($class->id, $usedAsNextClass ?? [])) (Already mapped by another class) @endif
                </option>
              @endforeach
            </select>
            <small class="text-muted">Select the class students will be promoted to. Leave empty or choose Alumni for the last class.</small>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <div class="form-check">
              <input type="checkbox" name="is_beginner" value="1" class="form-check-input" id="is_beginner">
              <label class="form-check-label" for="is_beginner">Beginner Class (First Class)</label>
              <small class="text-muted d-block">Entry class for new students</small>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-check">
              <input type="checkbox" name="is_alumni" value="1" class="form-check-input" id="is_alumni">
              <label class="form-check-label" for="is_alumni">Alumni Class (Last Class)</label>
              <small class="text-muted d-block">Final class; students become alumni after this</small>
            </div>
          </div>
        </div>

        <div class="mt-3">
          <label class="form-label">Assign Teachers</label>
          <select name="teacher_ids[]" class="form-select" multiple>
            @foreach ($teachers as $teacher)
              <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
            @endforeach
          </select>
          <small class="text-muted">Hold Ctrl/Cmd to select multiple teachers</small>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('academics.classrooms.index') }}" class="btn btn-ghost-strong">Cancel</a>
        <button type="submit" class="btn btn-settings-primary">Add Classroom</button>
      </div>
    </form>
  </div>
</div>
@endsection

@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Campus & Weekly Reports</div>
        <h1 class="mb-1">New Student Follow-Up</h1>
        <p class="text-muted mb-0">Weekly student concerns.</p>
      </div>
      <a href="{{ route('reports.student-followups.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <div class="settings-card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-person-plus"></i> Follow-Up Details</h5>
        <p class="text-muted small mb-0">Week ending, student, concerns and action taken.</p>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('reports.student-followups.store') }}">
          @csrf
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label fw-semibold">Week Ending</label>
              <input type="date" name="week_ending" class="form-control" required value="{{ old('week_ending') }}">
              @error('week_ending')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Campus</label>
              <select name="campus" class="form-select">
                <option value="">Select campus</option>
                <option value="lower" {{ old('campus') == 'lower' ? 'selected' : '' }}>Lower</option>
                <option value="upper" {{ old('campus') == 'upper' ? 'selected' : '' }}>Upper</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Student</label>
              <select name="student_id" class="form-select" required>
                <option value="">Select student</option>
                @foreach($students as $student)
                  <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>{{ $student->full_name ?? $student->name }}</option>
                @endforeach
              </select>
              @error('student_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Class</label>
              <select name="classroom_id" class="form-select" required>
                <option value="">Select class</option>
                @foreach($classrooms as $classroom)
                  <option value="{{ $classroom->id }}" {{ old('classroom_id') == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
                @endforeach
              </select>
              @error('classroom_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">Academic Concern</label>
              <select name="academic_concern" class="form-select">
                <option value="">Select</option>
                <option value="1" {{ old('academic_concern') === '1' ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ old('academic_concern') === '0' ? 'selected' : '' }}>No</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Behavior Concern</label>
              <select name="behavior_concern" class="form-select">
                <option value="">Select</option>
                <option value="1" {{ old('behavior_concern') === '1' ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ old('behavior_concern') === '0' ? 'selected' : '' }}>No</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Parent Contacted</label>
              <select name="parent_contacted" class="form-select">
                <option value="">Select</option>
                <option value="1" {{ old('parent_contacted') === '1' ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ old('parent_contacted') === '0' ? 'selected' : '' }}>No</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Action Taken</label>
              <input type="text" name="action_taken" class="form-control" value="{{ old('action_taken') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Progress Status</label>
              <select name="progress_status" class="form-select">
                <option value="">Select</option>
                @foreach(['Improving','Same','Worse'] as $opt)
                  <option value="{{ $opt }}" {{ old('progress_status') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Notes</label>
              <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-settings-primary">
              <i class="bi bi-check-circle"></i> Save Follow-Up
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

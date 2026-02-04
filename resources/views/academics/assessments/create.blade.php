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
        <h1 class="mb-1">New Assessment</h1>
        <p class="text-muted mb-0">Capture assessment results by class and subject.</p>
      </div>
      <a href="{{ route('academics.assessments.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <div class="settings-card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-clipboard-plus"></i> Assessment Details</h5>
        <p class="text-muted small mb-0">Assessment date, class, subject, student and score.</p>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('academics.assessments.store') }}">
          @csrf
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label fw-semibold">Assessment Date</label>
              <input type="date" name="assessment_date" class="form-control" value="{{ old('assessment_date') }}">
              @error('assessment_date')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Week Ending</label>
              <input type="date" name="week_ending" class="form-control" value="{{ old('week_ending') }}">
              @error('week_ending')<div class="text-danger small">{{ $message }}</div>@enderror
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

            <div class="col-md-6">
              <label class="form-label fw-semibold">Subject</label>
              <select name="subject_id" class="form-select" required>
                <option value="">Select subject</option>
                @foreach($subjects as $subject)
                  <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                @endforeach
              </select>
              @error('subject_id')<div class="text-danger small">{{ $message }}</div>@enderror
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

            <div class="col-md-4">
              <label class="form-label fw-semibold">Assessment Type</label>
              <input type="text" name="assessment_type" class="form-control" value="{{ old('assessment_type') }}" placeholder="CAT / Quiz / Exam / Assignment">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Score</label>
              <input type="number" step="0.01" name="score" class="form-control" value="{{ old('score') }}">
              @error('score')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Out Of</label>
              <input type="number" step="0.01" name="out_of" class="form-control" value="{{ old('out_of') }}">
              @error('out_of')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Teacher</label>
              <select name="staff_id" class="form-select">
                <option value="">Select teacher (optional)</option>
                @foreach($staff as $member)
                  <option value="{{ $member->id }}" {{ old('staff_id') == $member->id ? 'selected' : '' }}>{{ $member->full_name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Academic Group</label>
              <input type="text" name="academic_group" class="form-control" value="{{ old('academic_group') }}">
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Remarks</label>
              <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}">
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-settings-primary">
              <i class="bi bi-check-circle"></i> Save Assessment
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

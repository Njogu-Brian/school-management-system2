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
        <h1 class="mb-1">New Subject Report</h1>
        <p class="text-muted mb-0">Weekly subject status.</p>
      </div>
      <a href="{{ route('reports.subject-reports.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <div class="settings-card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-book"></i> Report Details</h5>
        <p class="text-muted small mb-0">Week ending, class, subject, teacher and syllabus status.</p>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('reports.subject-reports.store') }}">
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
              <label class="form-label fw-semibold">Teacher</label>
              <select name="staff_id" class="form-select">
                <option value="">Select teacher</option>
                @foreach($staff as $member)
                  <option value="{{ $member->id }}" {{ old('staff_id') == $member->id ? 'selected' : '' }}>{{ $member->full_name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Topics Covered</label>
              <textarea name="topics_covered" class="form-control" rows="2">{{ old('topics_covered') }}</textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Syllabus Status</label>
              <select name="syllabus_status" class="form-select">
                <option value="">Select</option>
                @foreach(['On Track','Slightly Behind','Behind'] as $opt)
                  <option value="{{ $opt }}" {{ old('syllabus_status') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Strong %</label>
              <input type="number" step="0.01" name="strong_percent" class="form-control" value="{{ old('strong_percent') }}">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Average %</label>
              <input type="number" step="0.01" name="average_percent" class="form-control" value="{{ old('average_percent') }}">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Struggling %</label>
              <input type="number" step="0.01" name="struggling_percent" class="form-control" value="{{ old('struggling_percent') }}">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Homework Given</label>
              <select name="homework_given" class="form-select">
                <option value="">Select</option>
                <option value="1" {{ old('homework_given') === '1' ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ old('homework_given') === '0' ? 'selected' : '' }}>No</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Test Done</label>
              <select name="test_done" class="form-select">
                <option value="">Select</option>
                <option value="1" {{ old('test_done') === '1' ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ old('test_done') === '0' ? 'selected' : '' }}>No</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Marking Done</label>
              <select name="marking_done" class="form-select">
                <option value="">Select</option>
                <option value="1" {{ old('marking_done') === '1' ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ old('marking_done') === '0' ? 'selected' : '' }}>No</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Main Challenge</label>
              <input type="text" name="main_challenge" class="form-control" value="{{ old('main_challenge') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Support Needed</label>
              <input type="text" name="support_needed" class="form-control" value="{{ old('support_needed') }}">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Academic Group</label>
              <input type="text" name="academic_group" class="form-control" value="{{ old('academic_group') }}">
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-settings-primary">
              <i class="bi bi-check-circle"></i> Save Report
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

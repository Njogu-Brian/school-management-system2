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
        <h1 class="mb-1">New Class Report</h1>
        <p class="text-muted mb-0">Weekly class status.</p>
      </div>
      <a href="{{ route('reports.class-reports.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <div class="settings-card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-journal-plus"></i> Report Details</h5>
        <p class="text-muted small mb-0">Week ending, class, teacher and metrics.</p>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('reports.class-reports.store') }}">
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
              <label class="form-label fw-semibold">Class Teacher</label>
              <select name="class_teacher_id" class="form-select">
                <option value="">Select teacher</option>
                @foreach($staff as $member)
                  <option value="{{ $member->id }}" {{ old('class_teacher_id') == $member->id ? 'selected' : '' }}>{{ $member->full_name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Total Learners</label>
              <input type="number" name="total_learners" class="form-control" value="{{ old('total_learners') }}">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Frequent Absentees</label>
              <input type="number" name="frequent_absentees" class="form-control" value="{{ old('frequent_absentees') }}">
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">Discipline Level</label>
              <select name="discipline_level" class="form-select">
                <option value="">Select</option>
                @foreach(['Excellent','Good','Fair','Poor'] as $opt)
                  <option value="{{ $opt }}" {{ old('discipline_level') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Homework Completion</label>
              <select name="homework_completion" class="form-select">
                <option value="">Select</option>
                @foreach(['High','Medium','Low'] as $opt)
                  <option value="{{ $opt }}" {{ old('homework_completion') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Classroom Condition</label>
              <select name="classroom_condition" class="form-select">
                <option value="">Select</option>
                @foreach(['Good','Fair','Poor'] as $opt)
                  <option value="{{ $opt }}" {{ old('classroom_condition') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">Learners Struggling</label>
              <input type="number" name="learners_struggling" class="form-control" value="{{ old('learners_struggling') }}">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Learners Improved</label>
              <input type="number" name="learners_improved" class="form-control" value="{{ old('learners_improved') }}">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Parents to Contact</label>
              <input type="number" name="parents_to_contact" class="form-control" value="{{ old('parents_to_contact') }}">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Academic Group</label>
              <input type="text" name="academic_group" class="form-control" value="{{ old('academic_group') }}">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Notes</label>
              <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
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

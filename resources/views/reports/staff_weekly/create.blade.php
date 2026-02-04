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
        <h1 class="mb-1">New Staff Weekly Report</h1>
        <p class="text-muted mb-0">Weekly staff performance.</p>
      </div>
      <a href="{{ route('reports.staff-weekly.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <div class="settings-card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-person-plus"></i> Report Details</h5>
        <p class="text-muted small mb-0">Week ending, teacher, attendance and performance.</p>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('reports.staff-weekly.store') }}">
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
              <label class="form-label fw-semibold">Teacher</label>
              <select name="staff_id" class="form-select" required>
                <option value="">Select teacher</option>
                @foreach($staff as $member)
                  <option value="{{ $member->id }}" {{ old('staff_id') == $member->id ? 'selected' : '' }}>{{ $member->full_name }}</option>
                @endforeach
              </select>
              @error('staff_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">On Time All Week</label>
              <select name="on_time_all_week" class="form-select">
                <option value="">Select</option>
                <option value="1" {{ old('on_time_all_week') === '1' ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ old('on_time_all_week') === '0' ? 'selected' : '' }}>No</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Lessons Missed</label>
              <input type="number" name="lessons_missed" class="form-control" value="{{ old('lessons_missed') }}">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Books Marked</label>
              <select name="books_marked" class="form-select">
                <option value="">Select</option>
                <option value="1" {{ old('books_marked') === '1' ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ old('books_marked') === '0' ? 'selected' : '' }}>No</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">Schemes Updated</label>
              <select name="schemes_updated" class="form-select">
                <option value="">Select</option>
                <option value="1" {{ old('schemes_updated') === '1' ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ old('schemes_updated') === '0' ? 'selected' : '' }}>No</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Class Control</label>
              <select name="class_control" class="form-select">
                <option value="">Select</option>
                @foreach(['Good','Fair','Poor'] as $opt)
                  <option value="{{ $opt }}" {{ old('class_control') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">General Performance</label>
              <select name="general_performance" class="form-select">
                <option value="">Select</option>
                @foreach(['Excellent','Good','Fair','Poor'] as $opt)
                  <option value="{{ $opt }}" {{ old('general_performance') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
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
              <i class="bi bi-check-circle"></i> Save Report
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

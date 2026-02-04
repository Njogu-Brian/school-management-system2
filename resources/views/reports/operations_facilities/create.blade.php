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
        <h1 class="mb-1">New Operations & Facilities Report</h1>
        <p class="text-muted mb-0">Weekly facilities status.</p>
      </div>
      <a href="{{ route('reports.operations-facilities.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <div class="settings-card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-building-gear"></i> Report Details</h5>
        <p class="text-muted small mb-0">Week ending, campus, area, status and issue.</p>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('reports.operations-facilities.store') }}">
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
              <label class="form-label fw-semibold">Area</label>
              <input type="text" name="area" class="form-control" placeholder="Toilets / Classrooms / Desks / Kitchen / Water / Transport / Security" required value="{{ old('area') }}">
              @error('area')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">Status</label>
              <select name="status" class="form-select">
                <option value="">Select</option>
                @foreach(['Good','Fair','Poor'] as $opt)
                  <option value="{{ $opt }}" {{ old('status') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Resolved</label>
              <select name="resolved" class="form-select">
                <option value="">Select</option>
                <option value="1" {{ old('resolved') === '1' ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ old('resolved') === '0' ? 'selected' : '' }}>No</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Responsible Person</label>
              <input type="text" name="responsible_person" class="form-control" value="{{ old('responsible_person') }}">
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Issue Noted</label>
              <textarea name="issue_noted" class="form-control" rows="2">{{ old('issue_noted') }}</textarea>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Action Needed</label>
              <textarea name="action_needed" class="form-control" rows="2">{{ old('action_needed') }}</textarea>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Notes</label>
              <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
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

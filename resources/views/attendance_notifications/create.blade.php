@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Attendance</div>
        <h1 class="mb-1">Add Notification Recipient</h1>
        <p class="text-muted mb-0">Who should receive attendance alerts.</p>
      </div>
      <a href="{{ route('attendance.notifications.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Recipient Details</h5>
        <span class="pill-badge pill-secondary">Required fields *</span>
      </div>
      <div class="card-body">
        <form action="{{ route('attendance.notifications.store') }}" method="POST" class="row g-3">
            @csrf
            <div class="col-md-6">
                <label class="form-label">Label <span class="text-danger">*</span></label>
                <input type="text" name="label" class="form-control" required placeholder="e.g., Operations Lead">
            </div>
            <div class="col-md-6">
                <label class="form-label">Select Staff <span class="text-danger">*</span></label>
                <select name="staff_id" class="form-select" required>
                    <option value="">-- Select Staff --</option>
                    @foreach($staff as $s)
                        <option value="{{ $s->id }}">{{ $s->first_name }} {{ $s->last_name }} ({{ $s->phone_number }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Assign Classes</label>
                <select name="classroom_ids[]" class="form-select" multiple>
                    @foreach($classrooms as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Leave empty to assign ALL classes.</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">Active?</label>
                <select name="active" class="form-select">
                    <option value="1" selected>Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="{{ route('attendance.notifications.index') }}" class="btn btn-ghost-strong">Cancel</a>
                <button type="submit" class="btn btn-settings-primary">Save Recipient</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

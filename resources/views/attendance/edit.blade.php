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
        <h1 class="mb-1">Edit Attendance</h1>
        <div class="d-flex align-items-center gap-2 flex-wrap mt-2" style="color: #ffffff; font-size: 15px;">
          <span style="color: #ffffff; font-weight: 500;">
            <i class="bi bi-person"></i> <strong>{{ $attendance->student->full_name }}</strong>
          </span>
          <span style="color: rgba(255, 255, 255, 0.7);">|</span>
          <span style="color: #ffffff; font-weight: 500;">
            <i class="bi bi-building"></i> {{ $attendance->student->classroom->name ?? 'N/A' }}
            @if($attendance->student->stream)
              - {{ $attendance->student->stream->name }}
            @endif
          </span>
          <span style="color: rgba(255, 255, 255, 0.7);">|</span>
          <span style="color: #ffffff; font-weight: 500;">
            <i class="bi bi-card-text"></i> {{ $attendance->student->admission_number }}
          </span>
        </div>
      </div>
      <a href="{{ route('attendance.records') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back to Records
      </a>
    </div>

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Update Status</h5>
          <p class="small mb-0" style="color: #6b7280;">
            Date: <strong style="color: #0f172a;">{{ $attendance->date->format('l, F d, Y') }}</strong>
          </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <span class="pill-badge pill-info">{{ $attendance->student->classroom->name ?? 'N/A' }}</span>
          @if($attendance->student->stream)
            <span class="pill-badge pill-secondary">{{ $attendance->student->stream->name }}</span>
          @endif
        </div>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('attendance.update', $attendance->id) }}" class="row g-3">
          @csrf
          <div class="col-12">
            <label class="form-label d-block">Attendance Status</label>
            <div class="d-flex gap-3 flex-wrap">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="status" id="present" value="present" {{ $attendance->status === 'present' ? 'checked' : '' }}>
                <label class="form-check-label" for="present">Present</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="status" id="absent" value="absent" {{ $attendance->status === 'absent' ? 'checked' : '' }}>
                <label class="form-check-label" for="absent">Absent</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="status" id="late" value="late" {{ $attendance->status === 'late' ? 'checked' : '' }}>
                <label class="form-check-label" for="late">Late</label>
              </div>
            </div>
          </div>

          <div class="col-12">
            <label for="reason" class="form-label">Reason (if absent/late)</label>
            <input type="text" name="reason" id="reason" class="form-control" value="{{ old('reason', $attendance->reason) }}" {{ $attendance->status === 'present' ? 'disabled' : '' }}>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('attendance.records') }}" class="btn btn-ghost-strong">Cancel</a>
            <button type="submit" class="btn btn-settings-primary">Update Attendance</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function toggleReason() {
        const status = document.querySelector('input[name="status"]:checked')?.value;
        const reason = document.getElementById('reason');
        if (status === 'present') {
            reason.disabled = true;
            reason.value = '';
        } else {
            reason.disabled = false;
        }
    }
    document.querySelectorAll('input[name="status"]').forEach(r => r.addEventListener('change', toggleReason));
    toggleReason();
});
</script>
@endpush
@endsection

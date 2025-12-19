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
        <p class="text-muted mb-0">{{ $attendance->student->full_name }}</p>
      </div>
      <a href="{{ route('attendance.mark.form') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back to Marking
      </a>
    </div>

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Update Status</h5>
        <span class="pill-badge pill-secondary">Admission #{{ $attendance->student->admission_number }}</span>
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
            <a href="{{ route('attendance.mark.form') }}" class="btn btn-ghost-strong">Cancel</a>
            @if(can_access('attendance', 'record', 'edit'))
            <button type="submit" class="btn btn-settings-primary">Update Attendance</button>
            @endif
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

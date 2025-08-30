@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Attendance for {{ $attendance->student->full_name }}</h1>

    <form method="POST" action="{{ route('attendance.update', $attendance->id) }}" class="mt-3">
        @csrf

        <div class="mb-3">
            <label class="form-label d-block">Attendance Status:</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="present" value="present" {{ $attendance->status === 'present' ? 'checked' : '' }}>
                <label class="form-check-label" for="present">Present</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="absent" value="absent" {{ $attendance->status === 'absent' ? 'checked' : '' }}>
                <label class="form-check-label" for="absent">Absent</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="status" id="late" value="late" {{ $attendance->status === 'late' ? 'checked' : '' }}>
                <label class="form-check-label" for="late">Late</label>
            </div>
        </div>

        <div class="mb-3">
            <label for="reason" class="form-label">Reason (if absent/late)</label>
            <input type="text" name="reason" id="reason" class="form-control" value="{{ old('reason', $attendance->reason) }}" {{ $attendance->status === 'present' ? 'disabled' : '' }}>
        </div>

        @if(can_access('attendance', 'record', 'edit'))
        <button type="submit" class="btn btn-primary">Update Attendance</button>
        @endif
    </form>
</div>

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
@endsection

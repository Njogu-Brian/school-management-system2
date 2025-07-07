@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Attendance for {{ $attendance->student->name }}</h1>

    <form method="POST" action="{{ route('attendance.update', $attendance->id) }}">
        @csrf

        <label for="is_present">Attendance Status:</label>
        <select name="is_present" id="is_present" class="form-control">
            <option value="1" {{ $attendance->is_present ? 'selected' : '' }}>Present</option>
            <option value="0" {{ !$attendance->is_present ? 'selected' : '' }}>Absent</option>
        </select>

        <label for="reason">Reason (if absent):</label>
        <input type="text" name="reason" id="reason" class="form-control" value="{{ old('reason', $attendance->reason) }}" {{ $attendance->is_present ? 'disabled' : '' }}>

@if(can_access('attendance', 'record', 'edit'))
        <button type="submit" class="btn btn-primary mt-3">Update Attendance</button>
@endif
    </form>
</div>

<script>
    document.getElementById('is_present').addEventListener('change', function() {
        let reasonField = document.getElementById('reason');
        if (this.value == "1") {
            reasonField.value = ""; // Clear reason when marking present
            reasonField.disabled = true;
        } else {
            reasonField.disabled = false;
        }
    });
</script>
@endsection

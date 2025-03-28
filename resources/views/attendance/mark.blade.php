@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Mark Attendance</h1>

    <!-- Success Message -->
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Class Selection Form -->
    <form method="GET" action="{{ route('attendance.mark.form') }}">
        <label for="class">Select Class:</label>
        <select name="class" id="class" class="form-control" onchange="this.form.submit()">
            <option value="">-- Select Class --</option>
            @foreach($classes as $id => $name)
                <option value="{{ $id }}" {{ isset($selectedClass) && $selectedClass == $id ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
    </form>

    <hr>

    <!-- Students List for Selected Class -->
    @if ($students->isNotEmpty())
    <form method="POST" action="{{ route('attendance.mark') }}">
        @csrf
        <input type="hidden" name="class" value="{{ $selectedClass }}">

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Attendance</th>
                    <th>Reason (if absent)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalPresent = 0;
                    $totalAbsent = 0;
                    $unmarkedAttendance = false;
                @endphp
                @foreach ($students as $student)
                    @php
                        $attendance = $attendanceRecords->get($student->id);
                        if ($attendance) {
                            if ($attendance->is_present) {
                                $totalPresent++;
                            } else {
                                $totalAbsent++;
                            }
                        } else {
                            $unmarkedAttendance = true;
                        }
                    @endphp
                    <tr>
                        <td>
                            {{ $student->full_name ?? "{$student->first_name} {$student->middle_name} {$student->last_name}" }}
                        </td>
                        <td>
                            <select name="status_{{ $student->id }}" class="form-control attendance-select" required>
                                <option value="">-- Select --</option>
                                <option value="1" {{ isset($attendance) && $attendance->is_present ? 'selected' : '' }}>Present</option>
                                <option value="0" {{ isset($attendance) && !$attendance->is_present ? 'selected' : '' }}>Absent</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="reason_{{ $student->id }}" 
                                   class="form-control reason-input"
                                   value="{{ isset($attendance) ? $attendance->reason : '' }}"
                                   {{ (isset($attendance) && $attendance->is_present) ? 'disabled' : '' }}>
                        </td>
                        <td>
                            @if ($attendance)
                                <!-- Show Edit Button -->
                                <a href="{{ route('attendance.edit', $attendance->id) }}" class="btn btn-warning">Edit</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Section -->
        <div class="mt-4">
            <h4>Attendance Summary</h4>
            <p><strong>Total Present:</strong> {{ $totalPresent }}</p>
            <p><strong>Total Absent:</strong> {{ $totalAbsent }}</p>
        </div>

        <!-- Submit Button (Only show if attendance isn't marked for all students) -->
        @if ($unmarkedAttendance)
            <button type="submit" class="btn btn-primary">Submit Attendance</button>
        @endif
    </form>
    @else
        <p>No students found for this class.</p>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.attendance-select').forEach(select => {
            select.addEventListener('change', function () {
                let studentId = this.name.split('_')[1];
                let reasonField = document.querySelector(`input[name="reason_${studentId}"]`);
                
                if (this.value == "0") {
                    reasonField.disabled = false;
                    reasonField.required = true; // Ensure reason is required when absent
                } else {
                    reasonField.disabled = true;
                    reasonField.required = false;
                    reasonField.value = ''; // Clear reason if marked Present
                }
            });
        });
    });
</script>
@endsection

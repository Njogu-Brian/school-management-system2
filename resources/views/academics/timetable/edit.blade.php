@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Timetable - {{ $classroom->name }}</h1>
        <a href="{{ route('academics.timetable.classroom', ['classroom' => $classroom->id, 'academic_year_id' => $year->id, 'term_id' => $term->id]) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <form action="{{ route('academics.timetable.save') }}" method="POST" id="timetableForm">
        @csrf
        <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
        <input type="hidden" name="academic_year_id" value="{{ $year->id }}">
        <input type="hidden" name="term_id" value="{{ $term->id }}">

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="timetableTable">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                @foreach($days as $day)
                                    <th>{{ $day }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($timeSlots as $slot)
                            <tr>
                                <td class="fw-bold">
                                    @if(in_array($slot['period'], ['Break', 'Lunch']))
                                        {{ $slot['period'] }}<br>
                                        <small>{{ $slot['start'] }} - {{ $slot['end'] }}</small>
                                    @else
                                        Period {{ $slot['period'] }}<br>
                                        <small>{{ $slot['start'] }} - {{ $slot['end'] }}</small>
                                    @endif
                                </td>
                                @foreach($days as $day)
                                    <td>
                                        @if(in_array($slot['period'], ['Break', 'Lunch']))
                                            <input type="hidden" name="timetable[{{ $day }}][{{ $slot['period'] }}][start]" value="{{ $slot['start'] }}">
                                            <input type="hidden" name="timetable[{{ $day }}][{{ $slot['period'] }}][end]" value="{{ $slot['end'] }}">
                                            <span class="text-muted">{{ $slot['period'] }}</span>
                                        @else
                                            @php
                                                $existing = $savedTimetable->where('day', $day)->where('period', $slot['period'])->first();
                                            @endphp
                                            <select name="timetable[{{ $day }}][{{ $slot['period'] }}][subject_id]" 
                                                    class="form-select form-select-sm timetable-subject" 
                                                    data-day="{{ $day }}" 
                                                    data-period="{{ $slot['period'] }}">
                                                <option value="">-- Free --</option>
                                                @foreach($assignments as $assignment)
                                                    <option value="{{ $assignment->subject_id }}" 
                                                        data-teacher-id="{{ $assignment->staff_id }}"
                                                        {{ $existing && $existing->subject_id == $assignment->subject_id ? 'selected' : '' }}>
                                                        {{ $assignment->subject->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="timetable[{{ $day }}][{{ $slot['period'] }}][start]" value="{{ $slot['start'] }}">
                                            <input type="hidden" name="timetable[{{ $day }}][{{ $slot['period'] }}][end]" value="{{ $slot['end'] }}">
                                            <input type="hidden" name="timetable[{{ $day }}][{{ $slot['period'] }}][teacher_id]" 
                                                   class="timetable-teacher" 
                                                   value="{{ $existing ? $existing->staff_id : '' }}">
                                            <input type="text" name="timetable[{{ $day }}][{{ $slot['period'] }}][room]" 
                                                   class="form-control form-control-sm mt-1" 
                                                   placeholder="Room" 
                                                   value="{{ $existing ? $existing->room : '' }}">
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('academics.timetable.classroom', ['classroom' => $classroom->id, 'academic_year_id' => $year->id, 'term_id' => $term->id]) }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Timetable</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-populate teacher when subject is selected
    document.querySelectorAll('.timetable-subject').forEach(function(select) {
        select.addEventListener('change', function() {
            const teacherInput = this.closest('td').querySelector('.timetable-teacher');
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.dataset.teacherId) {
                teacherInput.value = selectedOption.dataset.teacherId;
            } else {
                teacherInput.value = '';
            }
        });
    });

    // Check for conflicts before submit
    document.getElementById('timetableForm').addEventListener('submit', function(e) {
        const conflicts = [];
        const teacherSchedule = {};

        document.querySelectorAll('.timetable-subject').forEach(function(select) {
            if (select.value) {
                const day = select.dataset.day;
                const period = select.dataset.period;
                const teacherInput = select.closest('td').querySelector('.timetable-teacher');
                const teacherId = teacherInput.value;

                if (teacherId) {
                    const key = teacherId + '_' + day + '_' + period;
                    if (teacherSchedule[key]) {
                        conflicts.push({
                            day: day,
                            period: period,
                            teacher: teacherId
                        });
                    } else {
                        teacherSchedule[key] = true;
                    }
                }
            }
        });

        if (conflicts.length > 0) {
            e.preventDefault();
            alert('Teacher conflicts detected! Please resolve before saving.');
            return false;
        }
    });
});
</script>
@endsection



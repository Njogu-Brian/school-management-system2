@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Timetable</div>
        <h1 class="mb-1">Edit Timetable - {{ $classroom->name }}</h1>
        <p class="text-muted mb-0">Adjust subjects, rooms, and teacher assignments.</p>
      </div>
      <a href="{{ route('academics.timetable.classroom', ['classroom' => $classroom->id, 'academic_year_id' => $year->id, 'term_id' => $term->id]) }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <form action="{{ route('academics.timetable.save') }}" method="POST" id="timetableForm" class="settings-card">
      @csrf
      <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
      <input type="hidden" name="academic_year_id" value="{{ $year->id }}">
      <input type="hidden" name="term_id" value="{{ $term->id }}">

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern align-middle mb-0" id="timetableTable">
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
                <td class="fw-semibold">
                  @if(in_array($slot['period'], ['Break', 'Lunch']))
                    {{ $slot['period'] }}<br><small>{{ $slot['start'] }} - {{ $slot['end'] }}</small>
                  @else
                    Period {{ $slot['period'] }}<br><small>{{ $slot['start'] }} - {{ $slot['end'] }}</small>
                  @endif
                </td>
                @foreach($days as $day)
                  <td>
                    @if(in_array($slot['period'], ['Break', 'Lunch']))
                      <input type="hidden" name="timetable[{{ $day }}][{{ $slot['period'] }}][start]" value="{{ $slot['start'] }}">
                      <input type="hidden" name="timetable[{{ $day }}][{{ $slot['period'] }}][end]" value="{{ $slot['end'] }}">
                      <span class="text-muted">{{ $slot['period'] }}</span>
                    @else
                      @php $existing = $savedTimetable->where('day', $day)->where('period', $slot['period'])->first(); @endphp
                      <select name="timetable[{{ $day }}][{{ $slot['period'] }}][subject_id]" class="form-select form-select-sm timetable-subject" data-day="{{ $day }}" data-period="{{ $slot['period'] }}">
                        <option value="">-- Free --</option>
                        @foreach($assignments as $assignment)
                          <option value="{{ $assignment->subject_id }}" data-teacher-id="{{ $assignment->staff_id }}" {{ $existing && $existing->subject_id == $assignment->subject_id ? 'selected' : '' }}>{{ $assignment->subject->name }}</option>
                        @endforeach
                      </select>
                      <input type="hidden" name="timetable[{{ $day }}][{{ $slot['period'] }}][start]" value="{{ $slot['start'] }}">
                      <input type="hidden" name="timetable[{{ $day }}][{{ $slot['period'] }}][end]" value="{{ $slot['end'] }}">
                      <input type="hidden" name="timetable[{{ $day }}][{{ $slot['period'] }}][teacher_id]" class="timetable-teacher" value="{{ $existing ? $existing->staff_id : '' }}">
                      <input type="text" name="timetable[{{ $day }}][{{ $slot['period'] }}][room]" class="form-control form-control-sm mt-1" placeholder="Room" value="{{ $existing ? $existing->room : '' }}">
                    @endif
                  </td>
                @endforeach
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('academics.timetable.classroom', ['classroom' => $classroom->id, 'academic_year_id' => $year->id, 'term_id' => $term->id]) }}" class="btn btn-ghost-strong">Cancel</a>
        <button type="submit" class="btn btn-settings-primary">Save Timetable</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.timetable-subject').forEach(function(select) {
    select.addEventListener('change', function() {
      const teacherInput = this.closest('td').querySelector('.timetable-teacher');
      const selectedOption = this.options[this.selectedIndex];
      teacherInput.value = selectedOption.dataset.teacherId || '';
    });
  });

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
            conflicts.push({ day, period, teacher: teacherId });
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
@endpush
@endsection

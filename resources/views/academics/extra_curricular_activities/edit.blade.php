@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Activities</div>
        <h1 class="mb-1">Edit Activity</h1>
        <p class="text-muted mb-0">Update scheduling, finance, and assigned students.</p>
      </div>
      <a href="{{ route('academics.extra-curricular-activities.show', $extra_curricular_activity) }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <form action="{{ route('academics.extra-curricular-activities.update', $extra_curricular_activity) }}" method="POST" class="row g-3">
          @csrf
          @method('PUT')

          <div class="col-md-6">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $extra_curricular_activity->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Type <span class="text-danger">*</span></label>
            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
              <option value="">Select Type</option>
              <option value="club" {{ old('type', $extra_curricular_activity->type) == 'club' ? 'selected' : '' }}>Club</option>
              <option value="sport" {{ old('type', $extra_curricular_activity->type) == 'sport' ? 'selected' : '' }}>Sport</option>
              <option value="event" {{ old('type', $extra_curricular_activity->type) == 'event' ? 'selected' : '' }}>Event</option>
              <option value="parade" {{ old('type', $extra_curricular_activity->type) == 'parade' ? 'selected' : '' }}>Parade</option>
              <option value="other" {{ old('type', $extra_curricular_activity->type) == 'other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Day</label>
            <select name="day" class="form-select">
              <option value="">Select Day</option>
              <option value="Monday" {{ old('day', $extra_curricular_activity->day) == 'Monday' ? 'selected' : '' }}>Monday</option>
              <option value="Tuesday" {{ old('day', $extra_curricular_activity->day) == 'Tuesday' ? 'selected' : '' }}>Tuesday</option>
              <option value="Wednesday" {{ old('day', $extra_curricular_activity->day) == 'Wednesday' ? 'selected' : '' }}>Wednesday</option>
              <option value="Thursday" {{ old('day', $extra_curricular_activity->day) == 'Thursday' ? 'selected' : '' }}>Thursday</option>
              <option value="Friday" {{ old('day', $extra_curricular_activity->day) == 'Friday' ? 'selected' : '' }}>Friday</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Start Time</label>
            <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $extra_curricular_activity->start_time ? $extra_curricular_activity->start_time->format('H:i') : '') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">End Time</label>
            <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $extra_curricular_activity->end_time ? $extra_curricular_activity->end_time->format('H:i') : '') }}">
          </div>

          <div class="col-md-4">
            <label class="form-label">Period</label>
            <input type="number" name="period" class="form-control" min="1" max="10" value="{{ old('period', $extra_curricular_activity->period) }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">Academic Year <span class="text-danger">*</span></label>
            <select name="academic_year_id" class="form-select @error('academic_year_id') is-invalid @enderror" required>
              <option value="">Select Year</option>
              @foreach($years as $year)
                <option value="{{ $year->id }}" {{ old('academic_year_id', $extra_curricular_activity->academic_year_id) == $year->id ? 'selected' : '' }}>{{ $year->year }}</option>
              @endforeach
            </select>
            @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Term <span class="text-danger">*</span></label>
            <select name="term_id" class="form-select @error('term_id') is-invalid @enderror" required>
              <option value="">Select Term</option>
              @foreach($terms as $term)
                <option value="{{ $term->id }}" {{ old('term_id', $extra_curricular_activity->term_id) == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
              @endforeach
            </select>
            @error('term_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label">Classrooms</label>
            <select name="classroom_ids[]" class="form-select" multiple>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" {{ in_array($classroom->id, old('classroom_ids', $extra_curricular_activity->classroom_ids ?? [])) ? 'selected' : '' }}>{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Supervising Staff</label>
            <select name="staff_ids[]" class="form-select" multiple>
              @foreach($staff as $member)
                <option value="{{ $member->id }}" {{ in_array($member->id, old('staff_ids', $extra_curricular_activity->staff_ids ?? [])) ? 'selected' : '' }}>{{ $member->full_name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $extra_curricular_activity->description) }}</textarea>
          </div>

          <div class="col-12"><div class="divider">Finance Integration</div></div>

          <div class="col-md-6">
            <label class="form-label">Fee Amount (KES)</label>
            <input type="number" name="fee_amount" class="form-control @error('fee_amount') is-invalid @enderror" step="0.01" min="0" value="{{ old('fee_amount', $extra_curricular_activity->fee_amount) }}" placeholder="0.00">
            <small class="text-muted">Leave empty if free. Fee creates/updates votehead and class fee structures.</small>
            @if($extra_curricular_activity->votehead)
              <div class="alert alert-soft alert-info border-0 mt-2"><small><i class="bi bi-info-circle"></i> Linked votehead: <strong>{{ $extra_curricular_activity->votehead->name }}</strong></small></div>
            @endif
            @error('fee_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="auto_invoice" value="1" id="auto_invoice" {{ old('auto_invoice', $extra_curricular_activity->auto_invoice) ? 'checked' : '' }}>
              <label class="form-check-label" for="auto_invoice"><strong>Auto-invoice assigned students</strong></label>
              <small class="text-muted d-block">Invoice students when assigned to this activity.</small>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">Assigned Students</label>
            <input type="text" id="student-search" class="form-control mb-2" placeholder="Search students by name or admission number...">
            <select name="student_ids[]" id="student_ids" class="form-select" multiple style="min-height: 150px;">
              @php
                $selectedStudents = old('student_ids', $extra_curricular_activity->student_ids ?? []);
                $allStudents = \App\Models\Student::with('classroom')->orderBy('first_name')->get();
              @endphp
              @foreach($allStudents as $student)
                <option value="{{ $student->id }}" data-name="{{ strtolower($student->first_name . ' ' . $student->last_name) }}" data-admission="{{ strtolower($student->admission_number ?? '') }}" data-class="{{ strtolower($student->classroom->name ?? '') }}" {{ in_array($student->id, $selectedStudents) ? 'selected' : '' }}>
                  {{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }}) - {{ $student->classroom->name ?? 'No Class' }}
                </option>
              @endforeach
            </select>
            <small class="text-muted">Hold Ctrl/Cmd to select multiple. Use search to filter. Assigned: {{ count($selectedStudents) }}.</small>
          </div>

          <div class="col-12">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $extra_curricular_activity->is_active) ? 'checked' : '' }}>
                  <label class="form-check-label" for="is_active">Active</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="repeat_weekly" value="1" id="repeat_weekly" {{ old('repeat_weekly', $extra_curricular_activity->repeat_weekly) ? 'checked' : '' }}>
                  <label class="form-check-label" for="repeat_weekly">Repeat Weekly</label>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('academics.extra-curricular-activities.show', $extra_curricular_activity) }}" class="btn btn-ghost-strong">Cancel</a>
            <button type="submit" class="btn btn-settings-primary">Update Activity</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.getElementById('student-search').addEventListener('input', function(e){
  const search=e.target.value.toLowerCase();
  document.querySelectorAll('#student_ids option').forEach(option=>{
    const name=option.getAttribute('data-name')||'';
    const admission=option.getAttribute('data-admission')||'';
    const cls=option.getAttribute('data-class')||'';
    option.style.display = (name.includes(search)||admission.includes(search)||cls.includes(search)) ? '' : 'none';
  });
});
</script>
@endpush
@endsection

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
        <h1 class="mb-1">Create Activity</h1>
        <p class="text-muted mb-0">Add clubs, sports, or events with scheduling and finance options.</p>
      </div>
      <a href="{{ route('academics.extra-curricular-activities.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <form action="{{ route('academics.extra-curricular-activities.store') }}" method="POST" class="row g-3">
          @csrf

          <div class="col-md-6">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Type <span class="text-danger">*</span></label>
            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
              <option value="">Select Type</option>
              <option value="club" {{ old('type') == 'club' ? 'selected' : '' }}>Club</option>
              <option value="sport" {{ old('type') == 'sport' ? 'selected' : '' }}>Sport</option>
              <option value="event" {{ old('type') == 'event' ? 'selected' : '' }}>Event</option>
              <option value="parade" {{ old('type') == 'parade' ? 'selected' : '' }}>Parade</option>
              <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Day</label>
            <select name="day" class="form-select">
              <option value="">Select Day</option>
              <option value="Monday" {{ old('day') == 'Monday' ? 'selected' : '' }}>Monday</option>
              <option value="Tuesday" {{ old('day') == 'Tuesday' ? 'selected' : '' }}>Tuesday</option>
              <option value="Wednesday" {{ old('day') == 'Wednesday' ? 'selected' : '' }}>Wednesday</option>
              <option value="Thursday" {{ old('day') == 'Thursday' ? 'selected' : '' }}>Thursday</option>
              <option value="Friday" {{ old('day') == 'Friday' ? 'selected' : '' }}>Friday</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Start Time</label>
            <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">End Time</label>
            <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}">
          </div>

          <div class="col-md-4">
            <label class="form-label">Period</label>
            <input type="number" name="period" class="form-control" min="1" max="10" value="{{ old('period') }}">
            <small class="text-muted">Period number if scheduled during class time.</small>
          </div>
          <div class="col-md-4">
            <label class="form-label">Academic Year <span class="text-danger">*</span></label>
            <select name="academic_year_id" class="form-select @error('academic_year_id') is-invalid @enderror" required>
              <option value="">Select Year</option>
              @foreach($years as $year)
                <option value="{{ $year->id }}" {{ old('academic_year_id', $currentYearId ?? null) == $year->id ? 'selected' : '' }}>{{ $year->year }}</option>
              @endforeach
            </select>
            @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-4">
            <label class="form-label">Term <span class="text-danger">*</span></label>
            <select name="term_id" class="form-select @error('term_id') is-invalid @enderror" required>
              <option value="">Select Term</option>
              @foreach($terms as $term)
                <option value="{{ $term->id }}" {{ old('term_id', $currentTermId ?? null) == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
              @endforeach
            </select>
            @error('term_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label">Classrooms</label>
            <select name="classroom_ids[]" class="form-select" multiple>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" {{ in_array($classroom->id, old('classroom_ids', [])) ? 'selected' : '' }}>{{ $classroom->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Hold Ctrl/Cmd to select multiple. Leave empty for all classrooms.</small>
          </div>

          <div class="col-12">
            <label class="form-label">Supervising Staff</label>
            <select name="staff_ids[]" class="form-select" multiple>
              @foreach($staff as $member)
                <option value="{{ $member->id }}" {{ in_array($member->id, old('staff_ids', [])) ? 'selected' : '' }}>{{ $member->full_name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Hold Ctrl/Cmd to select multiple.</small>
          </div>

          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
          </div>

          <div class="col-12">
            <div class="divider">Finance Integration</div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Fee Amount (KES)</label>
            <input type="number" name="fee_amount" class="form-control @error('fee_amount') is-invalid @enderror" step="0.01" min="0" value="{{ old('fee_amount') }}" placeholder="0.00">
            <small class="text-muted">Leave empty if free. Setting a fee auto-creates a votehead and adds to class fee structures.</small>
            @error('fee_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="auto_invoice" value="1" id="auto_invoice" {{ old('auto_invoice') ? 'checked' : '' }}>
              <label class="form-check-label" for="auto_invoice"><strong>Auto-invoice assigned students</strong></label>
              <small class="text-muted d-block">Invoice students when assigned to this activity.</small>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">Assign Students</label>
            <input type="text" id="student-search" class="form-control mb-2" placeholder="Search students by name or admission number...">
            <select name="student_ids[]" id="student_ids" class="form-select" multiple style="min-height: 150px;">
              @php
                $selectedStudents = old('student_ids', []);
                $allStudents = \App\Models\Student::with('classroom')
                    ->where('archive', 0)
                    ->where('is_alumni', false)
                    ->orderBy('first_name')
                    ->get();
              @endphp
              @foreach($allStudents as $student)
                <option value="{{ $student->id }}" data-name="{{ strtolower($student->full_name) }}" data-admission="{{ strtolower($student->admission_number ?? '') }}" data-class="{{ strtolower($student->classroom->name ?? '') }}" {{ in_array($student->id, $selectedStudents) ? 'selected' : '' }}>
                  {{ $student->full_name }} ({{ $student->admission_number }}) - {{ $student->classroom->name ?? 'No Class' }}
                </option>
              @endforeach
            </select>
            <small class="text-muted">Hold Ctrl/Cmd to select multiple students. Use search to filter.</small>
          </div>

          <div class="col-12">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                  <label class="form-check-label" for="is_active">Active</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="repeat_weekly" value="1" id="repeat_weekly" {{ old('repeat_weekly', true) ? 'checked' : '' }}>
                  <label class="form-check-label" for="repeat_weekly">Repeat Weekly</label>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('academics.extra-curricular-activities.index') }}" class="btn btn-ghost-strong">Cancel</a>
            <button type="submit" class="btn btn-settings-primary">Create Activity</button>
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

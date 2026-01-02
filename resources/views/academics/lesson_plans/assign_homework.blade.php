@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Lesson Plans</div>
        <h1 class="mb-1">Assign Homework</h1>
        <p class="text-muted mb-0">Create homework directly from this lesson plan.</p>
      </div>
      <a href="{{ route('academics.lesson-plans.show', $lesson_plan) }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="row g-3">
      <div class="col-md-8">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-journal-text"></i><h5 class="mb-0">Lesson Plan Details</h5></div>
          <div class="card-body">
            <table class="table table-borderless mb-0">
              <tr><th width="200">Subject</th><td>{{ $lesson_plan->subject->name }}</td></tr>
              <tr><th>Classroom</th><td>{{ $lesson_plan->classroom->name }}</td></tr>
              <tr><th>Title</th><td>{{ $lesson_plan->title }}</td></tr>
              <tr><th>Planned Date</th><td>{{ $lesson_plan->planned_date->format('l, d M Y') }}</td></tr>
            </table>
          </div>
        </div>

        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-check2-square"></i><h5 class="mb-0">Assign Homework</h5></div>
          <div class="card-body">
            <form action="{{ route('academics.lesson-plans.assign-homework', $lesson_plan) }}" method="POST" enctype="multipart/form-data">
              @csrf

              <div class="mb-3">
                <label class="form-label">Homework Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title', 'Homework - ' . $lesson_plan->title) }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="mb-3">
                <label class="form-label">Instructions <span class="text-danger">*</span></label>
                <textarea class="form-control @error('instructions') is-invalid @enderror" name="instructions" rows="5" required>{{ old('instructions', $lesson_plan->learning_outcomes ?? '') }}</textarea>
                @error('instructions')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Due Date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control @error('due_date') is-invalid @enderror" name="due_date" value="{{ old('due_date', $lesson_plan->planned_date->addDays(1)->format('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required>
                  @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                  <label class="form-label">Max Score (Optional)</label>
                  <input type="number" class="form-control @error('max_score') is-invalid @enderror" name="max_score" value="{{ old('max_score') }}" min="1">
                  @error('max_score')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="form-check form-switch mt-3">
                <input class="form-check-input" type="checkbox" id="allow_late_submission" name="allow_late_submission" value="1" {{ old('allow_late_submission', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="allow_late_submission">Allow Late Submission</label>
              </div>

              <div class="mt-3">
                <label class="form-label">Assign To</label>
                <div class="d-flex flex-column gap-1">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="target_scope" id="target_class" value="class" checked onchange="toggleStudentSelection()">
                    <label class="form-check-label" for="target_class">All Students in Class</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="target_scope" id="target_students" value="students" onchange="toggleStudentSelection()">
                    <label class="form-check-label" for="target_students">Selected Students</label>
                  </div>
                </div>
              </div>

              <div class="mt-3" id="student_selection" style="display: none;">
                <label class="form-label">Select Students</label>
                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                  @php $students = \App\Models\Student::where('classroom_id', $lesson_plan->classroom_id)
                      ->where('archive', 0)
                      ->where('is_alumni', false)
                      ->get(); @endphp
                  @foreach($students as $student)
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="student_ids[]" value="{{ $student->id }}" id="student_{{ $student->id }}">
                    <label class="form-check-label" for="student_{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }}</label>
                  </div>
                  @endforeach
                </div>
              </div>

              <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('academics.lesson-plans.show', $lesson_plan) }}" class="btn btn-ghost-strong">Cancel</a>
                <button type="submit" class="btn btn-settings-primary"><i class="bi bi-check-circle"></i> Assign Homework</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-info-circle"></i><h5 class="mb-0">Lesson Plan Info</h5></div>
          <div class="card-body">
            <small class="text-muted d-block mb-1"><strong>Subject:</strong> {{ $lesson_plan->subject->name }}</small>
            <small class="text-muted d-block mb-1"><strong>Classroom:</strong> {{ $lesson_plan->classroom->name }}</small>
            <small class="text-muted d-block mb-1"><strong>Date:</strong> {{ $lesson_plan->planned_date->format('d M Y') }}</small>
            <small class="text-muted d-block"><strong>Duration:</strong> {{ $lesson_plan->duration_minutes }} minutes</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
function toggleStudentSelection(){
  const targetStudents=document.getElementById('target_students');
  const studentSelection=document.getElementById('student_selection');
  if(targetStudents.checked){studentSelection.style.display='block';}
  else{studentSelection.style.display='none';document.querySelectorAll('input[name="student_ids[]"]').forEach(cb=>cb.checked=false);}
}
</script>
@endpush
@endsection

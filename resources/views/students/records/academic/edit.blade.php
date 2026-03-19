@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.academic-history.index', $student) }}">Academic History</a></li>
      <li class="breadcrumb-item active">Edit Entry</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Edit Academic History - {{ $student->full_name }}</h1>
    <a href="{{ route('students.academic-history.show', [$student, $academicHistory]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  @include('students.partials.alerts')

  <form action="{{ route('students.academic-history.update', [$student, $academicHistory]) }}" method="POST">
    @csrf @method('PUT')
    <div class="card">
      <div class="card-header">Academic History Information</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Classroom</label>
            <select name="classroom_id" class="form-select" id="academic-classroom-id">
              <option value="">Select Classroom</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" @selected(old('classroom_id', $academicHistory->classroom_id)==$classroom->id)>{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6" id="academic-stream-wrapper">
            <label class="form-label">Stream</label>
            <select name="stream_id" class="form-select" id="academic-stream-id">
              <option value="">Select Stream</option>
            </select>
            <small class="text-muted">Only shown when the selected classroom has streams.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Enrollment Date <span class="text-danger">*</span></label>
            <input type="date" name="enrollment_date" class="form-control" value="{{ old('enrollment_date', $academicHistory->enrollment_date->toDateString()) }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Completion Date</label>
            <input type="date" name="completion_date" class="form-control" value="{{ old('completion_date', $academicHistory->completion_date?->toDateString()) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Promotion Status</label>
            <select name="promotion_status" class="form-select">
              <option value="">Select Status</option>
              <option value="promoted" @selected(old('promotion_status', $academicHistory->promotion_status)=='promoted')>Promoted</option>
              <option value="retained" @selected(old('promotion_status', $academicHistory->promotion_status)=='retained')>Retained</option>
              <option value="demoted" @selected(old('promotion_status', $academicHistory->promotion_status)=='demoted')>Demoted</option>
              <option value="transferred" @selected(old('promotion_status', $academicHistory->promotion_status)=='transferred')>Transferred</option>
              <option value="graduated" @selected(old('promotion_status', $academicHistory->promotion_status)=='graduated')>Graduated</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Final Grade</label>
            <input type="number" name="final_grade" class="form-control" value="{{ old('final_grade', $academicHistory->final_grade) }}" step="0.01" min="0" max="100">
          </div>
          <div class="col-md-6">
            <label class="form-label">Class Position</label>
            <input type="number" name="class_position" class="form-control" value="{{ old('class_position', $academicHistory->class_position) }}" min="1">
          </div>
          <div class="col-md-6">
            <label class="form-label">Stream Position</label>
            <input type="number" name="stream_position" class="form-control" value="{{ old('stream_position', $academicHistory->stream_position) }}" min="1">
          </div>
          <div class="col-md-12">
            <label class="form-label">Remarks</label>
            <textarea name="remarks" class="form-control" rows="3">{{ old('remarks', $academicHistory->remarks) }}</textarea>
          </div>
          <div class="col-md-12">
            <label class="form-label">Teacher Comments</label>
            <textarea name="teacher_comments" class="form-control" rows="3">{{ old('teacher_comments', $academicHistory->teacher_comments) }}</textarea>
          </div>
          <div class="col-md-6">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_current" value="1" id="is_current" @checked(old('is_current', $academicHistory->is_current))>
              <label class="form-check-label" for="is_current">Mark as Current Academic Year</label>
            </div>
            <small class="text-muted">If checked, all other entries will be marked as non-current</small>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('students.academic-history.show', [$student, $academicHistory]) }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Entry</button>
      </div>
    </div>
  </form>
</div>

@push('scripts')
<script>
(function(){
  const classroomSelect = document.getElementById('academic-classroom-id');
  const streamSelect = document.getElementById('academic-stream-id');
  const streamWrapper = document.getElementById('academic-stream-wrapper');
  const getStreamsUrl = '{{ route("students.getStreams") }}';
  const preselect = '{{ old("stream_id", $academicHistory->stream_id ?? "") }}';

  function loadStreams(classroomId) {
    streamSelect.innerHTML = '<option value="">Select Stream</option>';
    if (streamWrapper) streamWrapper.style.display = classroomId ? '' : 'none';
    if (!classroomId) return;
    fetch(getStreamsUrl, {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}','Content-Type': 'application/json'},
      body: JSON.stringify({ classroom_id: classroomId })
    }).then(r=>r.json()).then(streams=>{
      (streams||[]).forEach(s=>{
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.name;
        if (String(s.id) === String(preselect)) opt.selected = true;
        streamSelect.appendChild(opt);
      });
      if (streamWrapper) streamWrapper.style.display = (streams && streams.length > 0) ? '' : 'none';
    }).catch(()=>{});
  }
  classroomSelect?.addEventListener('change', ()=> loadStreams(classroomSelect.value));
  loadStreams(classroomSelect?.value || '');
})();
</script>
@endpush
@endsection


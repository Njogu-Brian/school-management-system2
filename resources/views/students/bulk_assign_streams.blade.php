@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Bulk Assign Students to Streams</h1>
        <p class="text-muted mb-0">Select a classroom and assign multiple students to a stream.</p>
      </div>
      <a href="{{ route('students.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back to Students
      </a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="settings-card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><i class="bi bi-1-circle"></i> Step 1: Select Classroom</h5>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('students.bulk.assign-streams') }}">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Classroom</label>
              <select name="classroom_id" class="form-select" required onchange="this.form.submit()">
                <option value="">-- Select a Classroom --</option>
                @foreach($classrooms as $classroom)
                  <option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>
                    {{ $classroom->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
        </form>
      </div>
    </div>

    @if($selectedClassroom)
      <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="mb-0">
            <i class="bi bi-2-circle"></i> Step 2: Select Students and Assign Stream
            <span class="pill-badge pill-primary ms-2">{{ $selectedClassroom->name }}</span>
          </h5>
        </div>
        <div class="card-body">
          @if($students->count() > 0)
            <form method="POST" action="{{ route('students.bulk.assign-streams.process') }}" id="bulkStreamForm">
              @csrf
              <input type="hidden" name="classroom_id" value="{{ $selectedClassroom->id }}">
              <div class="row mb-4 g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Select Stream</label>
                  <select name="stream_id" class="form-select" required>
                    <option value="">-- Select a Stream --</option>
                    @foreach($streams as $stream)
                      @php
                        $isValidStream = $stream->classroom_id == $selectedClassroom->id || 
                                        $stream->classrooms->contains('id', $selectedClassroom->id);
                      @endphp
                      @if($isValidStream)
                        <option value="{{ $stream->id }}">{{ $stream->name }} @if($stream->classroom) ({{ $stream->classroom->name }}) @endif</option>
                      @endif
                    @endforeach
                  </select>
                  <small class="text-muted">Only streams assigned to {{ $selectedClassroom->name }} are shown</small>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end gap-2 flex-wrap">
                  <button type="button" class="btn btn-ghost-strong" onclick="selectAll()">
                    <i class="bi bi-check-all"></i> Select All
                  </button>
                  <button type="button" class="btn btn-ghost-strong" onclick="deselectAll()">
                    <i class="bi bi-x-square"></i> Deselect All
                  </button>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-modern table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th width="50"><input type="checkbox" id="selectAll" onchange="toggleAll(this)"></th>
                      <th>Admission #</th>
                      <th>Name</th>
                      <th>Current Stream</th>
                      <th>Parent Contact</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($students as $student)
                      <tr>
                        <td><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-checkbox"></td>
                        <td><span class="pill-badge pill-secondary">{{ $student->admission_number }}</span></td>
                        <td>
                          <div class="fw-semibold">{{ $student->full_name }}</div>
                          <small class="text-muted">{{ $student->gender }}</small>
                        </td>
                        <td>
                          @if($student->stream)
                            <span class="pill-badge pill-info">{{ $student->stream->name }}</span>
                          @else
                            <span class="text-muted">No stream</span>
                          @endif
                        </td>
                        <td>
                          <small class="text-muted">{{ $student->parent?->primary_contact_phone ?? $student->parent?->primary_contact_email ?? 'No contact' }}</small>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>

              <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted"><span id="selectedCount">0</span> student(s) selected</div>
                <button type="submit" class="btn btn-settings-primary" id="submitBtn" disabled>
                  <i class="bi bi-save"></i> Assign Selected Students to Stream
                </button>
              </div>
            </form>
          @else
            <div class="alert alert-soft border-0">
              <i class="bi bi-info-circle"></i> No active students found in {{ $selectedClassroom->name }}.
            </div>
          @endif
        </div>
      </div>
    @else
      <div class="alert alert-soft border-0">
        <i class="bi bi-info-circle"></i> Please select a classroom to begin assigning students to streams.
      </div>
    @endif
  </div>
</div>

@push('scripts')
<script>
  function toggleAll(checkbox) {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = checkbox.checked);
    updateSelectedCount();
  }
  function selectAll() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = true);
    document.getElementById('selectAll').checked = true;
    updateSelectedCount();
  }
  function deselectAll() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateSelectedCount();
  }
  function updateSelectedCount() {
    const checked = document.querySelectorAll('.student-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = checked;
    const stream = document.querySelector('select[name="stream_id"]').value;
    document.getElementById('submitBtn').disabled = checked === 0 || !stream;
  }
  document.querySelectorAll('.student-checkbox').forEach(cb => cb.addEventListener('change', updateSelectedCount));
  const streamSelect = document.querySelector('select[name="stream_id"]');
  streamSelect?.addEventListener('change', updateSelectedCount);
  document.getElementById('bulkStreamForm')?.addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('.student-checkbox:checked').length;
    const streamId = streamSelect.value;
    if (checked === 0) { e.preventDefault(); alert('Please select at least one student.'); return; }
    if (!streamId) { e.preventDefault(); alert('Please select a stream.'); return; }
    if (!confirm(`Assign ${checked} student(s) to the selected stream?`)) e.preventDefault();
  });
</script>
@endpush
@endsection

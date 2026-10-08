@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics</div>
        <h1 class="mb-1">Edit Subject</h1>
        <p class="text-muted mb-0">Update subject details and classroom assignments.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('academics.subjects.show', $subject) }}" class="btn btn-ghost-strong text-info">
          <i class="bi bi-eye"></i> View
        </a>
        <a href="{{ route('academics.subjects.index') }}" class="btn btn-ghost-strong">
          <i class="bi bi-arrow-left"></i> Back
        </a>
      </div>
    </div>

    <form method="POST" action="{{ route('academics.subjects.update', $subject) }}">
      @csrf @method('PUT')
      <div class="row">
        <div class="col-md-8">
          <div class="settings-card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
              <i class="bi bi-info-circle"></i>
              <h5 class="mb-0">Basic Information</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Subject Code <span class="text-danger">*</span></label>
                  <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $subject->code) }}" required>
                  @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                  <label class="form-label">Subject Name <span class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $subject->name) }}" required>
                  @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-md-6">
                  <label class="form-label">Level</label>
                  <select name="level" class="form-select">
                    <option value="">-- Select Level --</option>
                    <optgroup label="Pre-Primary">
                      <option value="PP1" {{ old('level', $subject->level) == 'PP1' ? 'selected' : '' }}>PP1</option>
                      <option value="PP2" {{ old('level', $subject->level) == 'PP2' ? 'selected' : '' }}>PP2</option>
                    </optgroup>
                    <optgroup label="Lower Primary">
                      <option value="Grade 1" {{ old('level', $subject->level) == 'Grade 1' ? 'selected' : '' }}>Grade 1</option>
                      <option value="Grade 2" {{ old('level', $subject->level) == 'Grade 2' ? 'selected' : '' }}>Grade 2</option>
                      <option value="Grade 3" {{ old('level', $subject->level) == 'Grade 3' ? 'selected' : '' }}>Grade 3</option>
                    </optgroup>
                    <optgroup label="Upper Primary">
                      <option value="Grade 4" {{ old('level', $subject->level) == 'Grade 4' ? 'selected' : '' }}>Grade 4</option>
                      <option value="Grade 5" {{ old('level', $subject->level) == 'Grade 5' ? 'selected' : '' }}>Grade 5</option>
                      <option value="Grade 6" {{ old('level', $subject->level) == 'Grade 6' ? 'selected' : '' }}>Grade 6</option>
                    </optgroup>
                    <optgroup label="Junior Secondary">
                      <option value="Grade 7" {{ old('level', $subject->level) == 'Grade 7' ? 'selected' : '' }}>Grade 7</option>
                      <option value="Grade 8" {{ old('level', $subject->level) == 'Grade 8' ? 'selected' : '' }}>Grade 8</option>
                      <option value="Grade 9" {{ old('level', $subject->level) == 'Grade 9' ? 'selected' : '' }}>Grade 9</option>
                    </optgroup>
                  </select>
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-md-6">
                  <label class="form-label">Learning Area</label>
                  <input type="text" name="learning_area" class="form-control" value="{{ old('learning_area', $subject->learning_area) }}">
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-md-6">
                  <div class="form-check form-switch">
                    <input type="checkbox" name="is_optional" value="1" class="form-check-input" id="is_optional" {{ old('is_optional', $subject->is_optional) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_optional">Optional Subject</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $subject->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="settings-card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
              <i class="bi bi-info-circle"></i>
              <h6 class="mb-0">Current Status</h6>
            </div>
            <div class="card-body">
              <p class="mb-1"><strong>Classrooms:</strong> {{ $classroomAssignments->count() }}</p>
              <p class="mb-1"><strong>Created:</strong> {{ $subject->created_at->format('M d, Y') }}</p>
              <p class="mb-0"><strong>Updated:</strong> {{ $subject->updated_at->format('M d, Y') }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="settings-card">
        <div class="card-header">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-building"></i>
            <h5 class="mb-0">Classroom Assignments</h5>
          </div>
          <p class="text-muted small mb-0 mt-1">A class with Love and Peace has one row per stream, so each stream can have its own teacher.</p>
        </div>
        <div class="card-body">
          <div id="classroom-assignments">
            @forelse($classroomAssignments as $index => $assignment)
            <div class="classroom-assignment-item mb-3 p-3 border rounded">
              <input type="hidden" name="classroom_assignments[{{ $index }}][id]" value="{{ $assignment->id }}">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">Classroom</label>
                  <select name="classroom_assignments[{{ $index }}][classroom_id]" class="form-select js-classroom-select" required>
                    <option value="">-- Select Classroom --</option>
                    @foreach($classrooms as $classroom)
                      <option value="{{ $classroom->id }}" {{ $assignment->classroom_id == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Stream</label>
                  <select name="classroom_assignments[{{ $index }}][stream_id]" class="form-select js-stream-select" data-selected-stream="{{ $assignment->stream_id }}">
                    <option value="">All streams</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Teacher</label>
                  <select name="classroom_assignments[{{ $index }}][staff_id]" class="form-select">
                    <option value="">-- Select Teacher --</option>
                    @foreach($teachers as $teacher)
                      <option value="{{ $teacher->id }}" {{ $assignment->staff_id == $teacher->id ? 'selected' : '' }}>{{ $teacher->full_name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Academic Year</label>
                  <select name="classroom_assignments[{{ $index }}][academic_year_id]" class="form-select">
                    <option value="">-- All --</option>
                    @foreach($years as $year)
                      <option value="{{ $year->id }}" {{ $assignment->academic_year_id == $year->id ? 'selected' : '' }}>{{ $year->year }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-1">
                  <label class="form-label">Term</label>
                  <select name="classroom_assignments[{{ $index }}][term_id]" class="form-select">
                    <option value="">-- All --</option>
                    @foreach($terms as $term)
                      <option value="{{ $term->id }}" {{ $assignment->term_id == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-1">
                  <label class="form-label">Compulsory</label>
                  <div class="form-check form-switch">
                    <input type="checkbox" name="classroom_assignments[{{ $index }}][is_compulsory]" value="1" class="form-check-input" {{ $assignment->is_compulsory ? 'checked' : '' }}>
                  </div>
                </div>
              </div>
              <button type="button" class="btn btn-sm btn-ghost-strong text-danger mt-2 remove-assignment">
                <i class="bi bi-trash"></i> Remove
              </button>
            </div>
            @empty
            <div class="classroom-assignment-item mb-3 p-3 border rounded">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label">Classroom</label>
                  <select name="classroom_assignments[0][classroom_id]" class="form-select js-classroom-select">
                    <option value="">-- Select Classroom --</option>
                    @foreach($classrooms as $classroom)
                      <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Stream</label>
                  <select name="classroom_assignments[0][stream_id]" class="form-select js-stream-select">
                    <option value="">All streams</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Teacher</label>
                  <select name="classroom_assignments[0][staff_id]" class="form-select">
                    <option value="">-- Select Teacher --</option>
                    @foreach($teachers as $teacher)
                      <option value="{{ $teacher->id }}">{{ $teacher->full_name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Academic Year</label>
                  <select name="classroom_assignments[0][academic_year_id]" class="form-select">
                    <option value="">-- All --</option>
                    @foreach($years as $year)
                      <option value="{{ $year->id }}">{{ $year->year }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-1">
                  <label class="form-label">Term</label>
                  <select name="classroom_assignments[0][term_id]" class="form-select">
                    <option value="">-- All --</option>
                    @foreach($terms as $term)
                      <option value="{{ $term->id }}">{{ $term->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-1">
                  <label class="form-label">Compulsory</label>
                  <div class="form-check form-switch">
                    <input type="checkbox" name="classroom_assignments[0][is_compulsory]" value="1" class="form-check-input" checked>
                  </div>
                </div>
              </div>
            </div>
            @endforelse
          </div>
          <button type="button" class="btn btn-sm btn-ghost-strong" id="add-classroom-assignment">
            <i class="bi bi-plus-circle"></i> Add Another Classroom
          </button>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <a href="{{ route('academics.subjects.index') }}" class="btn btn-ghost-strong">Cancel</a>
        <button type="submit" class="btn btn-settings-primary btn-lg">
          <i class="bi bi-check-circle"></i> Update Subject
        </button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
  const streamsByClassroom = @json($streamsByClassroom ?? new \stdClass());
  const existingSlots = @json($classroomAssignments->map(fn ($assignment) => [
      'id' => $assignment->id,
      'classroom_id' => $assignment->classroom_id,
      'stream_id' => $assignment->stream_id,
      'staff_id' => $assignment->staff_id,
  ])->values());
  let assignmentIndex = {{ max($classroomAssignments->count(), 1) }};
  const addBtn = document.getElementById('add-classroom-assignment');
  const container = document.getElementById('classroom-assignments');

  function streamsFor(classroomId) {
    if (!classroomId) return [];
    return streamsByClassroom[classroomId] || streamsByClassroom[String(classroomId)] || [];
  }

  function findSlot(classroomId, streamId) {
    return existingSlots.find((slot) => String(slot.classroom_id) === String(classroomId) && String(slot.stream_id) === String(streamId)) || null;
  }

  function fillStreams(selectEl, classroomId, selectedStreamId) {
    if (!selectEl) return;
    const streams = streamsFor(classroomId);
    selectEl.innerHTML = '';
    if (!streams.length) {
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = classroomId ? 'No streams / class-level' : 'Select a class first';
      selectEl.appendChild(opt);
      return;
    }
    streams.forEach((stream) => {
      const opt = document.createElement('option');
      opt.value = stream.id;
      opt.textContent = stream.name;
      if (String(selectedStreamId || '') === String(stream.id)) opt.selected = true;
      selectEl.appendChild(opt);
    });
  }

  function setNamedValue(row, field, value) {
    const el = row.querySelector(`[name*="[${field}]"]`);
    if (!el) return;
    if (el.type === 'checkbox') {
      el.checked = !!value;
    } else {
      el.value = value ? String(value) : '';
    }
  }

  function usedStreamIds(classroomId, exceptRow) {
    const ids = new Set();
    container.querySelectorAll('.classroom-assignment-item').forEach((row) => {
      if (row === exceptRow) return;
      if (row.querySelector('.js-classroom-select')?.value !== String(classroomId)) return;
      const value = row.querySelector('.js-stream-select')?.value;
      if (value) ids.add(String(value));
    });
    return ids;
  }

  function cloneAssignment(source) {
    const template = source.cloneNode(true);
    delete template.dataset.bound;
    template.querySelectorAll('[name]').forEach((el) => {
      el.name = el.name.replace(/\[(\d+)\]/, `[${assignmentIndex}]`);
    });
    assignmentIndex++;
    const streamSel = template.querySelector('.js-stream-select');
    if (streamSel) streamSel.removeAttribute('data-selected-stream');
    return template;
  }

  function expandStreams(row) {
    const classSel = row.querySelector('.js-classroom-select');
    const streamSel = row.querySelector('.js-stream-select');
    if (!classSel || !streamSel) return;
    const classroomId = classSel.value;
    const streams = streamsFor(classroomId);
    const preferred = streamSel.getAttribute('data-selected-stream') || streamSel.value || '';

    if (!streams.length) {
      fillStreams(streamSel, classroomId, '');
      return;
    }

    const used = usedStreamIds(classroomId, row);
    const free = streams.find((stream) => !used.has(String(stream.id)) && String(stream.id) !== String(preferred));
    const chosen = preferred && !used.has(String(preferred)) ? preferred : (free ? free.id : streams[0].id);
    fillStreams(streamSel, classroomId, chosen);
    streamSel.removeAttribute('data-selected-stream');

    const chosenSlot = findSlot(classroomId, streamSel.value);
    if (chosenSlot) {
      setNamedValue(row, 'id', chosenSlot.id);
      setNamedValue(row, 'staff_id', chosenSlot.staff_id);
    }

    streams.forEach((stream) => {
      if (String(stream.id) === String(streamSel.value) || used.has(String(stream.id))) return;
      const clone = cloneAssignment(row);
      container.appendChild(clone);
      clone.querySelector('.js-classroom-select').value = classroomId;
      fillStreams(clone.querySelector('.js-stream-select'), classroomId, stream.id);
      const slot = findSlot(classroomId, stream.id);
      setNamedValue(clone, 'id', slot ? slot.id : '');
      setNamedValue(clone, 'staff_id', slot ? slot.staff_id : '');
      bindClassroomStream(clone);
      used.add(String(stream.id));
    });
  }

  function bindClassroomStream(row) {
    const classSel = row.querySelector('.js-classroom-select');
    const streamSel = row.querySelector('.js-stream-select');
    if (!classSel || !streamSel || row.dataset.bound === '1') return;
    row.dataset.bound = '1';
    const saved = streamSel.getAttribute('data-selected-stream') || '';
    if (saved) {
      fillStreams(streamSel, classSel.value, saved);
    } else {
      expandStreams(row);
    }
    classSel.addEventListener('change', () => {
      streamSel.removeAttribute('data-selected-stream');
      setNamedValue(row, 'id', '');
      setNamedValue(row, 'staff_id', '');
      expandStreams(row);
    });
  }

  if (container) {
    container.querySelectorAll('.classroom-assignment-item').forEach(bindClassroomStream);
  }

  if (addBtn && container) {
    addBtn.addEventListener('click', () => {
      const template = container.firstElementChild?.cloneNode(true);
      if (!template) return;

      template.querySelectorAll('input[type="hidden"]').forEach(el => {
        if (el.name && el.name.includes('[id]')) {
          el.value = '';
        }
      });

      template.querySelectorAll('select, input').forEach(el => {
        if (el.name) {
          el.name = el.name.replace(/\[(\d+)\]/, `[${assignmentIndex}]`);
        }
        if (el.type === 'checkbox') {
          el.checked = false;
        } else if (el.tagName === 'SELECT') {
          el.selectedIndex = 0;
        } else if (el.type !== 'hidden') {
          el.value = '';
        }
      });

      const streamSel = template.querySelector('.js-stream-select');
      if (streamSel) streamSel.removeAttribute('data-selected-stream');
      delete template.dataset.bound;

      if (!template.querySelector('.remove-assignment')) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm btn-ghost-strong text-danger mt-2 remove-assignment';
        btn.innerHTML = '<i class="bi bi-trash"></i> Remove';
        template.appendChild(btn);
      }

      container.appendChild(template);
      bindClassroomStream(template);
      assignmentIndex++;
    });
  }

  document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-assignment')) {
      const item = e.target.closest('.classroom-assignment-item');
      if (item && container.childElementCount > 1) {
        item.remove();
      }
    }
  });
</script>
@endpush
@endsection

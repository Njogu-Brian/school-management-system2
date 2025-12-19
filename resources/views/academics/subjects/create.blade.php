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
        <h1 class="mb-1">Create New Subject</h1>
        <p class="text-muted mb-0">Define subject info and optional classroom assignments.</p>
      </div>
      <a href="{{ route('academics.subjects.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <form method="POST" action="{{ route('academics.subjects.store') }}">
      @csrf
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
                  <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                  @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <small class="text-muted">Unique code (e.g., ENG, MATH)</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Subject Name <span class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                  @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-md-6">
                  <label class="form-label">Subject Group</label>
                  <select name="subject_group_id" class="form-select @error('subject_group_id') is-invalid @enderror">
                    <option value="">-- None --</option>
                    @foreach($groups as $group)
                      <option value="{{ $group->id }}" {{ old('subject_group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                  </select>
                  @error('subject_group_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                  <label class="form-label">Level</label>
                  <select name="level" class="form-select">
                    <option value="">-- Select Level --</option>
                    <optgroup label="Pre-Primary">
                      <option value="PP1" {{ old('level') == 'PP1' ? 'selected' : '' }}>PP1</option>
                      <option value="PP2" {{ old('level') == 'PP2' ? 'selected' : '' }}>PP2</option>
                    </optgroup>
                    <optgroup label="Lower Primary">
                      <option value="Grade 1" {{ old('level') == 'Grade 1' ? 'selected' : '' }}>Grade 1</option>
                      <option value="Grade 2" {{ old('level') == 'Grade 2' ? 'selected' : '' }}>Grade 2</option>
                      <option value="Grade 3" {{ old('level') == 'Grade 3' ? 'selected' : '' }}>Grade 3</option>
                    </optgroup>
                    <optgroup label="Upper Primary">
                      <option value="Grade 4" {{ old('level') == 'Grade 4' ? 'selected' : '' }}>Grade 4</option>
                      <option value="Grade 5" {{ old('level') == 'Grade 5' ? 'selected' : '' }}>Grade 5</option>
                      <option value="Grade 6" {{ old('level') == 'Grade 6' ? 'selected' : '' }}>Grade 6</option>
                    </optgroup>
                    <optgroup label="Junior Secondary">
                      <option value="Grade 7" {{ old('level') == 'Grade 7' ? 'selected' : '' }}>Grade 7</option>
                      <option value="Grade 8" {{ old('level') == 'Grade 8' ? 'selected' : '' }}>Grade 8</option>
                      <option value="Grade 9" {{ old('level') == 'Grade 9' ? 'selected' : '' }}>Grade 9</option>
                    </optgroup>
                  </select>
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-md-6">
                  <label class="form-label">Learning Area</label>
                  <input type="text" name="learning_area" class="form-control" value="{{ old('learning_area') }}" placeholder="e.g., Language, Mathematics">
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-md-6">
                  <div class="form-check form-switch">
                    <input type="checkbox" name="is_optional" value="1" class="form-check-input" id="is_optional" {{ old('is_optional') ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_optional">Optional Subject</label>
                  </div>
                  <small class="text-muted">Mark if this is an optional/selective subject</small>
                </div>
                <div class="col-md-6">
                  <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
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
              <i class="bi bi-lightbulb"></i>
              <h6 class="mb-0">Quick Tips</h6>
            </div>
            <div class="card-body">
              <ul class="small mb-0">
                <li>Use unique codes for each subject</li>
                <li>For Junior High, mark selective subjects as optional</li>
                <li>You can assign classrooms after creating the subject</li>
                <li>Use the CBC generator for quick setup</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="settings-card">
        <div class="card-header d-flex align-items-center gap-2">
          <i class="bi bi-building"></i>
          <h5 class="mb-0">Classroom Assignments (Optional)</h5>
        </div>
        <div class="card-body">
          <p class="text-muted">Assign this subject to classrooms now or later. Leave blank to skip.</p>
          <div id="classroom-assignments">
            <div class="classroom-assignment-item mb-3 p-3 border rounded">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Classroom</label>
                  <select name="classroom_assignments[0][classroom_id]" class="form-select">
                    <option value="">-- Select Classroom --</option>
                    @foreach($classrooms as $classroom)
                      <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                    @endforeach
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
                <div class="col-md-2">
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
          </div>
          <button type="button" class="btn btn-sm btn-ghost-strong" id="add-classroom-assignment">
            <i class="bi bi-plus-circle"></i> Add Another Classroom
          </button>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <a href="{{ route('academics.subjects.index') }}" class="btn btn-ghost-strong">Cancel</a>
        <button type="submit" class="btn btn-settings-primary btn-lg">
          <i class="bi bi-check-circle"></i> Create Subject
        </button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
  let assignmentIndex = 1;
  const addBtn = document.getElementById('add-classroom-assignment');
  const container = document.getElementById('classroom-assignments');

  if (addBtn && container) {
    addBtn.addEventListener('click', function() {
      const first = container.firstElementChild;
      if (!first) return;
      const clone = first.cloneNode(true);
      clone.querySelectorAll('select, input').forEach(el => {
        if (el.name) {
          el.name = el.name.replace(/\[0\]/, `[${assignmentIndex}]`);
        }
        if (el.type === 'checkbox') {
          el.checked = false;
        } else {
          el.value = '';
        }
      });
      container.appendChild(clone);
      assignmentIndex++;
    });
  }
</script>
@endpush
@endsection

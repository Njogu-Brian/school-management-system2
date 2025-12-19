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
        <h1 class="mb-1">Subjects Management</h1>
        <p class="text-muted mb-0">Create, group, and assign subjects to classrooms and teachers.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('academics.subjects.teacher-assignments') }}" class="btn btn-ghost-strong">
          <i class="bi bi-person-lines-fill"></i> Subject Teacher Assignments
        </a>
        <button type="button" class="btn btn-ghost-strong" data-bs-toggle="modal" data-bs-target="#generateCBCModal">
          <i class="bi bi-magic"></i> Generate CBC/CBE Subjects
        </button>
        <a href="{{ route('academics.subjects.create') }}" class="btn btn-settings-primary">
          <i class="bi bi-plus-circle"></i> Add Subject
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" action="{{ route('academics.subjects.index') }}" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name, Code, Learning Area...">
          </div>
          <div class="col-md-2">
            <label class="form-label">Group</label>
            <select name="group_id" class="form-select">
              <option value="">All Groups</option>
              @foreach($groups as $group)
                <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Level</label>
            <select name="level" class="form-select">
              <option value="">All Levels</option>
              @foreach($levels as $level)
                <option value="{{ $level }}" {{ request('level') == $level ? 'selected' : '' }}>{{ $level }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Type</label>
            <select name="is_optional" class="form-select">
              <option value="">All</option>
              <option value="0" {{ request('is_optional') === '0' ? 'selected' : '' }}>Mandatory</option>
              <option value="1" {{ request('is_optional') === '1' ? 'selected' : '' }}>Optional</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="is_active" class="form-select">
              <option value="">All</option>
              <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
              <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>
          <div class="col-md-1 d-flex justify-content-end">
            <button type="submit" class="btn btn-settings-primary w-100">
              <i class="bi bi-search"></i>
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Subjects</h5>
          <p class="text-muted small mb-0">Group, level, and classroom coverage.</p>
        </div>
        <span class="input-chip">{{ $subjects->total() ?? $subjects->count() }} total</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Group</th>
                <th>Level</th>
                <th>Learning Area</th>
                <th>Type</th>
                <th>Status</th>
                <th>Classrooms</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($subjects as $subject)
              <tr>
                <td class="fw-semibold">{{ $subject->code }}</td>
                <td>{{ $subject->name }}</td>
                <td>
                  @if($subject->group)
                    <span class="pill-badge pill-info">{{ $subject->group->name }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @if($subject->level)
                    <span class="pill-badge pill-secondary">{{ $subject->level }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>{{ $subject->learning_area ?? '—' }}</td>
                <td>
                  @if($subject->is_optional)
                    <span class="pill-badge pill-warning">Optional</span>
                  @else
                    <span class="pill-badge pill-success">Mandatory</span>
                  @endif
                </td>
                <td>
                  @if($subject->is_active)
                    <span class="pill-badge pill-success">Active</span>
                  @else
                    <span class="pill-badge pill-muted">Inactive</span>
                  @endif
                </td>
                <td>
                  @php
                    $assignedClassrooms = $subject->classroomSubjects->filter(fn($assignment) => $assignment->classroom);
                  @endphp
                  @if($assignedClassrooms->isNotEmpty())
                    <div class="d-flex flex-wrap gap-1">
                      @foreach($assignedClassrooms->take(3) as $assignment)
                        <span class="pill-badge pill-primary">
                          {{ $assignment->classroom->name }}
                          @if($assignment->stream)
                            <span class="fw-normal small text-white-50">• {{ $assignment->stream->name }}</span>
                          @endif
                        </span>
                      @endforeach
                    </div>
                    @if($assignedClassrooms->count() > 3)
                      <small class="text-muted d-block mt-1">+{{ $assignedClassrooms->count() - 3 }} more</small>
                    @endif
                  @else
                    <span class="text-muted">Not Assigned</span>
                  @endif
                </td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-1 flex-wrap">
                    <a href="{{ route('academics.subjects.show', $subject) }}" class="btn btn-sm btn-ghost-strong text-info" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('academics.subjects.edit', $subject) }}" class="btn btn-sm btn-ghost-strong" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form action="{{ route('academics.subjects.destroy', $subject) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this subject?');">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-ghost-strong text-danger" title="Delete">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox"></i> No subjects found
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">
        {{ $subjects->links() }}
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="generateCBCModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content settings-card mb-0">
      <form method="POST" action="{{ route('academics.subjects.generate-cbc') }}">
        @csrf
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title">Generate CBC/CBE Subjects</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Level <span class="text-danger">*</span></label>
            <select name="level" class="form-select" required>
              <option value="">Select Level</option>
              <optgroup label="Pre-Primary">
                <option value="PP1">PP1</option>
                <option value="PP2">PP2</option>
              </optgroup>
              <optgroup label="Lower Primary">
                <option value="Grade 1">Grade 1</option>
                <option value="Grade 2">Grade 2</option>
                <option value="Grade 3">Grade 3</option>
              </optgroup>
              <optgroup label="Upper Primary">
                <option value="Grade 4">Grade 4</option>
                <option value="Grade 5">Grade 5</option>
                <option value="Grade 6">Grade 6</option>
              </optgroup>
              <optgroup label="Junior Secondary">
                <option value="Grade 7">Grade 7</option>
                <option value="Grade 8">Grade 8</option>
                <option value="Grade 9">Grade 9</option>
              </optgroup>
            </select>
            <small class="text-muted">Select the grade level to generate subjects for</small>
          </div>

          <div class="mb-3">
            <div class="form-check">
              <input type="checkbox" name="assign_to_classrooms" id="assign_to_classrooms" value="1" class="form-check-input">
              <label class="form-check-label" for="assign_to_classrooms">Assign to Classrooms</label>
            </div>
            <small class="text-muted">Automatically assign generated subjects to selected classrooms</small>
          </div>

          <div class="mb-3" id="classrooms-select" style="display: none;">
            <label class="form-label">Select Classrooms</label>
            <select name="classroom_ids[]" class="form-select" multiple size="5">
              @foreach(\App\Models\Academics\Classroom::orderBy('name')->get() as $classroom)
                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Hold Ctrl/Cmd to select multiple classrooms</small>
          </div>

          <div class="alert alert-soft border-0 alert-info">
            <i class="bi bi-info-circle"></i> This will create all CBC/CBE subjects for the selected level. Optional subjects for Junior High will be marked as optional automatically.
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-settings-primary">
            <i class="bi bi-magic"></i> Generate Subjects
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const checkbox = document.getElementById('assign_to_classrooms');
    const selectBlock = document.getElementById('classrooms-select');
    if (checkbox && selectBlock) {
      checkbox.addEventListener('change', () => {
        selectBlock.style.display = checkbox.checked ? 'block' : 'none';
      });
    }
  });
</script>
@endpush
@endsection

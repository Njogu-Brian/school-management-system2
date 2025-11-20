@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1">Subject Teacher Assignments</h1>
            <p class="text-muted mb-0">Assign multiple classroom subjects to teachers in a single action.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('academics.subjects.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Subjects
            </a>
            <a href="{{ route('academics.assign-teachers') }}" class="btn btn-outline-primary">
                <i class="bi bi-people"></i> Classroom Teacher Assignments
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
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

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('academics.subjects.teacher-assignments') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Subject, code, class, stream">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Subject</label>
                    <select name="subject_id" class="form-select">
                        <option value="">All Subjects</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                {{ $subject->code }} — {{ $subject->name }} @if($subject->level) ({{ $subject->level }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Classroom</label>
                    <select name="classroom_id" class="form-select">
                        <option value="">All Classrooms</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>
                                {{ $classroom->name }}
                            </option>
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
                    <label class="form-label">Assignment Status</label>
                    <select name="assigned" class="form-select">
                        <option value="">All</option>
                        <option value="assigned" {{ request('assigned') === 'assigned' ? 'selected' : '' }}>Assigned</option>
                        <option value="unassigned" {{ request('assigned') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        @foreach([25, 50, 75, 100] as $size)
                            <option value="{{ $size }}" {{ $perPage == $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="{{ route('academics.subjects.teacher-assignments') }}" class="btn btn-outline-secondary w-100">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <form method="POST" action="{{ route('academics.subjects.teacher-assignments.save') }}">
            @csrf
            <div class="card-body">
                @if($teachers->isEmpty())
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle"></i> No teachers found. Please add teacher staff profiles first.
                    </div>
                @endif

                @if($assignments->count() > 0)
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="selectAllAssignments">
                            <label class="form-check-label" for="selectAllAssignments">Select All Visible Rows</label>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <label for="bulkTeacherSelect" class="mb-0 text-muted">Apply teacher to selected</label>
                            <select id="bulkTeacherSelect" class="form-select form-select-sm">
                                <option value="">-- Choose Teacher --</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->full_name }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="applyTeacherBtn">
                                <i class="bi bi-arrow-down-up"></i> Apply
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>Classroom</th>
                                    <th>Stream</th>
                                    <th>Subject</th>
                                    <th>Current Teacher</th>
                                    <th style="width:220px;">Assign Teacher</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($assignments as $assignment)
                                    <tr>
                                        <td>
                                            <input type="checkbox"
                                                   class="form-check-input assignment-checkbox"
                                                   data-target="assignment-{{ $assignment->id }}">
                                        </td>
                                        <td>
                                            <strong>{{ $assignment->classroom->name ?? '—' }}</strong>
                                        </td>
                                        <td>{{ $assignment->stream->name ?? '—' }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $assignment->subject->name ?? '—' }}</div>
                                            <small class="text-muted">
                                                {{ $assignment->subject->code ?? '' }}
                                                @if($assignment->subject && $assignment->subject->level)
                                                    • {{ $assignment->subject->level }}
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            @if($assignment->teacher)
                                                <span class="badge bg-success">{{ $assignment->teacher->full_name }}</span>
                                            @else
                                                <span class="text-muted">Unassigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            <select
                                                name="assignments[{{ $assignment->id }}]"
                                                id="assignment-{{ $assignment->id }}"
                                                class="form-select form-select-sm @error('assignments.' . $assignment->id) is-invalid @enderror">
                                                <option value="">— Unassigned —</option>
                                                @foreach($teachers as $teacher)
                                                    <option value="{{ $teacher->id }}"
                                                        {{ (string) old('assignments.' . $assignment->id, $assignment->staff_id) === (string) $teacher->id ? 'selected' : '' }}>
                                                        {{ $teacher->full_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('assignments.' . $assignment->id)
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-3 gap-3">
                        <div>
                            Showing {{ $assignments->firstItem() }}-{{ $assignments->lastItem() }} of {{ $assignments->total() }} assignments
                        </div>
                        <div>
                            {{ $assignments->links() }}
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Save Teacher Assignments
                        </button>
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i> No classroom subject assignments matched your filters.
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('selectAllAssignments');
        const checkboxes = document.querySelectorAll('.assignment-checkbox');
        const bulkSelect = document.getElementById('bulkTeacherSelect');
        const applyButton = document.getElementById('applyTeacherBtn');

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
            });
        }

        if (applyButton && bulkSelect) {
            applyButton.addEventListener('click', function () {
                const teacherId = bulkSelect.value;
                if (!teacherId) {
                    alert('Please choose a teacher to apply.');
                    return;
                }

                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        const selectId = cb.getAttribute('data-target');
                        const select = document.getElementById(selectId);
                        if (select) {
                            select.value = teacherId;
                        }
                    }
                });
            });
        }
    });
</script>
@endpush


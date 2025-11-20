@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Subjects Management</h1>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('academics.subjects.teacher-assignments') }}" class="btn btn-outline-primary">
                <i class="bi bi-person-lines-fill"></i> Subject Teacher Assignments
            </a>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generateCBCModal">
                <i class="bi bi-magic"></i> Generate CBC/CBE Subjects
            </button>
            <a href="{{ route('academics.subjects.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Subject
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

    <!-- Filters Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('academics.subjects.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name, Code, Learning Area...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Group</label>
                    <select name="group_id" class="form-select">
                        <option value="">All Groups</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Level</label>
                    <select name="level" class="form-select">
                        <option value="">All Levels</option>
                        @foreach($levels as $level)
                            <option value="{{ $level }}" {{ request('level') == $level ? 'selected' : '' }}>
                                {{ $level }}
                            </option>
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
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Subjects Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subjects as $subject)
                        <tr>
                            <td><strong>{{ $subject->code }}</strong></td>
                            <td>{{ $subject->name }}</td>
                            <td>
                                @if($subject->group)
                                    <span class="badge bg-info">{{ $subject->group->name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($subject->level)
                                    <span class="badge bg-secondary">{{ $subject->level }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $subject->learning_area ?? '—' }}</td>
                            <td>
                                @if($subject->is_optional)
                                    <span class="badge bg-warning">Optional</span>
                                @else
                                    <span class="badge bg-success">Mandatory</span>
                                @endif
                            </td>
                            <td>
                                @if($subject->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $assignedClassrooms = $subject->classroomSubjects
                                        ->filter(fn($assignment) => $assignment->classroom);
                                @endphp
                                @if($assignedClassrooms->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($assignedClassrooms->take(3) as $assignment)
                                            <span class="badge bg-primary">
                                                {{ $assignment->classroom->name }}
                                                @if($assignment->stream)
                                                    <span class="fw-normal small text-white-50">• {{ $assignment->stream->name }}</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                    @if($assignedClassrooms->count() > 3)
                                        <small class="text-muted d-block mt-1">
                                            +{{ $assignedClassrooms->count() - 3 }} more
                                        </small>
                                    @endif
                                @else
                                    <span class="text-muted">Not Assigned</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('academics.subjects.show', $subject) }}" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('academics.subjects.edit', $subject) }}" class="btn btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('academics.subjects.destroy', $subject) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
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

            <div class="mt-3">
                {{ $subjects->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Generate CBC/CBE Subjects Modal -->
<div class="modal fade" id="generateCBCModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('academics.subjects.generate-cbc') }}">
                @csrf
                <div class="modal-header">
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
                        <small class="form-text text-muted">Select the grade level to generate subjects for</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="assign_to_classrooms" id="assign_to_classrooms" value="1" class="form-check-input">
                            <label class="form-check-label" for="assign_to_classrooms">
                                Assign to Classrooms
                            </label>
                        </div>
                        <small class="form-text text-muted">Automatically assign generated subjects to selected classrooms</small>
                    </div>

                    <div class="mb-3" id="classrooms-select" style="display: none;">
                        <label class="form-label">Select Classrooms</label>
                        <select name="classroom_ids[]" class="form-select" multiple size="5">
                            @foreach(\App\Models\Academics\Classroom::orderBy('name')->get() as $classroom)
                                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple classrooms</small>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> This will create all CBC/CBE subjects for the selected level. 
                        For Junior High (Grade 7-9), optional subjects will be marked as optional and mandatory subjects as mandatory.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-magic"></i> Generate Subjects
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('assign_to_classrooms').addEventListener('change', function() {
        document.getElementById('classrooms-select').style.display = this.checked ? 'block' : 'none';
    });
</script>
@endpush
@endsection

@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Subject Details</h1>
        <div>
            <a href="{{ route('academics.subjects.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('academics.subjects.edit', $subject) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-book"></i> Subject Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Code:</strong> {{ $subject->code }}
                        </div>
                        <div class="col-md-6">
                            <strong>Name:</strong> {{ $subject->name }}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Group:</strong>
                            @if($subject->group)
                                <span class="badge bg-info">{{ $subject->group->name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <strong>Level:</strong>
                            @if($subject->level)
                                <span class="badge bg-secondary">{{ $subject->level }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Learning Area:</strong> {{ $subject->learning_area ?? '—' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Type:</strong>
                            @if($subject->is_optional)
                                <span class="badge bg-warning">Optional</span>
                            @else
                                <span class="badge bg-success">Mandatory</span>
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Status:</strong>
                            @if($subject->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Classroom Assignments -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-building"></i> Classroom Assignments</h5>
                </div>
                <div class="card-body">
                    @if($subject->classroomSubjects->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Classroom</th>
                                        <th>Stream</th>
                                        <th>Teacher</th>
                                        <th>Academic Year</th>
                                        <th>Term</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subject->classroomSubjects as $assignment)
                                    <tr>
                                        <td>{{ $assignment->classroom->name }}</td>
                                        <td>{{ $assignment->stream->name ?? '—' }}</td>
                                        <td>{{ $assignment->teacher->full_name ?? '—' }}</td>
                                        <td>{{ $assignment->academicYear->year ?? '—' }}</td>
                                        <td>{{ $assignment->term->name ?? '—' }}</td>
                                        <td>
                                            @if($assignment->is_compulsory)
                                                <span class="badge bg-success">Compulsory</span>
                                            @else
                                                <span class="badge bg-warning">Optional</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No classroom assignments yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Statistics -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Classrooms:</strong>
                        <span class="badge bg-primary">{{ $subject->classrooms->count() }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>Teachers:</strong>
                        <span class="badge bg-secondary">{{ $subject->teachers->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


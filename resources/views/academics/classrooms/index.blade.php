@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Classroom Management</h2>
            <small class="text-muted">Manage classes, streams, and student assignments</small>
        </div>
        <div class="d-flex gap-2">
            @if(Route::has('students.bulk.assign-streams'))
                <a href="{{ route('students.bulk.assign-streams') }}" class="btn btn-outline-primary">
                    <i class="bi bi-people"></i> Bulk Assign Students to Streams
                </a>
            @endif
            <a href="{{ route('academics.classrooms.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Classroom
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Total Classes</h6>
                            <h3 class="mb-0">{{ $classrooms->count() }}</h3>
                        </div>
                        <i class="bi bi-building fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Total Students</h6>
                            <h3 class="mb-0">{{ $classrooms->sum(fn($c) => $c->students->count()) }}</h3>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Total Streams</h6>
                            <h3 class="mb-0">{{ $classrooms->sum(fn($c) => $c->streams->count()) }}</h3>
                        </div>
                        <i class="bi bi-diagram-3 fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-2 text-white-50">Mapped Classes</h6>
                            <h3 class="mb-0">{{ $classrooms->filter(fn($c) => $c->nextClass || $c->is_alumni)->count() }}</h3>
                        </div>
                        <i class="bi bi-link-45deg fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Classrooms</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>Class Name</th>
                <th>Type</th>
                <th>Next Class</th>
                <th>Students</th>
                <th>Streams</th>
                <th>Teachers</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($classrooms as $classroom)
            <tr>
                <td>{{ $classroom->name }}</td>
                
                <!-- Type -->
                <td>
                    @if($classroom->is_beginner)
                        <span class="badge bg-info">Beginner</span>
                    @endif
                    @if($classroom->is_alumni)
                        <span class="badge bg-warning">Alumni</span>
                    @endif
                    @if(!$classroom->is_beginner && !$classroom->is_alumni)
                        <span class="text-muted">-</span>
                    @endif
                </td>
                
                <!-- Next Class -->
                <td>
                    @if($classroom->is_alumni)
                        <span class="text-muted">Graduation</span>
                    @elseif($classroom->nextClass)
                        <span class="text-success">
                            <i class="bi bi-arrow-right"></i> {{ $classroom->nextClass->name }}
                        </span>
                        @if($classroom->previousClasses->count() > 0)
                            <br><small class="text-muted">
                                <i class="bi bi-arrow-left"></i> From: {{ $classroom->previousClasses->pluck('name')->join(', ') }}
                            </small>
                        @endif
                    @else
                        <span class="text-danger">Not Mapped</span>
                    @endif
                </td>
                
                <!-- Students Count -->
                <td>
                    <span class="badge bg-primary">{{ $classroom->students->count() }}</span>
                    @if($classroom->students->count() > 0)
                        <br><small class="text-muted">
                            <a href="{{ route('students.index', ['classroom_id' => $classroom->id]) }}" class="text-decoration-none">
                                View Students
                            </a>
                        </small>
                    @endif
                </td>
                
                <!-- Display Streams -->
                <td>
                    @if($classroom->streams->count())
                        @foreach($classroom->streams as $stream)
                            <span class="badge bg-info">{{ $stream->name }}</span>
                        @endforeach
                    @else
                        <span class="text-muted">No Stream Assigned</span>
                    @endif
                </td>

                <!-- Display Teachers -->
                <td>
                    @php
                        $allTeachers = $classroom->allTeachers();
                    @endphp
                    @if($allTeachers->count())
                        @foreach($allTeachers as $teacher)
                            <span class="badge bg-success">
                                @if($teacher->staff)
                                    {{ $teacher->staff->first_name }} {{ $teacher->staff->last_name }}
                                @else
                                    {{ $teacher->name }}
                                @endif
                            </span>
                        @endforeach
                    @else
                        <span class="text-muted">Not Assigned</span>
                    @endif
                </td>

                <td>
                    <div class="btn-group" role="group">
                        <a href="{{ route('academics.classrooms.edit', $classroom->id) }}" class="btn btn-sm btn-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @if($classroom->students->count() > 0 && ($classroom->nextClass || $classroom->is_alumni))
                            <a href="{{ route('academics.promotions.show', $classroom) }}" class="btn btn-sm btn-success" title="Promote Students">
                                <i class="bi bi-arrow-up-circle"></i>
                            </a>
                        @endif
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteClassroom({{ $classroom->id }})" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <form id="delete-form-{{ $classroom->id }}" action="{{ route('academics.classrooms.destroy', $classroom->id) }}" method="POST" style="display:none;">
                        @csrf
                        @method('DELETE')
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
            </div>
        </div>
    </div>
</div>

<script>
function deleteClassroom(id) {
    if (confirm('Are you sure you want to delete this classroom? This action cannot be undone.')) {
        document.getElementById('delete-form-' + id).submit();
    }
}
</script>
@endsection

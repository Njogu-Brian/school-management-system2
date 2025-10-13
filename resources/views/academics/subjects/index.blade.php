@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Subjects</h1>
    <a href="{{ route('academics.subjects.create') }}" class="btn btn-primary mb-3">Add Subject</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered table-striped align-middle">
        <thead class="table-light">
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Group</th>
                <th>Learning Area</th>
                <th>Classrooms</th>
                <th>Teachers</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjects as $subject)
            <tr>
                <td>{{ $subject->code }}</td>
                <td>{{ $subject->name }}</td>
                <td>{{ optional($subject->group)->name ?? '—' }}</td>
                <td>{{ $subject->learning_area ?? '—' }}</td>

                <!-- Classrooms -->
                <td>
                    @if(optional($subject->classrooms)->isEmpty())
                        <span class="text-muted">Not Assigned</span>
                    @else
                        @foreach($subject->classrooms as $classroom)
                            <span class="badge bg-info text-dark">{{ $classroom->name }}</span>
                        @endforeach
                    @endif
                </td>

                <!-- Teachers -->
                <td>
                    @if(optional($subject->teachers)->isEmpty())
                        <span class="text-muted">Not Assigned</span>
                    @else
                        @foreach($subject->teachers as $teacher)
                            <span class="badge bg-secondary">{{ $teacher->name }}</span>
                        @endforeach
                    @endif
                </td>

                <td>
                    <a href="{{ route('academics.subjects.edit', $subject) }}" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <form action="{{ route('academics.subjects.destroy', $subject) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete subject?')">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-3">
        {{ $subjects->links() }}
    </div>
</div>
@endsection

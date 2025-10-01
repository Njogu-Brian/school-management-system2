@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-3">Exams</h1>

    <a href="{{ route('academics.exams.create') }}" class="btn btn-primary mb-3">
        <i class="bi bi-plus"></i> Create Exam
    </a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Year / Term</th>
                    <th>Classroom</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($exams as $exam)
                    <tr>
                        <td>{{ $exam->name }}</td>
                        <td>{{ strtoupper($exam->type) }}</td>
                        <td>{{ $exam->academicYear->year }} / {{ $exam->term->name }}</td>
                        <td>
    @foreach($exam->classrooms as $classroom)
        <span class="badge bg-primary">{{ $classroom->name }}</span>
    @endforeach
</td>

<td>
    @foreach($exam->subjects as $subject)
        <span class="badge bg-success">{{ $subject->name }}</span>
    @endforeach
</td>

                        <td><span class="badge bg-secondary">{{ ucfirst($exam->status) }}</span></td>
                        <td>
                            <a href="{{ route('academics.exams.edit', $exam) }}" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('academics.exams.destroy', $exam) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete exam?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No exams available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $exams->links() }}
</div>
@endsection

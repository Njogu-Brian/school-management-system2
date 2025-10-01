@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Exam Grades</h1>

    <a href="{{ route('academics.exam-grades.create') }}" class="btn btn-primary mb-3">
        <i class="bi bi-plus"></i> Add Grade
    </a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Exam Type</th>
                    <th>Grade</th>
                    <th>Range</th>
                    <th>Point</th>
                    <th>Description</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($grades as $grade)
                    <tr>
                        <td>{{ $grade->exam_type }}</td>
                        <td>{{ $grade->grade_name }}</td>
                        <td>{{ $grade->percent_from }} - {{ $grade->percent_upto }}</td>
                        <td>{{ $grade->grade_point }}</td>
                        <td>{{ $grade->description }}</td>
                        <td>
                            <a href="{{ route('academics.exam-grades.edit',$grade) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('academics.exam-grades.destroy',$grade) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete grade?')"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No grades found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

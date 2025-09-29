@extends('layouts.app')
@section('content')
<div class="container">
    <h1>Exam Grades</h1>
    <a href="{{ route('academics.exam-grades.create') }}" class="btn btn-primary mb-3">Add Grade</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Exam Type</th>
                <th>Grade Name</th>
                <th>Percent Range</th>
                <th>Grade Point</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grades as $grade)
            <tr>
                <td>{{ $grade->exam_type }}</td>
                <td>{{ $grade->grade_name }}</td>
                <td>{{ $grade->percent_from }} - {{ $grade->percent_upto }}</td>
                <td>{{ $grade->grade_point }}</td>
                <td>{{ $grade->description }}</td>
                <td>
                    <a href="{{ route('academics.exam-grades.edit',$grade) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('academics.exam-grades.destroy',$grade) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this grade?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

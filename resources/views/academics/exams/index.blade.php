@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Exams</h1>
    <a href="{{ route('exams.create') }}" class="btn btn-primary mb-3">Add Exam</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Title</th>
                <th>Classroom</th>
                <th>Term</th>
                <th>Year</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($exams as $exam)
            <tr>
                <td>{{ $exam->title }}</td>
                <td>{{ optional($exam->classroom)->name }}</td>
                <td>{{ optional($exam->term)->name }}</td>
                <td>{{ optional($exam->year)->year }}</td>
                <td>{{ $exam->date }}</td>
                <td>
                    <a href="{{ route('exams.edit',$exam) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('exams.destroy',$exam) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete exam?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $exams->links() }}
</div>
@endsection

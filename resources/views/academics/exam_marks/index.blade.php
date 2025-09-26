@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Exam Marks</h1>
    <a href="{{ route('exam-marks.create') }}" class="btn btn-primary mb-3">Add Marks</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Exam</th>
                <th>Student</th>
                <th>Subject</th>
                <th>Marks</th>
                <th>Grade</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($marks as $mark)
            <tr>
                <td>{{ optional($mark->exam)->title }}</td>
                <td>{{ optional($mark->student)->full_name }}</td>
                <td>{{ optional($mark->subject)->name }}</td>
                <td>{{ $mark->marks }}</td>
                <td>{{ $mark->grade }}</td>
                <td>
                    <a href="{{ route('exam-marks.edit',$mark) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('exam-marks.destroy',$mark) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete marks?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $marks->links() }}
</div>
@endsection

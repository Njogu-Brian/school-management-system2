@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Student Management</h1>

    <a href="{{ route('students.create') }}" class="btn btn-primary mb-3">Add New Student</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
    <thead>
        <tr>
            <th>Admission No</th>
            <th>Name</th>
            <th>Class</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($students as $student)
            <tr>
                <td>{{ $student->admission_number }}</td>
                <td>{{ $student->name }}</td>
                <td>{{ $student->class }}</td>
                <td>
                    @if ($student->archive)
                        <!-- Restore button if student is archived -->
                        <form action="{{ route('students.restore', $student->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-success">Restore</button>
                        </form>
                    @else
                        <!-- Edit button -->
                        <a href="{{ route('students.edit', $student->id) }}" class="btn btn-primary">Edit</a>

                        <!-- Archive button -->
                        <form action="{{ route('students.archive', $student->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-warning">Archive</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Student Management</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <a href="{{ route('students.create') }}" class="btn btn-success mb-3">Add New Student</a>
    <a href="{{ route('students.bulk') }}" class="btn btn-info mb-3">Bulk Upload</a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Admission</th>
                <th>Name</th>
                <th>Class</th>
                <th>Stream</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $student)
                <tr>
                    <td>{{ $student->admission_number }}</td>
                    <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                    <td>{{ $student->classroom->name ?? 'N/A' }}</td>
                    <td>{{ $student->stream->name ?? 'N/A' }}</td>
                    <td>
                        <a href="{{ route('students.edit', $student->id) }}" class="btn btn-sm btn-primary">Edit</a>
                        @if ($student->archive)
                            <form action="{{ route('students.restore', $student->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success">Restore</button>
                            </form>
                        @else
                            <form action="{{ route('students.archive', $student->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-warning">Archive</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

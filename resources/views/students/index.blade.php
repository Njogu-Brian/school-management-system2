@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Student Management</h1>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-between mb-3">
        <div>
            <a href="{{ route('students.create') }}" class="btn btn-success">Add New Student</a>
            <a href="{{ route('students.bulk') }}" class="btn btn-info">Bulk Upload</a>
        </div>
        <div>
            @if(request()->has('showArchived'))
                <a href="{{ route('students.index') }}" class="btn btn-secondary">Show Active</a>
            @else
                <a href="{{ route('students.index', ['showArchived' => 1]) }}" class="btn btn-secondary">Show Archived</a>
            @endif
        </div>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Admission</th>
                <th>Name</th>
                <th>Class</th>
                <th>Stream</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $student)
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
                                <button type="submit" class="btn btn-sm btn-success">Restore</button>
                            </form>
                        @else
                            <form action="{{ route('students.archive', $student->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning">Archive</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No students found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

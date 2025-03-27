@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Student Management</h1>

    <a href="{{ route('students.create') }}" class="btn btn-primary mb-3">Add New Student</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Search Filters -->
    <form method="GET" action="{{ route('students.index') }}" class="mb-3">
        <div class="row">
            <div class="col">
                <input type="text" name="name" class="form-control" placeholder="Search by Name" value="{{ request('name') }}">
            </div>
            <div class="col">
                <input type="text" name="admission_number" class="form-control" placeholder="Search by Admission Number" value="{{ request('admission_number') }}">
            </div>
            <div class="col">
                <select name="classroom_id" class="form-control">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ request('classroom_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ route('students.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Admission No</th>
                <th>Name</th>
                <th>Class</th>
                <th>Stream</th>
                <th>Category</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $student)
                <tr>
                    <td>{{ $student->admission_number }}</td>
                    <td>{{ $student->name }}</td>
                    <td>{{ $student->classroom->name ?? 'N/A' }}</td>
                    <td>{{ $student->stream->name ?? 'N/A' }}</td>
                    <td>{{ $student->category->name ?? 'N/A' }}</td>
                    <td>
                        @if ($student->archive)
                            <form action="{{ route('students.restore', $student->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success">Restore</button>
                            </form>
                        @else
                            <a href="{{ route('students.edit', $student->id) }}" class="btn btn-primary">Edit</a>
                            <form action="{{ route('students.archive', $student->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-warning">Archive</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No students found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

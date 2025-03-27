@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Student Management</h1>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('students.index') }}" class="mb-3">
        <div class="row">
            <div class="col-md-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" value="{{ request('name') }}">
            </div>
            <div class="col-md-3">
                <label>Admission Number</label>
                <input type="text" name="admission_number" class="form-control" value="{{ request('admission_number') }}">
            </div>
            <div class="col-md-3">
                <label>Class</label>
                <select name="classroom_id" class="form-control">
                    <option value="">All Classes</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" {{ request('classroom_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mt-4">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('students.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </div>
    </form>

    {{-- Add New Student Button --}}
    <a href="{{ route('students.create') }}" class="btn btn-success mb-3">Add New Student</a>

    {{-- Student Table --}}
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Admission Number</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Last Name</th>
                <th>Class</th>
                <th>Stream</th>
                <th>Category</th>
                <th>Parent</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $student)
                <tr>
                    <td>{{ $student->admission_number }}</td>
                    <td>{{ $student->first_name }}</td>
                    <td>{{ $student->middle_name }}</td>
                    <td>{{ $student->last_name }}</td>
                    <td>{{ $student->classroom->name ?? 'N/A' }}</td>
                    <td>{{ $student->stream->name ?? 'N/A' }}</td>
                    <td>{{ $student->category->name ?? 'N/A' }}</td>
                    <td>{{ optional($student->parent)->father_name ?? optional($student->parent)->mother_name ?? 'N/A' }}</td>
                    <td>
                        @if ($student->archive)
                            <!-- Restore Button -->
                            <form action="{{ route('students.restore', $student->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">Restore</button>
                            </form>
                        @else
                            <!-- Edit and Archive Buttons -->
                            <a href="{{ route('students.edit', $student->id) }}" class="btn btn-primary btn-sm">Edit</a>
                            <form action="{{ route('students.archive', $student->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm">Archive</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No students found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

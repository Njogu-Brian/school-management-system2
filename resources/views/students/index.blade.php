@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Student Management</h1>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('students.index') }}" class="mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <label for="name">Name</label>
                <input type="text" name="name" class="form-control" id="name" value="{{ request('name') }}">
            </div>
            <div class="col-md-3">
                <label for="admission_number">Admission Number</label>
                <input type="text" name="admission_number" class="form-control" id="admission_number" value="{{ request('admission_number') }}">
            </div>
            <div class="col-md-3">
                <label for="classroom_id">Class</label>
                <select name="classroom_id" class="form-control" id="classroom_id">
                    <option value="">All Classes</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}" {{ request('classroom_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                <div>
@if(can_access('students', 'manage_students', 'view'))
                    <button type="submit" class="btn btn-primary">Filter</button>
@endif
                    <a href="{{ route('students.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </div>
    </form>

    {{-- Add New Student and Bulk Upload Buttons --}}
    <div class="mb-3 d-flex gap-2">
        <a href="{{ route('students.create') }}" class="btn btn-success">Add New Student</a>
        <a href="{{ route('students.bulk') }}" class="btn btn-info">Bulk Upload</a>
    </div>
    {{-- Archive Filter --}}
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="showArchived" name="showArchived" 
               {{ request('showArchived') ? 'checked' : '' }}
               onchange="location.href='{{ route('students.index', ['showArchived' => request('showArchived') ? null : '1']) }}'">
        <label class="form-check-label" for="showArchived">Show Archived Students</label>
    </div>


    {{-- Student Table --}}
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
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
                        <td>{{ $student->middle_name ?? 'N/A' }}</td>
                        <td>{{ $student->last_name }}</td>
                        <td>{{ $student->classroom->name ?? 'N/A' }}</td>
                        <td>{{ $student->stream->name ?? 'N/A' }}</td>
                        <td>{{ $student->category->name ?? 'N/A' }}</td>
                        <td>{{ optional($student->parent)->father_name ?? optional($student->parent)->mother_name ?? 'N/A' }}</td>
                        <td>
                            @if ($student->archive)
                                <form action="{{ route('students.restore', $student->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">Restore</button>
                                </form>
                            @else
                                <a href="{{ route('students.edit', $student->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                <form action="{{ route('students.archive', $student->id) }}" method="POST" class="d-inline">
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
</div>
@endsection

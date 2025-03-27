@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Classroom Management</h1>
    <a href="{{ route('classrooms.create') }}" class="btn btn-primary mb-3">Add New Classroom</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Class Name</th>
                <th>Teacher</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($classrooms as $classroom)
            <tr>
                <td>{{ $classroom->name }}</td>
                <td>{{ $classroom->teacher ? $classroom->teacher->first_name . ' ' . $classroom->teacher->last_name : 'Not Assigned' }}</td>
                <td>
                    <a href="{{ route('classrooms.edit', $classroom->id) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form action="{{ route('classrooms.destroy', $classroom->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

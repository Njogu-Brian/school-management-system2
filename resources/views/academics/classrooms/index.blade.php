@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Classroom Management</h1>
    <a href="{{ route('academics.classrooms.create') }}" class="btn btn-primary mb-3">Add New Classroom</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Class Name</th>
                <th>Type</th>
                <th>Next Class</th>
                <th>Streams</th>
                <th>Teacher</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($classrooms as $classroom)
            <tr>
                <td>{{ $classroom->name }}</td>
                
                <!-- Type -->
                <td>
                    @if($classroom->is_beginner)
                        <span class="badge bg-info">Beginner</span>
                    @endif
                    @if($classroom->is_alumni)
                        <span class="badge bg-warning">Alumni</span>
                    @endif
                    @if(!$classroom->is_beginner && !$classroom->is_alumni)
                        <span class="text-muted">-</span>
                    @endif
                </td>
                
                <!-- Next Class -->
                <td>
                    @if($classroom->is_alumni)
                        <span class="text-muted">Graduation</span>
                    @elseif($classroom->nextClass)
                        <span class="text-success">{{ $classroom->nextClass->name }}</span>
                    @else
                        <span class="text-danger">Not Mapped</span>
                    @endif
                </td>
                
                <!-- Display Streams -->
                <td>
                    @if($classroom->streams->count())
                        @foreach($classroom->streams as $stream)
                            <span class="badge bg-info">{{ $stream->name }}</span>
                        @endforeach
                    @else
                        <span class="text-muted">No Stream Assigned</span>
                    @endif
                </td>

                <!-- Display Teacher -->
                <td>
                    @if($classroom->teachers->count())
                        @foreach($classroom->teachers as $teacher)
                            @if($teacher->staff)
                                {{ $teacher->staff->first_name }} {{ $teacher->staff->last_name }}<br>
                            @else
                                {{ $teacher->name }} <small class="text-muted">(No staff record)</small><br>
                            @endif
                        @endforeach
                    @else
                        Not Assigned
                    @endif
                </td>

                <td>
                    <a href="{{ route('academics.classrooms.edit', $classroom->id) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form action="{{ route('academics.classrooms.destroy', $classroom->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

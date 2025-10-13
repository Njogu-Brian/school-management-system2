@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Stream Management</h1>
    <a href="{{ route('academics.streams.create') }}" class="btn btn-primary mb-3">Add New Stream</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Stream Name</th>
                <th>Classrooms</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($streams as $stream)
                <tr>
                    <td>{{ $stream->name }}</td>
                    <td>
                        @if(optional($stream->classrooms)->isEmpty())
                            <span class="text-muted">Not Assigned</span>
                        @else
                            {{ $stream->classrooms->pluck('name')->implode(', ') }}
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('academics.streams.edit', $stream->id) }}" class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form action="{{ route('academics.streams.destroy', $stream->id) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this stream?')">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@extends('layouts.app')

@section('content')
<h1>Drop-Off Points Management</h1>

<a href="{{ route('dropoffpoints.create') }}" class="btn btn-success mb-3">Add New Drop-Off Point</a>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Drop-Off Point Name</th>
            <th>Route</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($dropOffPoints as $point)
            <tr>
                <td>{{ $point->name }}</td>
                <td>{{ $point->route->name ?? 'No Route' }}</td>
                <td>
                    <a href="{{ route('dropoffpoints.edit', $point->id) }}" class="btn btn-primary btn-sm">Edit</a>
                    <form action="{{ route('dropoffpoints.destroy', $point->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this drop-off point?')">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center">No Drop-Off Points Found</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection

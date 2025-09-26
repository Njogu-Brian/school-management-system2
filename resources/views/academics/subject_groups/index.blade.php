@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Subject Groups</h1>
    <a href="{{ route('subject-groups.create') }}" class="btn btn-primary mb-3">Add Group</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groups as $group)
            <tr>
                <td>{{ $group->name }}</td>
                <td>{{ $group->description }}</td>
                <td>
                    <a href="{{ route('subject-groups.edit',$group) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('subject-groups.destroy',$group) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete group?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $groups->links() }}
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Subjects</h1>
    <a href="{{ route('subjects.create') }}" class="btn btn-primary mb-3">Add Subject</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Group</th>
                <th>Learning Area</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjects as $subject)
            <tr>
                <td>{{ $subject->code }}</td>
                <td>{{ $subject->name }}</td>
                <td>{{ optional($subject->group)->name }}</td>
                <td>{{ $subject->learning_area }}</td>
                <td>
                    <a href="{{ route('subjects.edit',$subject) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('subjects.destroy',$subject) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete subject?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $subjects->links() }}
</div>
@endsection

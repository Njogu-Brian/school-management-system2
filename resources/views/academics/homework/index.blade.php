@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Homework</h1>
    <a href="{{ route('academics.homework.create') }}" class="btn btn-primary mb-3">Assign Homework</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Class</th>
                <th>Subject</th>
                <th>Title</th>
                <th>Due Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($homeworks as $task)
            <tr>
                <td>{{ $task->classroom?->name ?? 'N/A' }}</td>
                <td>{{ $task->subject->name }}</td>
                <td>{{ $task->title }}</td>
                <td>{{ $task->due_date->format('d M Y') }}</td>
                <td>
                    <a href="{{ route('academics.homework.show',$task) }}" class="btn btn-sm btn-info">View</a>
                    <a href="{{ route('academics.homework.edit',$task) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('academics.homework.destroy',$task) }}" method="POST">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete homework?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $homeworks->links() }}
</div>
@endsection

@extends('layouts.app')
@section('content')
<div class="container">
    <h1>Homework</h1>
    <a href="{{ route('academics.homework.create') }}" class="btn btn-primary mb-3">Assign Homework</a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Title</th>
                <th>Class</th>
                <th>Subject</th>
                <th>Due</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($homeworks as $task)
            <tr>
                <td>{{ $task->title }}</td>
                <td>{{ $task->classroom?->name ?? 'All' }}</td>
                <td>{{ $task->subject?->name ?? 'N/A' }}</td>
                <td>{{ $task->due_date->format('d M Y') }}</td>
                <td>
                    <a href="{{ route('academics.homework.show',$task) }}" class="btn btn-sm btn-info">View</a>
                    <form action="{{ route('academics.homework.destroy',$task) }}" method="POST" style="display:inline;">
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

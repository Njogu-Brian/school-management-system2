@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Digital Diaries</h1>
    <a href="{{ route('academics.diaries.create') }}" class="btn btn-primary mb-3">Start Diary</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Classroom</th>
                <th>Teacher</th>
                <th>Week</th>
                <th>Last Activity</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($diaries as $diary)
            <tr>
                <td>{{ $diary->classroom?->name ?? 'N/A' }}</td>
                <td>{{ $diary->teacher?->first_name }} {{ $diary->teacher?->last_name }}</td>
                <td>{{ $diary->week_start->format('d M Y') }}</td>
                <td>{{ optional($diary->messages->last())->created_at?->diffForHumans() ?? 'No messages' }}</td>
                <td>
                    <a href="{{ route('academics.diaries.show', $diary) }}" class="btn btn-sm btn-info">Open Chat</a>
                    <a href="{{ route('academics.diaries.edit',$diary) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('academics.diaries.destroy',$diary) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete diary?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $diaries->links() }}
</div>
@endsection

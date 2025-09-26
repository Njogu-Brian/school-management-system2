@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Digital Diaries</h1>
    <a href="{{ route('diaries.create') }}" class="btn btn-primary mb-3">Add Diary Entry</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Classroom</th>
                <th>Week</th>
                <th>Activities</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($diaries as $diary)
            <tr>
                <td>{{ $diary->classroom->name }}</td>
                <td>{{ $diary->week }}</td>
                <td>{{ Str::limit($diary->activities, 50) }}</td>
                <td>
                    <a href="{{ route('diaries.show',$diary) }}" class="btn btn-sm btn-info">View</a>
                    <a href="{{ route('diaries.edit',$diary) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('diaries.destroy',$diary) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete diary entry?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $diaries->links() }}
</div>
@endsection

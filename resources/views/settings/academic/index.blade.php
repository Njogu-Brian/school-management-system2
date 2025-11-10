@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4">Academic Years & Terms</h4>

    @if(session('success')) 
        <div class="alert alert-success">{{ session('success') }}</div> 
    @endif

    <div class="mb-3">
        <a href="{{ route('settings.academic.year.create') }}" class="btn btn-success">➕ Add Academic Year</a>
        <a href="{{ route('settings.academic.term.create') }}" class="btn btn-primary">➕ Add Term</a>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Year</th>
                <th>Active</th>
                <th>Terms</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($years as $year)
            <tr>
                <td>{{ $year->year }}</td>
                <td>{{ $year->is_active ? '✅' : '❌' }}</td>
                <td>
                    <ul>
                        @foreach($year->terms as $term)
                        <li>
                            {{ $term->name }}
                            {!! $term->is_current ? '<span class="badge bg-success">Current</span>' : '' !!}
                            <a href="{{ route('settings.academic.term.edit', $term) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('settings.academic.term.destroy', $term) }}" method="POST" style="display:inline;">
                                @csrf @method('DELETE')
                                <button onclick="return confirm('Delete term?')" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </li>
                        @endforeach
                    </ul>
                </td>
                <td>
                    <a href="{{ route('settings.academic.year.edit', $year) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form action="{{ route('settings.academic.year.destroy', $year) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button onclick="return confirm('Delete year?')" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

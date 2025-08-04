@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Academic Years</h4>
    <a href="{{ route('academic-years.create') }}" class="btn btn-primary mb-3">Add Year</a>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Year</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($years as $year)
            <tr>
                <td>{{ $year->year }}</td>
                <td>{{ $year->is_active ? '✅' : '❌' }}</td>
                <td>
                    <form action="{{ route('academic-years.destroy', $year) }}" method="POST">
                        @csrf @method('DELETE')
                        <button onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

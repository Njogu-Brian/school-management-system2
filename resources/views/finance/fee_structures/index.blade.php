@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Fee Structures</h3>
    <a href="{{ route('fee-structures.create') }}" class="btn btn-success mb-3">Create Fee Structure</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Classroom</th>
                <th>Year</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($structures as $structure)
            <tr>
                <td>{{ $structure->classroom->name }}</td>
                <td>{{ $structure->year }}</td>
                <td>
                    <a href="{{ route('fee-structures.show', $structure) }}" class="btn btn-sm btn-info">View</a>
                    <a href="{{ route('fee-structures.edit', $structure) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('fee-structures.destroy', $structure) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

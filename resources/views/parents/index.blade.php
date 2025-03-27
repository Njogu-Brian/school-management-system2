@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Parent Management</h1>
    <a href="{{ route('parent-info.create') }}" class="btn btn-primary mb-3">Add New Parent</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Father Name</th>
                <th>Father Phone</th>
                <th>Mother Name</th>
                <th>Mother Phone</th>
                <th>Guardian Name</th>
                <th>Guardian Phone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($parents as $parent)
                <tr>
                    <td>{{ $parent->father_name }}</td>
                    <td>{{ $parent->father_phone }}</td>
                    <td>{{ $parent->mother_name }}</td>
                    <td>{{ $parent->mother_phone }}</td>
                    <td>{{ $parent->guardian_name }}</td>
                    <td>{{ $parent->guardian_phone }}</td>
                    <td>
                        <a href="{{ route('parent-info.edit', $parent->id) }}" class="btn btn-primary btn-sm">Edit</a>
                        <form action="{{ route('parent-info.destroy', $parent->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this parent?');">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

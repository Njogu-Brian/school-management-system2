@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Manage Staff</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-between mb-3">
        <a href="{{ route('staff.create') }}" class="btn btn-success">➕ Add New Staff</a>
        <a href="{{ route('staff.upload.form') }}" class="btn btn-info">⬆ Bulk Upload</a>
    </div>

    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>Staff ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Category</th>
                <th>Department</th>
                <th>Job Title</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($staff as $s)
                <tr>
                    <td>{{ $s->staff_id }}</td>
                    <td>{{ $s->first_name }} {{ $s->last_name }}</td>
                    <td>{{ $s->email }}</td>
                    <td>{{ $s->phone_number }}</td>
                    <td>{{ $s->category->name ?? '-' }}</td>
                    <td>{{ $s->department->name ?? '-' }}</td>
                    <td>{{ $s->jobTitle->name ?? '-' }}</td>
                    <td>
                        <span class="badge bg-{{ $s->status == 'active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($s->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('staff.edit', $s->id) }}" class="btn btn-sm btn-warning">✏ Edit</a>
                       @if($s->status === 'active')
                            <form action="{{ route('staff.archive', $s->id) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Archive this staff?')">🗄 Archive</button>
                            </form>
                        @else
                            <form action="{{ route('staff.restore', $s->id) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-success"
                                    onclick="return confirm('Restore this staff?')">♻ Restore</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No staff found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@extends('layouts.app')

@section('content')
<h1>Manage Staff</h1>

<a href="{{ route('staff.create') }}" class="btn btn-success mb-3">Add New Staff</a>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role(s)</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($staff as $member)
        <tr>
            <td>{{ $member->first_name }} {{ $member->last_name }}</td>
            <td>{{ $member->email }}</td>
            <td>
                @if ($member->user && $member->user->roles)
                    @foreach ($member->user->roles as $role)
                        {{ ucfirst($role->name) }}@if (!$loop->last), @endif
                    @endforeach
                @else
                    <span class="text-muted">No roles</span>
                @endif
            </td>
            <td>{{ ucfirst($member->status ?? 'active') }}</td>
            <td>
                <a href="{{ route('staff.edit', $member) }}" class="btn btn-sm btn-primary">Edit</a>

                @if($member->status === 'archived')
                    <form action="{{ route('staff.restore', $member->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <button class="btn btn-sm btn-success">Restore</button>
                    </form>
                @else
                    <form action="{{ route('staff.archive', $member->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <button class="btn btn-sm btn-warning">Archive</button>
                    </form>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection

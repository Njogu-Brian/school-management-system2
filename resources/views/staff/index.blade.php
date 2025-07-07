@extends('layouts.app')

@section('content')
<h1 class="mb-4">Manage Staff</h1>

<div class="d-flex justify-content-between align-items-center mb-3">
@if(can_access('staff', 'manage_staff', 'add'))
    <a href="{{ route('staff.create') }}" class="btn btn-success">Add New Staff</a>
@endif

    <div>
        <a href="{{ asset('templates/staff_upload_template_enhanced.xlsx') }}" class="btn btn-outline-secondary me-2">
            Download Template
        </a>
        <a href="{{ route('staff.upload.form') }}" class="btn btn-primary">
            Bulk Upload Staff
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('errors') && is_array(session('errors')))
    <div class="alert alert-danger">
        <strong>Some staff rows were skipped due to errors:</strong>
        <ul class="mb-0">
            @foreach(session('errors') as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<table class="table table-bordered">
    <thead>
        <tr>
            <th>Staff ID</th>    
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
            <td>{{ $member->staff_id }}</td>
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

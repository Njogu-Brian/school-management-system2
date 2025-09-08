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

        <div>
            <a href="{{ asset('templates/staff_upload_template.xlsx') }}" class="btn btn-outline-secondary me-2">
                ⬇ Download Template
            </a>
            <a href="{{ route('staff.upload.form') }}" class="btn btn-primary">
                📤 Bulk Upload Staff
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Staff ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Department</th>
                    <th>Job Title</th>
                    <th>Supervisor</th>
                    <th>Status</th>
                    <th>Photo</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $index => $member)
                    <tr>
                        <td>{{ $index+1 }}</td>
                        <td>{{ $member->staff_id }}</td>
                        <td>{{ $member->first_name }} {{ $member->last_name }}</td>
                        <td>{{ $member->email }}</td>
                        <td>{{ $member->phone_number }}</td>
                        <td>{{ $member->department ?? '-' }}</td>
                        <td>{{ $member->job_title ?? '-' }}</td>
                        <td>{{ $member->supervisor?->first_name }} {{ $member->supervisor?->last_name }}</td>
                        <td>
                            <span class="badge bg-{{ $member->status == 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($member->status) }}
                            </span>
                        </td>
                        <td>
                            @if($member->photo)
                                <img src="{{ asset('storage/'.$member->photo) }}" class="img-thumbnail" width="50">
                            @else
                                <span class="text-muted">No Photo</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('staff.edit',$member->id) }}" class="btn btn-sm btn-primary">✏ Edit</a>
                            @if($member->status == 'active')
                                <form action="{{ route('staff.archive',$member->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-warning">🗄 Archive</button>
                                </form>
                            @else
                                <form action="{{ route('staff.restore',$member->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success">♻ Restore</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted">No staff found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

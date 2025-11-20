@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-0">Staff Profile Change Requests</h1>
            <small class="text-muted">Review and approve staff profile change requests</small>
        </div>
        <div>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            @php
                $pendingCount = \App\Models\StaffProfileChange::where('status', 'pending')->count();
            @endphp
            @if($pendingCount > 0 && (!$status || $status === 'pending'))
                <form action="{{ route('hr.profile_requests.approve-all') }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to approve all {{ $pendingCount }} pending request(s)? This action cannot be undone.');">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-all"></i> Approve All ({{ $pendingCount }})
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('errors') && is_array(session('errors')))
        <div class="alert alert-warning alert-dismissible fade show">
            <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Some requests failed:</h6>
            <ul class="mb-0 small">
                @foreach(session('errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form class="mb-3 d-flex gap-2" method="get">
        <select name="status" class="form-select" style="max-width:240px">
            <option value="">All statuses</option>
            @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $val=>$label)
                <option value="{{ $val }}" @selected($status===$val)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Staff</th>
                        <th>Submitted By</th>
                        <th>Submitted At</th>
                        <th>Status</th>
                        <th>Fields</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($changes as $c)
                    <tr>
                        <td>{{ $c->id }}</td>
                        <td>{{ $c->staff?->full_name }} <br><small class="text-muted">{{ $c->staff?->staff_id }}</small></td>
                        <td>{{ $c->submitter?->name }}</td>
                        <td>{{ $c->created_at->format('d M Y, H:i') }}</td>
                        <td>
                            @if($c->status==='pending') <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($c->status==='approved') <span class="badge bg-success">Approved</span>
                            @else <span class="badge bg-danger">Rejected</span>
                            @endif
                        </td>
                        <td>
                            @foreach(array_keys($c->changes ?? []) as $f)
                                <span class="badge bg-secondary me-1">{{ $f }}</span>
                            @endforeach
                        </td>
                        <td>
                            <a href="{{ route('hr.profile_requests.show',$c->id) }}" class="btn btn-sm btn-outline-primary">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center p-4 text-muted">No requests found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($changes->hasPages())
            <div class="card-footer">{{ $changes->links() }}</div>
        @endif
    </div>
</div>
@endsection

@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Profile Changes</div>
                <h1 class="mb-1">Staff Profile Change Requests</h1>
                <p class="text-muted mb-0">Review and approve staff profile change requests.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                @php
                    $pendingCount = \App\Models\StaffProfileChange::where('status', 'pending')->count();
                @endphp
                @if($pendingCount > 0 && (!$status || $status === 'pending'))
                    <form action="{{ route('hr.profile_requests.approve-all') }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to approve all {{ $pendingCount }} pending request(s)? This action cannot be undone.');">
                        @csrf
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check-all"></i> Approve All ({{ $pendingCount }})
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @include('partials.alerts')

        <div class="settings-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Filters</h5>
                    <p class="text-muted mb-0 small">Narrow down requests by status.</p>
                </div>
                @if($changes->total())
                    <span class="input-chip">{{ $changes->total() }} total</span>
                @endif
            </div>
            <div class="card-body">
                <form class="row g-3 align-items-end" method="get">
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $val=>$label)
                                <option value="{{ $val }}" @selected($status===$val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-settings-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Change Requests</h5>
                    <p class="mb-0 text-muted small">Pending and reviewed profile updates.</p>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    @if($changes->total())
                        <span class="input-chip">{{ $changes->total() }} total</span>
                    @endif
                    <div class="pill-badge pill-secondary">Sorted newest first</div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Staff</th>
                                <th>Submitted By</th>
                                <th>Submitted At</th>
                                <th>Status</th>
                                <th>Fields</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($changes as $c)
                            <tr>
                                <td class="fw-semibold">{{ $c->id }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $c->staff?->full_name }}</div>
                                    <small class="text-muted">{{ $c->staff?->staff_id }}</small>
                                </td>
                                <td>{{ $c->submitter?->name }}</td>
                                <td>{{ $c->created_at->format('d M Y, H:i') }}</td>
                                <td>
                                    @if($c->status==='pending') <span class="pill-badge pill-warning">Pending</span>
                                    @elseif($c->status==='approved') <span class="pill-badge pill-success">Approved</span>
                                    @else <span class="pill-badge pill-danger">Rejected</span>
                                    @endif
                                </td>
                                <td>
                                    @foreach(array_keys($c->changes ?? []) as $f)
                                        <span class="pill-badge pill-secondary me-1">{{ $f }}</span>
                                    @endforeach
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('hr.profile_requests.show',$c->id) }}" class="btn btn-sm btn-ghost-strong">
                                        <i class="bi bi-box-arrow-up-right"></i> Open
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center p-4 text-muted">No requests found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($changes->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-muted small">Showing {{ $changes->firstItem() }} - {{ $changes->lastItem() }} of {{ $changes->total() }}</div>
                    {{ $changes->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

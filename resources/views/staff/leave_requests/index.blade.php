@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Staff</div>
                <h1 class="mb-1">Leave Requests</h1>
                <p class="text-muted mb-0">Manage staff leave requests and approvals.</p>
            </div>
            <a href="{{ route('staff.leave-requests.create') }}" class="btn btn-settings-primary">
                <i class="bi bi-plus-circle"></i> New Leave Request
            </a>
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

        <div class="settings-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Filters</h5>
                    <p class="text-muted small mb-0">Filter by status, staff, and dates.</p>
                </div>
                <span class="pill-badge pill-secondary">Live query</span>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                            <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                            <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                            <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Staff</label>
                        <select name="staff_id" class="form-select">
                            <option value="">All Staff</option>
                            @foreach($staff as $s)
                                <option value="{{ $s->id }}" @selected(request('staff_id') == $s->id)>{{ $s->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-settings-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Leave Requests</h5>
                    <p class="mb-0 text-muted small">Status, date range, and actions.</p>
                </div>
                @if($leaveRequests->total() ?? null)
                    <span class="input-chip">{{ $leaveRequests->total() }} total</span>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Staff</th>
                                <th>Leave Type</th>
                                <th>Date Range</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaveRequests as $request)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $request->staff->full_name }}</div>
                                        <small class="text-muted">{{ $request->staff->staff_id }}</small>
                                    </td>
                                    <td>
                                        <span class="pill-badge pill-info">{{ $request->leaveType->name }}</span>
                                    </td>
                                    <td>
                                        <div>{{ $request->start_date->format('d M Y') }}</div>
                                        <small class="text-muted">to {{ $request->end_date->format('d M Y') }}</small>
                                    </td>
                                    <td>
                                        <span class="pill-badge pill-primary">{{ $request->days_requested }} days</span>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'pending' => 'pill-warning',
                                                'approved' => 'pill-success',
                                                'rejected' => 'pill-danger',
                                                'cancelled' => 'pill-secondary'
                                            ];
                                        @endphp
                                        <span class="pill-badge {{ $statusColors[$request->status] ?? 'pill-secondary' }}">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $request->created_at->format('d M Y, H:i') }}</small>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('staff.leave-requests.show', $request->id) }}" class="btn btn-sm btn-ghost-strong" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if($request->status === 'pending')
                                                <button type="button" class="btn btn-sm btn-success" onclick="approveRequest({{ $request->id }})" title="Approve">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="rejectRequest({{ $request->id }})" title="Reject">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No leave requests found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($leaveRequests->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-muted">
                        Showing {{ $leaveRequests->firstItem() }}–{{ $leaveRequests->lastItem() }} of {{ $leaveRequests->total() }}
                    </div>
                    {{ $leaveRequests->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Admin Notes (Optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approveRequest(id) {
    document.getElementById('approveForm').action = `/staff/leave-requests/${id}/approve`;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function rejectRequest(id) {
    document.getElementById('rejectForm').action = `/staff/leave-requests/${id}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endsection


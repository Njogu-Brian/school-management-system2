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
                <h1 class="mb-1">Leave Types</h1>
                <p class="text-muted mb-0">Manage the leave catalogue available to staff.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('staff.leave-types.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> Add Leave Type
                </a>
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

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Leave Types</h5>
                    <p class="mb-0 text-muted small">Track paid/unpaid, approvals, and status.</p>
                </div>
                @php $leaveTypeCount = $leaveTypes instanceof \Illuminate\Contracts\Pagination\Paginator ? $leaveTypes->total() : $leaveTypes->count(); @endphp
                @if($leaveTypeCount)
                    <span class="input-chip">{{ $leaveTypeCount }} total</span>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Max Days</th>
                                <th>Paid</th>
                                <th>Approval</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaveTypes as $type)
                                <tr>
                                    <td class="fw-semibold">{{ $type->name }}</td>
                                    <td><span class="pill-badge pill-info">{{ $type->code }}</span></td>
                                    <td>{{ $type->max_days ?? 'Unlimited' }}</td>
                                    <td>
                                        <span class="pill-badge {{ $type->is_paid ? 'pill-success' : 'pill-secondary' }}">
                                            {{ $type->is_paid ? 'Paid' : 'Unpaid' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="pill-badge {{ $type->requires_approval ? 'pill-warning' : 'pill-info' }}">
                                            {{ $type->requires_approval ? 'Requires Approval' : 'Auto-approve' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="pill-badge {{ $type->is_active ? 'pill-success' : 'pill-secondary' }}">
                                            {{ $type->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('staff.leave-types.edit', $type->id) }}" class="btn btn-sm btn-ghost-strong" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-ghost-strong text-danger" onclick="deleteLeaveType({{ $type->id }})" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <form id="delete-form-{{ $type->id }}" action="{{ route('staff.leave-types.destroy', $type->id) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No leave types found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function deleteLeaveType(id) {
    if (confirm('Are you sure you want to delete this leave type? This action cannot be undone.')) {
        document.getElementById('delete-form-' + id).submit();
    }
}
</script>
@endsection


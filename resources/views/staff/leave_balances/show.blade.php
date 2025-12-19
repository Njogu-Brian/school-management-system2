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
                <h1 class="mb-1">Leave Balance - {{ $staff->full_name }}</h1>
                <p class="text-muted mb-0">View and manage leave balances.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('staff.leave-balances.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <a href="{{ route('staff.leave-balances.create', ['staff_id' => $staff->id]) }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> Add Balance
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">
                        Leave Balances
                        @if($currentYear)
                            <small class="text-muted">({{ $currentYear->year }})</small>
                        @endif
                    </h5>
                </div>
                @if($balances->count())
                    <span class="input-chip">{{ $balances->count() }} types</span>
                @endif
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-modern table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Leave Type</th>
                                <th>Entitlement</th>
                                <th>Used</th>
                                <th>Carried Forward</th>
                                <th>Remaining</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($balances as $balance)
                                <tr>
                                    <td class="fw-semibold">{{ $balance->leaveType->name }}</td>
                                    <td>{{ $balance->entitlement_days }} days</td>
                                    <td>
                                        <span class="pill-badge pill-warning">{{ $balance->used_days }} days</span>
                                    </td>
                                    <td>{{ $balance->carried_forward }} days</td>
                                    <td>
                                        <span class="pill-badge {{ $balance->remaining_days > 0 ? 'pill-success' : 'pill-danger' }}">
                                            {{ $balance->remaining_days }} days
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-ghost-strong" onclick="editBalance({{ $balance->id }}, {{ $balance->entitlement_days }}, {{ $balance->carried_forward }})">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No leave balances set for this staff member.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editBalanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editBalanceForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Leave Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Entitlement Days <span class="text-danger">*</span></label>
                        <input type="number" name="entitlement_days" class="form-control" id="entitlement_days" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Carried Forward Days</label>
                        <input type="number" name="carried_forward" class="form-control" id="carried_forward" min="0" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-settings-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editBalance(id, entitlement, carried) {
    document.getElementById('editBalanceForm').action = `/staff/leave-balances/${id}`;
    document.getElementById('entitlement_days').value = entitlement;
    document.getElementById('carried_forward').value = carried;
    new bootstrap.Modal(document.getElementById('editBalanceModal')).show();
}
</script>
@endsection


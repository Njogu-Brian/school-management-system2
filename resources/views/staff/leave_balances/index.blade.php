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
                <h1 class="mb-1">Leave Balances</h1>
                <p class="text-muted mb-0">View and manage staff leave balances.</p>
            </div>
            <a href="{{ route('staff.leave-balances.create') }}" class="btn btn-settings-primary">
                <i class="bi bi-plus-circle"></i> Set Leave Balance
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="settings-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Filters</h5>
                    <p class="text-muted small mb-0">Filter by staff.</p>
                </div>
                <span class="pill-badge pill-secondary">Live query</span>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Staff</label>
                        <select name="staff_id" class="form-select">
                            <option value="">All Staff</option>
                            @foreach($staff as $s)
                                <option value="{{ $s->id }}" @selected(request('staff_id') == $s->id)>{{ $s->full_name }}</option>
                            @endforeach
                        </select>
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
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul"></i> Leave Balances
                        @if($currentYear)
                            <small class="text-muted">({{ $currentYear->year }})</small>
                        @endif
                    </h5>
                </div>
                @if($balances->total() ?? null)
                    <span class="input-chip">{{ $balances->total() }} total</span>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Staff</th>
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
                                    <td>
                                        <div class="fw-semibold">{{ $balance->staff->full_name }}</div>
                                        <small class="text-muted">{{ $balance->staff->staff_id }}</small>
                                    </td>
                                    <td>
                                        <span class="pill-badge pill-info">{{ $balance->leaveType->name }}</span>
                                    </td>
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
                                        <a href="{{ route('staff.leave-balances.show', $balance->staff_id) }}" class="btn btn-sm btn-ghost-strong" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No leave balances found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($balances->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-muted">
                        Showing {{ $balances->firstItem() }}–{{ $balances->lastItem() }} of {{ $balances->total() }}
                    </div>
                    {{ $balances->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Staff Advances</div>
                <h1 class="mb-1">Staff Advances</h1>
                <p class="text-muted mb-0">Manage staff advance loans and repayments.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('hr.payroll.advances.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> New Advance
                </a>
                @if($advances->total())
                    <span class="pill-badge pill-secondary">{{ $advances->total() }} records</span>
                @endif
            </div>
        </div>

        @include('partials.alerts')

        {{-- Filters --}}
        <div class="settings-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Filters</h5>
                    <p class="text-muted small mb-0">Refine advances by staff and status.</p>
                </div>
                <div class="pill-badge pill-secondary">Live query</div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Staff</label>
                        <select name="staff_id" class="form-select">
                            <option value="">All Staff</option>
                            @foreach($staff as $s)
                                <option value="{{ $s->id }}" @selected(request('staff_id')==$s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" @selected(request('status')==='pending')>Pending</option>
                            <option value="approved" @selected(request('status')==='approved')>Approved</option>
                            <option value="active" @selected(request('status')==='active')>Active</option>
                            <option value="completed" @selected(request('status')==='completed')>Completed</option>
                            <option value="cancelled" @selected(request('status')==='cancelled')>Cancelled</option>
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

        {{-- Table --}}
        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Advances</h5>
                    <p class="mb-0 text-muted small">Loan balances, repayment methods, and status.</p>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    @if($advances->total())
                        <span class="input-chip">{{ $advances->total() }} total</span>
                    @endif
                    <span class="pill-badge pill-info">Clickable rows</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Staff</th>
                                <th>Amount</th>
                                <th>Repaid</th>
                                <th>Balance</th>
                                <th>Repayment Method</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($advances as $advance)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $advance->staff->name }}</div>
                                        <div class="small text-muted">{{ $advance->purpose ?? '—' }}</div>
                                    </td>
                                    <td>
                                        <strong>Ksh {{ number_format($advance->amount, 2) }}</strong>
                                    </td>
                                    <td>
                                        <span class="text-success">Ksh {{ number_format($advance->amount_repaid, 2) }}</span>
                                    </td>
                                    <td>
                                        <strong class="text-primary">Ksh {{ number_format($advance->balance, 2) }}</strong>
                                    </td>
                                    <td>
                                        <span class="pill-badge pill-info">{{ ucfirst(str_replace('_', ' ', $advance->repayment_method)) }}</span>
                                        @if($advance->monthly_deduction_amount)
                                            <div class="small text-muted">Ksh {{ number_format($advance->monthly_deduction_amount, 2) }}/month</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $advance->advance_date->format('M d, Y') }}</div>
                                        @if($advance->expected_completion_date)
                                            <div class="small text-muted">Due: {{ $advance->expected_completion_date->format('M d, Y') }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $badgeColors = [
                                              'pending' => 'pill-warning',
                                              'approved' => 'pill-info',
                                              'active' => 'pill-success',
                                              'completed' => 'pill-secondary',
                                              'cancelled' => 'pill-danger'
                                            ];
                                            $badge = $badgeColors[$advance->status] ?? 'pill-secondary';
                                        @endphp
                                        <span class="pill-badge {{ $badge }}">{{ ucfirst($advance->status) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('hr.payroll.advances.show', $advance->id) }}" class="btn btn-sm btn-ghost-strong" title="View">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            @if($advance->status === 'pending')
                                                <a href="{{ route('hr.payroll.advances.edit', $advance->id) }}" class="btn btn-sm btn-ghost-strong" title="Edit">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No advances found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($advances->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Showing {{ $advances->firstItem() }}–{{ $advances->lastItem() }} of {{ $advances->total() }} advances
                    </div>
                    {{ $advances->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


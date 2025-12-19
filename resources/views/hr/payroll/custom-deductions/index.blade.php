@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Custom Deductions</div>
                <h1 class="mb-1">Custom Deductions</h1>
                <p class="text-muted mb-0">Manage staff custom deductions.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('hr.payroll.custom-deductions.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> New Deduction
                </a>
                @if($deductions->total())
                    <span class="pill-badge pill-secondary">{{ $deductions->total() }} records</span>
                @endif
            </div>
        </div>

        @include('partials.alerts')

        {{-- Filters --}}
        <div class="settings-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Filters</h5>
                    <p class="text-muted small mb-0">Refine by staff, type, and status.</p>
                </div>
                <span class="pill-badge pill-secondary">Live query</span>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Staff</label>
                        <select name="staff_id" class="form-select">
                            <option value="">All Staff</option>
                            @foreach($staff as $s)
                                <option value="{{ $s->id }}" @selected(request('staff_id')==$s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Deduction Type</label>
                        <select name="deduction_type_id" class="form-select">
                            <option value="">All Types</option>
                            @foreach($types as $type)
                                <option value="{{ $type->id }}" @selected(request('deduction_type_id')==$type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" @selected(request('status')==='active')>Active</option>
                            <option value="completed" @selected(request('status')==='completed')>Completed</option>
                            <option value="suspended" @selected(request('status')==='suspended')>Suspended</option>
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
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Custom Deductions</h5>
                    <p class="mb-0 text-muted small">Tracked deduction schedules for staff.</p>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    @if($deductions->total())
                        <span class="input-chip">{{ $deductions->total() }} total</span>
                    @endif
                    <span class="pill-badge pill-info">Progress shows when total set</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Staff</th>
                                <th>Deduction Type</th>
                                <th>Amount</th>
                                <th>Frequency</th>
                                <th>Effective Period</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deductions as $deduction)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $deduction->staff->name }}</div>
                                        <div class="small text-muted">ID: {{ $deduction->staff->staff_id }}</div>
                                    </td>
                                    <td>
                                        <span class="pill-badge pill-primary">{{ $deduction->deductionType->name }}</span>
                                    </td>
                                    <td>
                                        <strong>Ksh {{ number_format($deduction->amount, 2) }}</strong>
                                        @if($deduction->total_amount)
                                            <div class="small text-muted">of Ksh {{ number_format($deduction->total_amount, 2) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="pill-badge pill-info">{{ ucfirst($deduction->frequency) }}</span>
                                    </td>
                                    <td>
                                        <div>{{ $deduction->effective_from->format('M d, Y') }}</div>
                                        @if($deduction->effective_to)
                                            <div class="small text-muted">to {{ $deduction->effective_to->format('M d, Y') }}</div>
                                        @else
                                            <div class="small text-muted">Ongoing</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($deduction->total_amount)
                                            @php
                                              $progress = ($deduction->amount_deducted / $deduction->total_amount) * 100;
                                            @endphp
                                            <div class="small mb-1">{{ number_format($progress, 1) }}%</div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-success" style="width: {{ $progress }}%"></div>
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $badgeColors = [
                                              'active' => 'pill-success',
                                              'completed' => 'pill-secondary',
                                              'suspended' => 'pill-warning',
                                              'cancelled' => 'pill-danger'
                                            ];
                                            $badge = $badgeColors[$deduction->status] ?? 'pill-secondary';
                                        @endphp
                                        <span class="pill-badge {{ $badge }}">{{ ucfirst($deduction->status) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('hr.payroll.custom-deductions.show', $deduction->id) }}" class="btn btn-sm btn-ghost-strong" title="View">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            @if($deduction->status === 'active')
                                                <a href="{{ route('hr.payroll.custom-deductions.edit', $deduction->id) }}" class="btn btn-sm btn-ghost-strong" title="Edit">
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
                                        No custom deductions found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($deductions->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Showing {{ $deductions->firstItem() }}–{{ $deductions->lastItem() }} of {{ $deductions->total() }} deductions
                    </div>
                    {{ $deductions->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


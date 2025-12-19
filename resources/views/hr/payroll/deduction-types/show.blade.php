@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Deduction Types</div>
                <h1 class="mb-1">Deduction Type Details</h1>
                <p class="text-muted mb-0">View deduction type information.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="pill-badge {{ $type->is_active ? 'pill-success' : 'pill-secondary' }}">{{ $type->is_active ? 'Active' : 'Inactive' }}</span>
                <a href="{{ route('hr.payroll.deduction-types.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <a href="{{ route('hr.payroll.deduction-types.edit', $type->id) }}" class="btn btn-settings-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="settings-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Deduction Type Information</h5>
                            <p class="text-muted small mb-0">Meta, calculation method, and flags.</p>
                        </div>
                        <span class="pill-badge pill-secondary">Ref #{{ $type->id }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Name</label>
                                <div class="h5 mb-0">{{ $type->name }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Code</label>
                                <div>
                                    @if($type->code)
                                        <span class="pill-badge pill-secondary fs-6">{{ $type->code }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($type->description)
                        <div class="mb-3">
                            <label class="text-muted small">Description</label>
                            <div>{{ $type->description }}</div>
                        </div>
                        @endif

                        <div class="divider"></div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Calculation Method</label>
                                <div>
                                    <span class="pill-badge pill-info">{{ ucfirst(str_replace('_', ' ', $type->calculation_method)) }}</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Default Value</label>
                                <div>
                                    @if($type->calculation_method === 'fixed_amount')
                                        <strong>Ksh {{ number_format($type->default_amount ?? 0, 2) }}</strong>
                                    @elseif($type->percentage)
                                        <strong>{{ number_format($type->percentage, 2) }}%</strong>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Type</label>
                                <div>
                                    @if($type->is_statutory)
                                        <span class="pill-badge pill-danger">Statutory</span>
                                    @else
                                        <span class="pill-badge pill-primary">Custom</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Status</label>
                                <div>
                                    <span class="pill-badge {{ $type->is_active ? 'pill-success' : 'pill-secondary' }}">
                                        {{ $type->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Requires Approval</label>
                                <div>
                                    <span class="pill-badge {{ $type->requires_approval ? 'pill-warning' : 'pill-success' }}">
                                        {{ $type->requires_approval ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                            </div>
                            @if($type->notes)
                            <div class="col-12 mb-3">
                                <label class="text-muted small">Notes</label>
                                <div class="alert alert-soft border-0 mb-0">{{ nl2br(e($type->notes)) }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if($type->customDeductions->count() > 0)
                <div class="settings-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Active Deductions</h5>
                            <p class="text-muted small mb-0">Sample of linked records</p>
                        </div>
                        <span class="pill-badge pill-info">{{ $type->customDeductions->where('status', 'active')->count() }} active</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-modern table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Staff</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Effective From</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($type->customDeductions->take(10) as $deduction)
                                        <tr>
                                            <td>{{ $deduction->staff->name }}</td>
                                            <td>Ksh {{ number_format($deduction->amount, 2) }}</td>
                                            <td><span class="pill-badge {{ $deduction->status === 'active' ? 'pill-success' : 'pill-secondary' }}">{{ ucfirst($deduction->status) }}</span></td>
                                            <td>{{ $deduction->effective_from->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if($type->customDeductions->count() > 10)
                                <div class="text-center mt-2">
                                    <small class="text-muted">Showing 10 of {{ $type->customDeductions->count() }} deductions</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection


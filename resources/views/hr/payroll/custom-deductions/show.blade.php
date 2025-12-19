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
                <h1 class="mb-1">Custom Deduction Details</h1>
                <p class="text-muted mb-0">View deduction information.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
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
                <a href="{{ route('hr.payroll.custom-deductions.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                @if($deduction->status === 'active')
                    <a href="{{ route('hr.payroll.custom-deductions.edit', $deduction->id) }}" class="btn btn-settings-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <div class="settings-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Deduction Information</h5>
                            <p class="text-muted small mb-0">Staff, amount, and schedule.</p>
                        </div>
                        <span class="pill-badge pill-secondary">Ref #{{ $deduction->id }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Staff Member</label>
                                <div class="fw-semibold">{{ $deduction->staff->name }}</div>
                                <div class="small text-muted">ID: {{ $deduction->staff->staff_id }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Deduction Type</label>
                                <div>
                                    <span class="pill-badge pill-primary fs-6">{{ $deduction->deductionType->name }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="text-muted small">Deduction Amount</label>
                                <div class="h4 text-primary mb-0">Ksh {{ number_format($deduction->amount, 2) }}</div>
                            </div>
                            @if($deduction->total_amount)
                            <div class="col-md-4">
                                <label class="text-muted small">Total Amount</label>
                                <div class="h5 text-info mb-0">Ksh {{ number_format($deduction->total_amount, 2) }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Amount Deducted</label>
                                <div class="h5 text-success mb-0">Ksh {{ number_format($deduction->amount_deducted, 2) }}</div>
                            </div>
                            @endif
                        </div>

                        <div class="divider"></div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Frequency</label>
                                <div>
                                    <span class="pill-badge pill-info">{{ ucfirst($deduction->frequency) }}</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Status</label>
                                <div>
                                    <span class="pill-badge {{ $badge }}">{{ ucfirst($deduction->status) }}</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Effective From</label>
                                <div>{{ $deduction->effective_from->format('F d, Y') }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Effective To</label>
                                <div>{{ $deduction->effective_to ? $deduction->effective_to->format('F d, Y') : 'Ongoing' }}</div>
                            </div>
                            @if($deduction->total_installments)
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Installments</label>
                                <div>{{ $deduction->installment_number }} / {{ $deduction->total_installments }}</div>
                            </div>
                            @endif
                            @if($deduction->staffAdvance)
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Linked Advance</label>
                                <div>
                                    <a href="{{ route('hr.payroll.advances.show', $deduction->staffAdvance->id) }}" class="text-decoration-none">
                                        Advance #{{ $deduction->staffAdvance->id }}
                                    </a>
                                </div>
                            </div>
                            @endif
                            @if($deduction->description)
                            <div class="col-12 mb-3">
                                <label class="text-muted small">Description</label>
                                <div>{{ $deduction->description }}</div>
                            </div>
                            @endif
                            @if($deduction->notes)
                            <div class="col-12 mb-3">
                                <label class="text-muted small">Notes</label>
                                <div class="alert alert-soft border-0 mb-0">{{ nl2br(e($deduction->notes)) }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                @if($deduction->status === 'active')
                <div class="settings-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Actions</h5>
                        <span class="pill-badge pill-warning">Active</span>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('hr.payroll.custom-deductions.suspend', $deduction->id) }}" method="POST" onsubmit="return confirm('Suspend this deduction?')">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="bi bi-pause-circle"></i> Suspend
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                @if($deduction->status === 'suspended')
                <div class="settings-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Actions</h5>
                        <span class="pill-badge pill-secondary">Suspended</span>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('hr.payroll.custom-deductions.activate', $deduction->id) }}" method="POST" onsubmit="return confirm('Activate this deduction?')">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-play-circle"></i> Activate
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                @if($deduction->total_amount)
                <div class="settings-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Progress</h5>
                        <span class="pill-badge pill-success">{{ number_format($percentage, 1) }}%</span>
                    </div>
                    <div class="card-body">
                        @php
                            $percentage = ($deduction->amount_deducted / $deduction->total_amount) * 100;
                        @endphp
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Deduction Progress</span>
                                <span>{{ number_format($percentage, 1) }}%</span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentage }}%">{{ number_format($percentage, 1) }}%</div>
                            </div>
                        </div>
                        <div class="small text-muted">
                            <div>Total: Ksh {{ number_format($deduction->total_amount, 2) }}</div>
                            <div>Deducted: Ksh {{ number_format($deduction->amount_deducted, 2) }}</div>
                            <div>Remaining: Ksh {{ number_format($deduction->total_amount - $deduction->amount_deducted, 2) }}</div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

